<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Like;
use App\Models\Save;
use App\Models\Share;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    public function __construct(
        private GamificationService $gamification,
        private NotificationService $notifier,
    ) {}

    public function toggleLike(Request $request, Content $content)
    {
        $this->ensurePublished($content);
        $user = $request->user();

        $existing = Like::where('user_id', $user->id)->where('content_id', $content->id)->first();

        if ($existing) {
            $existing->delete();
            $content->decrement('likes_count');
            $liked = false;
        } else {
            Like::create(['user_id' => $user->id, 'content_id' => $content->id]);
            $content->increment('likes_count');
            $liked = true;

            if ($content->user_id !== $user->id) {
                // Point hanya sekali per pasangan user-konten
                $alreadyRewarded = \App\Models\PointTransaction::where('user_id', $content->user_id)
                    ->where('action', 'like_received')
                    ->where('reference_type', $content->getMorphClass())
                    ->where('reference_id', $content->id)
                    ->where('description', 'like', "%#{$user->id})")
                    ->exists();
                if (! $alreadyRewarded) {
                    $this->gamification->award($content->author, 'like_received', "Konten kamu disukai (#{$user->id})", $content);
                }
                $this->notifier->notify($content->user_id, 'like', "{$user->name} menyukai kontenmu", $content->title, $user, $content);
            }
        }

        return response()->json(['liked' => $liked, 'likes_count' => $content->fresh()->likes_count]);
    }

    public function toggleSave(Request $request, Content $content)
    {
        $this->ensurePublished($content);
        $user = $request->user();

        $existing = Save::where('user_id', $user->id)->where('content_id', $content->id)->first();

        if ($existing) {
            $existing->delete();
            $content->decrement('saves_count');
            $saved = false;
        } else {
            Save::create(['user_id' => $user->id, 'content_id' => $content->id]);
            $content->increment('saves_count');
            $saved = true;

            if ($content->user_id !== $user->id) {
                $alreadyRewarded = \App\Models\PointTransaction::where('user_id', $content->user_id)
                    ->where('action', 'save_received')
                    ->where('reference_id', $content->id)
                    ->where('description', 'like', "%#{$user->id})")
                    ->exists();
                if (! $alreadyRewarded) {
                    $this->gamification->award($content->author, 'save_received', "Konten kamu disimpan (#{$user->id})", $content);
                }
                $this->notifier->notify($content->user_id, 'save', "{$user->name} menyimpan kontenmu", $content->title, $user, $content);
            }
        }

        return response()->json(['saved' => $saved, 'saves_count' => $content->fresh()->saves_count]);
    }

    public function share(Request $request, Content $content)
    {
        $this->ensurePublished($content);
        $user = $request->user();
        $channel = $request->input('channel', 'link');

        Share::create(['user_id' => $user->id, 'content_id' => $content->id, 'channel' => $channel]);
        $content->increment('shares_count');

        $earned = 0;
        if ($content->user_id !== $user->id) {
            $alreadySharedToday = \App\Models\PointTransaction::where('user_id', $user->id)
                ->where('action', 'share_given')
                ->where('reference_type', $content->getMorphClass())
                ->where('reference_id', $content->id)
                ->whereDate('created_at', today())
                ->exists();

            if (! $alreadySharedToday) {
                $earned = $this->gamification->award($user, 'share_given', "Membagikan \"{$content->title}\"", $content);
                $this->gamification->award($content->author, 'share_received', "Konten kamu dibagikan oleh {$user->name}", $content);
                $this->notifier->notify($content->user_id, 'share', "{$user->name} membagikan kontenmu", $content->title, $user, $content);
            }
        }

        return response()->json(['shares_count' => $content->fresh()->shares_count, 'points_earned' => $earned]);
    }

    public function comments(Request $request, Content $content)
    {
        return $content->comments()
            ->whereNull('parent_id')
            ->with(['user:id,name,nip,avatar,department,points', 'replies'])
            ->latest()
            ->get();
    }

    public function storeComment(Request $request, Content $content)
    {
        $this->ensurePublished($content);
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:comments,id'],
        ]);
        $user = $request->user();

        $comment = $content->comments()->create([
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);
        $content->increment('comments_count');

        $earned = 0;
        if ($content->user_id !== $user->id) {
            $earned = $this->gamification->award($user, 'comment_given', "Berkomentar di \"{$content->title}\"", $content);
            $this->gamification->award($content->author, 'comment_received', "{$user->name} mengomentari kontenmu", $content);
            $this->notifier->notify($content->user_id, 'comment', "{$user->name} mengomentari kontenmu", str($data['body'])->limit(120), $user, $content);
        }

        if ($comment->parent_id) {
            $parent = Comment::find($comment->parent_id);
            if ($parent && $parent->user_id !== $content->user_id) {
                $this->notifier->notify($parent->user_id, 'reply', "{$user->name} membalas komentarmu", str($data['body'])->limit(120), $user, $content);
            }
        }

        return response()->json([
            'comment' => $comment->load('user:id,name,nip,avatar,department,points', 'replies'),
            'comments_count' => $content->fresh()->comments_count,
            'points_earned' => $earned,
        ], 201);
    }

    public function destroyComment(Request $request, Comment $comment)
    {
        $user = $request->user();
        abort_unless($comment->user_id === $user->id || $user->role === 'admin', 403);

        $content = $comment->content;
        $removed = 1 + $comment->replies()->count();
        $comment->delete();
        $content->decrement('comments_count', min($removed, $content->comments_count));

        return response()->json(['comments_count' => $content->fresh()->comments_count]);
    }

    private function ensurePublished(Content $content): void
    {
        abort_unless($content->status === Content::STATUS_PUBLISHED, 422, 'Konten belum dipublikasikan.');
    }
}
