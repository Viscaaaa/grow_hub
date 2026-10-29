<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\PointRule;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GamificationService
{
    /**
     * Tambahkan point ke user dan catat di ledger.
     * Mengembalikan jumlah point yang benar-benar diberikan (0 jika kena batas harian atau dinonaktifkan admin).
     */
    public function award(User $user, string $action, string $description, ?Model $reference = null, ?int $points = null): int
    {
        // 1. Cek aturan dinamis dari database (PointRule) jika tersedia
        $rule = null;
        try {
            $rule = PointRule::where('action', $action)->first();
        } catch (\Throwable $e) {
            // Abaikan jika tabel belum siap/tersedia
        }

        if ($rule) {
            // Jika aturan dinonaktifkan oleh Administrator, tidak ada poin yang diberikan
            if (! $rule->is_active) {
                return 0;
            }
            // Prioritaskan nilai poin dari aturan admin jika ada
            $points = $rule->points > 0 ? (int) $rule->points : ($points ?? 0);
            $cap = $rule->daily_cap;
        } else {
            // Fallback ke konfigurasi bawaan jika belum ada di database
            $points ??= (int) config("gamification.points.$action", 0);
            $cap = config("gamification.daily_caps.$action");
        }

        if ($points <= 0) {
            return 0;
        }

        if ($cap && $cap > 0) {
            $todayCount = PointTransaction::where('user_id', $user->id)
                ->where('action', $action)
                ->whereDate('created_at', today())
                ->count();
            if ($todayCount >= $cap) {
                return 0;
            }
        }

        $levelBefore = $user->level_info['level'];

        DB::transaction(function () use ($user, $action, $description, $reference, $points) {
            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $points,
                'action' => $action,
                'description' => $description,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);

            $user->points = max(0, $user->points + $points);
            $user->save();
        });

        $levelAfter = LevelService::forPoints($user->points);
        if ($levelAfter['level'] > $levelBefore) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'level_up',
                'title' => "Selamat! Kamu naik ke Level {$levelAfter['level']} – {$levelAfter['name']} 🎉",
                'message' => 'Terus berbagi pengetahuan untuk mencapai level berikutnya.',
                'link' => '/dashboard',
            ]);
        }

        return $points;
    }

    /**
     * Catat aktivitas harian user untuk menghitung streak.
     */
    public function recordActivity(User $user): void
    {
        $today = today();
        $last = $user->last_activity_date;

        if ($last && $last->isSameDay($today)) {
            return;
        }

        $user->current_streak = ($last && $last->isSameDay($today->copy()->subDay()))
            ? $user->current_streak + 1
            : 1;
        $user->longest_streak = max($user->longest_streak, $user->current_streak);
        $user->last_activity_date = $today;
        $user->save();

        $this->award($user, 'daily_login', 'Aktivitas harian – streak hari ke-'.$user->current_streak);

        $every = (int) config('gamification.streak_bonus.every_days', 7);
        if ($every > 0 && $user->current_streak % $every === 0) {
            $bonus = (int) config('gamification.streak_bonus.points', 15);
            $this->award($user, 'streak_bonus', "Bonus streak {$user->current_streak} hari 🔥", null, $bonus);

            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'streak',
                'title' => "Streak {$user->current_streak} hari! +{$bonus} point bonus 🔥",
                'message' => 'Pertahankan konsistensimu setiap hari.',
                'link' => '/dashboard',
            ]);
        }
    }
}
