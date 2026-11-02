<?php

namespace App\Services;

use App\Models\LevelRule;

class LevelService
{
    /**
     * Dapatkan daftar seluruh level aktif (baik dari database atau fallback ke config).
     */
    public static function all(): array
    {
        try {
            $dbLevels = LevelRule::getActiveTiers();
            if ($dbLevels->isNotEmpty()) {
                return $dbLevels->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'level' => (int) $item->level,
                        'name' => (string) $item->name,
                        'min' => (int) $item->min_points,
                        'description' => $item->description,
                        'badge_icon' => $item->badge_icon ?? 'award',
                        'badge_color' => $item->badge_color ?? '#10b981',
                    ];
                })->values()->all();
            }
        } catch (\Throwable $e) {
            // Fallback jika database belum siap
        }

        return config('gamification.levels', [
            ['level' => 1, 'name' => 'Seedling', 'min' => 0],
            ['level' => 2, 'name' => 'Sprout', 'min' => 100],
            ['level' => 3, 'name' => 'Sapling', 'min' => 250],
            ['level' => 4, 'name' => 'Grower', 'min' => 500],
            ['level' => 5, 'name' => 'Cultivator', 'min' => 900],
            ['level' => 6, 'name' => 'Harvester', 'min' => 1400],
            ['level' => 7, 'name' => 'Mentor', 'min' => 2000],
            ['level' => 8, 'name' => 'Evergreen', 'min' => 3000],
        ]);
    }

    /**
     * Hitung informasi level berdasarkan total point.
     */
    public static function forPoints(int $points): array
    {
        $levels = static::all();
        $current = $levels[0] ?? ['level' => 1, 'name' => 'Seedling', 'min' => 0];
        $next = null;

        foreach ($levels as $i => $lvl) {
            if ($points >= $lvl['min']) {
                $current = $lvl;
                $next = $levels[$i + 1] ?? null;
            }
        }

        $progress = 100;
        $toNext = 0;
        if ($next) {
            $range = $next['min'] - $current['min'];
            $progress = (int) floor((($points - $current['min']) / max($range, 1)) * 100);
            $toNext = $next['min'] - $points;
        }

        return [
            'level' => $current['level'],
            'name' => $current['name'],
            'min' => $current['min'],
            'badge_icon' => $current['badge_icon'] ?? 'award',
            'badge_color' => $current['badge_color'] ?? '#10b981',
            'next_level' => $next['level'] ?? null,
            'next_name' => $next['name'] ?? null,
            'next_min' => $next['min'] ?? null,
            'points_to_next' => max(0, $toNext),
            'progress' => min(100, max(0, $progress)),
        ];
    }
}
