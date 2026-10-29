<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Content;
use App\Models\User;

class NotificationService
{
    public function notify(int $userId, string $type, string $title, ?string $message = null, ?User $actor = null, ?Content $content = null, ?string $link = null): void
    {
        // Jangan kirim notifikasi ke diri sendiri
        if ($actor && $actor->id === $userId) {
            return;
        }

        AppNotification::create([
            'user_id' => $userId,
            'actor_id' => $actor?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'content_id' => $content?->id,
            'link' => $link ?? ($content ? "/content/{$content->id}" : null),
        ]);
    }

    public function notifyReviewers(string $title, ?string $message, User $actor, Content $content): void
    {
        User::whereIn('role', ['reviewer', 'admin'])
            ->where('id', '!=', $actor->id)
            ->pluck('id')
            ->each(fn ($id) => $this->notify($id, 'review_request', $title, $message, $actor, $content, '/review'));
    }
}
