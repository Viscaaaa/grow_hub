<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LevelRule;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLevelRuleController extends Controller
{
    /**
     * Tampilkan daftar seluruh aturan level, urutan, poin minimal, serta statistik distribusi user.
     */
    public function index(Request $request): JsonResponse
    {
        $levels = LevelRule::orderBy('level', 'asc')
            ->orderBy('min_points', 'asc')
            ->get();

        // Hitung distribusi user per level
        $activeLevels = $levels->where('is_active', true)->values();
        $userCountsByLevel = [];

        foreach ($activeLevels as $index => $lvl) {
            $min = (int) $lvl->min_points;
            $nextLvl = $activeLevels[$index + 1] ?? null;

            $query = User::where('is_active', true);
            if ($nextLvl) {
                $max = ((int) $nextLvl->min_points) - 1;
                $count = $query->whereBetween('points', [$min, $max])->count();
            } else {
                $count = $query->where('points', '>=', $min)->count();
            }

            $userCountsByLevel[$lvl->id] = $count;
        }

        // Lampirkan user_count ke setiap level model
        $levelsWithCounts = $levels->map(function ($lvl) use ($userCountsByLevel) {
            $arr = $lvl->toArray();
            $arr['user_count'] = $userCountsByLevel[$lvl->id] ?? 0;
            return $arr;
        });

        $stats = [
            'total_levels' => $levels->count(),
            'active_levels' => $levels->where('is_active', true)->count(),
            'inactive_levels' => $levels->where('is_active', false)->count(),
            'max_points' => (int) ($levels->where('is_active', true)->max('min_points') ?? 0),
            'total_users' => User::where('is_active', true)->count(),
        ];

        return response()->json([
            'levels' => $levelsWithCounts,
            'stats' => $stats,
        ]);
    }

    /**
     * Buat tingkatan level baru.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'min_points' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:500'],
            'badge_icon' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['badge_icon'] = $validated['badge_icon'] ?? 'award';
        $validated['badge_color'] = $validated['badge_color'] ?? '#10b981';

        $levelRule = LevelRule::create($validated);

        return response()->json([
            'message' => "Tingkatan level \"{$levelRule->name}\" (Level {$levelRule->level}) berhasil ditambahkan.",
            'level' => $levelRule,
        ], 201);
    }

    /**
     * Perbarui tingkatan level tunggal.
     */
    public function update(Request $request, LevelRule $levelRule): JsonResponse
    {
        $validated = $request->validate([
            'level' => ['required', 'integer', 'min:1', 'max:100'],
            'name' => ['required', 'string', 'max:100'],
            'min_points' => ['required', 'integer', 'min:0', 'max:1000000'],
            'description' => ['nullable', 'string', 'max:500'],
            'badge_icon' => ['nullable', 'string', 'max:50'],
            'badge_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['required', 'boolean'],
        ]);

        $levelRule->update($validated);

        return response()->json([
            'message' => "Pengaturan level \"{$levelRule->name}\" berhasil diperbarui.",
            'level' => $levelRule,
        ]);
    }

    /**
     * Simpan pembaruan banyak tingkatan level sekaligus (batch update).
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.id' => ['required', 'integer', 'exists:level_rules,id'],
            'levels.*.level' => ['required', 'integer', 'min:1', 'max:100'],
            'levels.*.name' => ['required', 'string', 'max:100'],
            'levels.*.min_points' => ['required', 'integer', 'min:0', 'max:1000000'],
            'levels.*.description' => ['nullable', 'string', 'max:500'],
            'levels.*.badge_icon' => ['nullable', 'string', 'max:50'],
            'levels.*.badge_color' => ['nullable', 'string', 'max:50'],
            'levels.*.is_active' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['levels'] as $item) {
                LevelRule::where('id', $item['id'])->update([
                    'level' => $item['level'],
                    'name' => $item['name'],
                    'min_points' => $item['min_points'],
                    'description' => $item['description'] ?? null,
                    'badge_icon' => $item['badge_icon'] ?? 'award',
                    'badge_color' => $item['badge_color'] ?? '#10b981',
                    'is_active' => $item['is_active'],
                    'updated_at' => now(),
                ]);
            }
        });

        $updatedLevels = LevelRule::orderBy('level', 'asc')->orderBy('min_points', 'asc')->get();

        return response()->json([
            'message' => 'Semua konfigurasi tingkatan level berhasil disimpan.',
            'levels' => $updatedLevels,
        ]);
    }

    /**
     * Toggle aktif / non-aktif 1-klik untuk suatu tingkatan level.
     */
    public function toggle(LevelRule $levelRule): JsonResponse
    {
        if ($levelRule->is_active) {
            $activeCount = LevelRule::where('is_active', true)->count();
            if ($activeCount <= 1) {
                return response()->json([
                    'message' => 'Tidak dapat menonaktifkan level ini karena minimal harus ada satu level yang aktif dalam sistem.',
                ], 422);
            }
        }

        $levelRule->is_active = ! $levelRule->is_active;
        $levelRule->save();

        $statusText = $levelRule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message' => "Level \"{$levelRule->name}\" berhasil {$statusText}.",
            'level' => $levelRule,
        ]);
    }

    /**
     * Hapus tingkatan level dari sistem.
     */
    public function destroy(LevelRule $levelRule): JsonResponse
    {
        if (LevelRule::count() <= 1) {
            return response()->json([
                'message' => 'Tidak dapat menghapus level ini karena sistem memerlukan minimal 1 tingkatan level.',
            ], 422);
        }

        $name = $levelRule->name;
        $levelRule->delete();

        return response()->json([
            'message' => "Tingkatan level \"{$name}\" berhasil dihapus dari sistem.",
        ]);
    }

    /**
     * Kembalikan konfigurasi seluruh tingkatan level ke nilai bawaan sistem (8 level standar).
     */
    public function resetDefaults(): JsonResponse
    {
        $defaultLevels = [
            [
                'level' => 1,
                'name' => 'Seedling',
                'min_points' => 0,
                'description' => 'Tingkat pemula untuk karyawan yang baru memulai perjalanan pengetahuan di GrowHub.',
                'badge_icon' => 'sprout',
                'badge_color' => '#10b981',
                'is_active' => true,
            ],
            [
                'level' => 2,
                'name' => 'Sprout',
                'min_points' => 100,
                'description' => 'Mulai aktif membaca dan berbagi ide pertama di platform.',
                'badge_icon' => 'leaf',
                'badge_color' => '#059669',
                'is_active' => true,
            ],
            [
                'level' => 3,
                'name' => 'Sapling',
                'min_points' => 250,
                'description' => 'Membangun kebiasaan berbagi pengetahuan dan berdiskusi dengan rekan kerja.',
                'badge_icon' => 'tree',
                'badge_color' => '#0d9488',
                'is_active' => true,
            ],
            [
                'level' => 4,
                'name' => 'Grower',
                'min_points' => 500,
                'description' => 'Kontributor reguler yang konsisten membagikan wawasan retail berkualitas.',
                'badge_icon' => 'flame',
                'badge_color' => '#3b82f6',
                'is_active' => true,
            ],
            [
                'level' => 5,
                'name' => 'Cultivator',
                'min_points' => 900,
                'description' => 'Penggerak komunitas pengetahuan yang aktif memberi solusi.',
                'badge_icon' => 'award',
                'badge_color' => '#6366f1',
                'is_active' => true,
            ],
            [
                'level' => 6,
                'name' => 'Harvester',
                'min_points' => 1400,
                'description' => 'Ahli operasional retail yang karyanya banyak diapresiasi dan disimpan.',
                'badge_icon' => 'star',
                'badge_color' => '#8b5cf6',
                'is_active' => true,
            ],
            [
                'level' => 7,
                'name' => 'Mentor',
                'min_points' => 2000,
                'description' => 'Teladan inspiratif dan mentor bagi karyawan lain di seluruh cabang.',
                'badge_icon' => 'sparkles',
                'badge_color' => '#ec4899',
                'is_active' => true,
            ],
            [
                'level' => 8,
                'name' => 'Evergreen',
                'min_points' => 3000,
                'description' => 'Tingkat penguasaan tertinggi, pilar utama ekosistem pengetahuan GrowHub.',
                'badge_icon' => 'crown',
                'badge_color' => '#f59e0b',
                'is_active' => true,
            ],
        ];

        DB::transaction(function () use ($defaultLevels) {
            LevelRule::truncate();

            foreach ($defaultLevels as $item) {
                LevelRule::create($item);
            }
        });

        return response()->json([
            'message' => 'Konfigurasi tingkatan level telah berhasil direset ke standar bawaan sistem.',
            'levels' => LevelRule::orderBy('level', 'asc')->orderBy('min_points', 'asc')->get(),
        ]);
    }
}
