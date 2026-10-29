<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\ContentMedia;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ContentController extends Controller
{
    public const AUTHOR_COLUMNS = 'author:id,name,nip,avatar,department,position,points';

    public function __construct(
        private GamificationService $gamification,
        private NotificationService $notifier,
    ) {}

    /** Knowledge Feed – hanya konten yang sudah publish */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Content::published()
            ->with([self::AUTHOR_COLUMNS, 'category', 'community:id,name,slug', 'media'])
            ->withViewerState($user);

        if ($cat = $request->query('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $cat));
        }
        if ($community = $request->query('community_id')) {
            $query->where('community_id', $community);
        }
        if ($author = $request->query('author_id')) {
            $query->where('user_id', $author);
        }
        if ($media = $request->query('media')) {
            $query->whereHas('media', fn ($q) => $q->where('type', $media));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'ilike', "%$q%")
                    ->orWhere('body', 'ilike', "%$q%")
                    ->orWhereRaw('CAST(tags AS TEXT) ILIKE ?', ["%$q%"]);
            });
        }

        match ($request->query('sort', 'latest')) {
            'popular' => $query->orderByDesc('likes_count')->orderByDesc('published_at'),
            'trending' => $query->where('published_at', '>=', now()->subDays(14))
                ->orderByRaw('(likes_count * 2 + comments_count * 3 + shares_count * 2 + saves_count * 2 + views_count * 0.1) DESC')
                ->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        return $query->paginate((int) $request->query('per_page', 10));
    }

    public function show(Request $request, Content $content)
    {
        $user = $request->user();
        $canView = $content->status === Content::STATUS_PUBLISHED
            || $content->user_id === $user->id
            || $user->isReviewer();

        abort_unless($canView, 404, 'Konten tidak ditemukan.');

        if ($content->status === Content::STATUS_PUBLISHED && $content->user_id !== $user->id) {
            $content->increment('views_count');
            $alreadyViewedToday = \App\Models\PointTransaction::where('user_id', $user->id)
                ->where('action', 'content_viewed')
                ->where('reference_type', $content->getMorphClass())
                ->where('reference_id', $content->id)
                ->whereDate('created_at', today())
                ->exists();

            if (! $alreadyViewedToday) {
                $this->gamification->award($user, 'content_viewed', "Membaca konten \"{$content->title}\"", $content);
            }
        }

        $content->load([self::AUTHOR_COLUMNS, 'category', 'community:id,name,slug', 'media', 'reviewer:id,name']);
        $content->loadExists([
            'likes as is_liked' => fn ($q) => $q->where('user_id', $user->id),
            'saves as is_saved' => fn ($q) => $q->where('user_id', $user->id),
        ]);

        $related = Content::published()
            ->where('id', '!=', $content->id)
            ->where('category_id', $content->category_id)
            ->with([self::AUTHOR_COLUMNS, 'category'])
            ->latest('published_at')
            ->limit(4)
            ->get(['id', 'title', 'user_id', 'category_id', 'published_at', 'likes_count']);

        return response()->json(['content' => $content, 'related' => $related]);
    }

    public function store(Request $request)
    {
        $data = $this->validateContent($request);
        $user = $request->user();
        $submit = $request->input('action', 'submit') === 'submit';

        $content = Content::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'],
            'community_id' => $data['community_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
            'tags' => $this->parseTags($request->input('tags')),
            'status' => $submit ? Content::STATUS_PENDING : Content::STATUS_DRAFT,
        ]);

        $this->storeMedia($content, $request->file('media', []));

        $points = 0;
        if ($submit) {
            $points = $this->afterSubmit($content, $user);
        }

        return response()->json([
            'message' => $submit
                ? 'Konten berhasil diunggah dan sedang menunggu review.'
                : 'Konten disimpan sebagai draft.',
            'content' => $content->load('media', 'category'),
            'points_earned' => $points,
        ], 201);
    }

    public function update(Request $request, Content $content)
    {
        $user = $request->user();
        abort_unless($content->user_id === $user->id, 403, 'Anda tidak dapat mengubah konten ini.');
        abort_if($content->status === Content::STATUS_PUBLISHED, 422, 'Konten yang sudah publish tidak dapat diubah.');

        $data = $this->validateContent($request, true);
        $submit = $request->input('action', 'submit') === 'submit';
        $wasPending = $content->status === Content::STATUS_PENDING;

        $content->fill([
            'category_id' => $data['category_id'],
            'community_id' => $data['community_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
            'tags' => $this->parseTags($request->input('tags')),
            'status' => $submit ? Content::STATUS_PENDING : Content::STATUS_DRAFT,
        ]);
        if ($submit) {
            $content->review_note = null;
            $content->reviewer_id = null;
            $content->reviewed_at = null;
        }
        $content->save();

        if ($removed = $request->input('remove_media')) {
            $content->media()->whereIn('id', (array) $removed)->get()->each(function (ContentMedia $m) {
                Storage::disk('public')->delete($m->path);
                $m->delete();
            });
        }
        $this->storeMedia($content, $request->file('media', []));

        $points = 0;
        if ($submit && ! $wasPending) {
            $points = $this->afterSubmit($content, $user);
        }

        return response()->json([
            'message' => $submit ? 'Konten dikirim ulang untuk review.' : 'Draft diperbarui.',
            'content' => $content->load('media', 'category'),
            'points_earned' => $points,
        ]);
    }

    public function destroy(Request $request, Content $content)
    {
        $user = $request->user();
        abort_unless($content->user_id === $user->id || $user->role === 'admin', 403);

        foreach ($content->media as $m) {
            Storage::disk('public')->delete($m->path);
        }
        $content->delete();

        return response()->json(['message' => 'Konten dihapus.']);
    }

    /** My Contribution */
    public function mine(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        $query = $user->contents()
            ->with(['category', 'media', 'reviewer:id,name', 'community:id,name,slug'])
            ->latest('updated_at');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $counts = $user->contents()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totals = $user->contents()->published()->selectRaw(
            'COALESCE(SUM(likes_count),0) as likes, COALESCE(SUM(comments_count),0) as comments, COALESCE(SUM(views_count),0) as views, COALESCE(SUM(shares_count),0) as shares, COALESCE(SUM(saves_count),0) as saves'
        )->first();

        return response()->json([
            'data' => $query->paginate(12),
            'counts' => [
                'all' => $counts->sum(),
                'draft' => $counts['draft'] ?? 0,
                'pending' => $counts['pending'] ?? 0,
                'published' => $counts['published'] ?? 0,
                'rejected' => $counts['rejected'] ?? 0,
            ],
            'engagement' => $totals,
        ]);
    }

    /** Saved */
    public function saved(Request $request)
    {
        $user = $request->user();

        return Content::published()
            ->whereHas('saves', fn ($q) => $q->where('user_id', $user->id))
            ->with([self::AUTHOR_COLUMNS, 'category', 'media'])
            ->withViewerState($user)
            ->orderByDesc(
                \App\Models\Save::select('created_at')
                    ->whereColumn('content_id', 'contents.id')
                    ->where('user_id', $user->id)
                    ->limit(1)
            )
            ->paginate(12);
    }

    // ------------------------------------------------------------------

    private function validateContent(Request $request, bool $updating = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:20'],
            'category_id' => ['required', 'exists:categories,id'],
            'community_id' => ['nullable', 'exists:communities,id'],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => [
                'file',
                'max:51200', // 50 MB
                'mimes:jpg,jpeg,png,gif,webp,mp4,mov,webm,avi,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv',
            ],
        ], [
            'body.min' => 'Isi konten minimal 20 karakter.',
            'media.*.max' => 'Ukuran file maksimal 50 MB.',
            'media.*.mimes' => 'Format file tidak didukung.',
        ]);
    }

    private function parseTags($tags): array
    {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : explode(',', $tags);
        }

        return collect($tags ?? [])
            ->map(fn ($t) => trim(ltrim((string) $t, '#')))
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();
    }

    /** @param UploadedFile[] $files */
    private function storeMedia(Content $content, array $files): void
    {
        $order = (int) $content->media()->max('sort_order');

        foreach ($files as $file) {
            $mime = $file->getMimeType() ?? '';
            $type = str_starts_with($mime, 'image/') ? 'image'
                : (str_starts_with($mime, 'video/') ? 'video' : 'document');

            $path = $file->store("contents/{$content->id}", 'public');

            $content->media()->create([
                'type' => $type,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $mime,
                'size' => $file->getSize(),
                'sort_order' => ++$order,
            ]);
        }
    }

    private function afterSubmit(Content $content, $user): int
    {
        $points = 0;
        // Hanya beri poin jika konten ini belum pernah mendapat poin submission sebelumnya
        $alreadyAwarded = \App\Models\PointTransaction::where('action', 'content_submitted')
            ->where('reference_type', $content->getMorphClass())
            ->where('reference_id', $content->id)
            ->exists();

        if (! $alreadyAwarded) {
            $points = $this->gamification->award($user, 'content_submitted', "Mengunggah konten \"{$content->title}\"", $content);
        }

        $this->notifier->notifyReviewers(
            'Konten baru menunggu review',
            "{$user->name} mengunggah \"{$content->title}\"",
            $user,
            $content,
        );

        return $points;
    }
}
