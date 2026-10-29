<?php

namespace App\Services;

class LevelService
{
    /**
     * Hitung informasi level berdasarkan total point.
     */
    public static function forPoints(int $points): array
    {
        $levels = config('gamification.levels');
        $current = $levels[0];
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
            'next_level' => $next['level'] ?? null,
            'next_name' => $next['name'] ?? null,
            'next_min' => $next['min'] ?? null,
            'points_to_next' => $toNext,
            'progress' => min(100, max(0, $progress)),
        ];
    }
}
