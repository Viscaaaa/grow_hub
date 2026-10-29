<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(
        private GamificationService $gamification,
        private NotificationService $notifier,
    ) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = Content::where('status', '!=', Content::STATUS_DRAFT)
            ->with([ContentController::AUTHOR_COLUMNS, 'category', 'media', 'reviewer:id,name', 'community:id,name,slug']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where('title', 'ilike', "%$q%");
        }

        $status === 'pending' ? $query->oldest() : $query->latest('reviewed_at');

        return response()->json([
            'data' => $query->paginate(10),
            'stats' => [
                'pending' => Content::where('status', Content::STATUS_PENDING)->count(),
                'published' => Content::where('status', Content::STATUS_PUBLISHED)->count(),
                'rejected' => Content::where('status', Content::STATUS_REJECTED)->count(),
                'reviewed_today' => Content::whereDate('reviewed_at', today())->count(),
            ],
        ]);
    }

    public function approve(Request $request, Content $content)
    {
        abort_unless($content->status === Content::STATUS_PENDING, 422, 'Konten ini tidak dalam status menunggu review.');
        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $reviewer = $request->user();

        $content->update([
            'status' => Content::STATUS_PUBLISHED,
            'reviewer_id' => $reviewer->id,
            'review_note' => $data['note'] ?? null,
            'reviewed_at' => now(),
            'published_at' => now(),
        ]);

        $points = 0;
        $alreadyPublishedAwarded = \App\Models\PointTransaction::where('action', 'content_published')
            ->where('reference_type', $content->getMorphClass())
            ->where('reference_id', $content->id)
            ->exists();

        if (! $alreadyPublishedAwarded) {
            $points = $this->gamification->award($content->author, 'content_published', "Konten \"{$content->title}\" dipublikasikan", $content);
        }
        $reviewerPoints = $this->gamification->award($reviewer, 'review_done', "Mereview \"{$content->title}\"", $content);

        $this->notifier->notify(
            $content->user_id, 'approved',
            "Kontenmu telah dipublikasikan! +{$points} point 🎉",
            "\"{$content->title}\" lolos review dan kini dapat dilihat semua karyawan.",
            $reviewer, $content,
        );

        return response()->json([
            'message' => 'Konten disetujui dan dipublikasikan.',
            'content' => $content,
            'author_points_awarded' => $points,
            'reviewer_points_awarded' => $reviewerPoints,
        ]);
    }

    public function reject(Request $request, Content $content)
    {
        abort_unless($content->status === Content::STATUS_PENDING, 422, 'Konten ini tidak dalam status menunggu review.');
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']], [
            'note.required' => 'Berikan alasan penolakan agar kontributor dapat memperbaikinya.',
        ]);
        $reviewer = $request->user();

        $content->update([
            'status' => Content::STATUS_REJECTED,
            'reviewer_id' => $reviewer->id,
            'review_note' => $data['note'],
            'reviewed_at' => now(),
        ]);

        $reviewerPoints = $this->gamification->award($reviewer, 'review_done', "Mereview \"{$content->title}\"", $content);

        $this->notifier->notify(
            $content->user_id, 'rejected',
            'Kontenmu perlu diperbaiki',
            "\"{$content->title}\": {$data['note']}",
            $reviewer, $content, '/contributions',
        );

        return response()->json([
            'message' => 'Konten ditolak.',
            'content' => $content,
            'reviewer_points_awarded' => $reviewerPoints,
        ]);
    }
}
