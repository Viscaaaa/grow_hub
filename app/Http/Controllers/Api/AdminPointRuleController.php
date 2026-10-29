<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointRule;
use App\Models\PointTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPointRuleController extends Controller
{
    /**
     * Tampilkan daftar seluruh aturan sistem poin dan status aktivasinya.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PointRule::query();

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $rules = $query->orderBy('sort_order')->orderBy('id')->get();

        $stats = [
            'total_rules' => PointRule::count(),
            'active_rules' => PointRule::where('is_active', true)->count(),
            'inactive_rules' => PointRule::where('is_active', false)->count(),
            'total_points_distributed' => (int) PointTransaction::sum('points'),
            'categories' => PointRule::select('category')->distinct()->pluck('category'),
        ];

        return response()->json([
            'rules' => $rules,
            'stats' => $stats,
        ]);
    }

    /**
     * Perbarui aturan poin tunggal (besaran poin, batas harian, status on/off).
     */
    public function update(Request $request, PointRule $pointRule): JsonResponse
    {
        $validated = $request->validate([
            'points' => ['required', 'integer', 'min:0', 'max:10000'],
            'daily_cap' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['required', 'boolean'],
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $pointRule->update($validated);

        return response()->json([
            'message' => "Pengaturan aturan \"{$pointRule->name}\" berhasil diperbarui.",
            'rule' => $pointRule,
        ]);
    }

    /**
     * Toggle aktif / non-aktif 1-klik untuk suatu aturan tindakan poin.
     */
    public function toggle(PointRule $pointRule): JsonResponse
    {
        $pointRule->is_active = ! $pointRule->is_active;
        $pointRule->save();

        $statusText = $pointRule->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return response()->json([
            'message' => "Sistem poin untuk tindakan \"{$pointRule->name}\" berhasil {$statusText}.",
            'rule' => $pointRule,
        ]);
    }

    /**
     * Simpan pembaruan banyak aturan poin sekaligus (batch update).
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rules' => ['required', 'array', 'min:1'],
            'rules.*.id' => ['required', 'integer', 'exists:point_rules,id'],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:10000'],
            'rules.*.is_active' => ['required', 'boolean'],
            'rules.*.daily_cap' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['rules'] as $item) {
                PointRule::where('id', $item['id'])->update([
                    'points' => $item['points'],
                    'is_active' => $item['is_active'],
                    'daily_cap' => $item['daily_cap'] ?? null,
                    'updated_at' => now(),
                ]);
            }
        });

        $updatedRules = PointRule::orderBy('sort_order')->orderBy('id')->get();

        return response()->json([
            'message' => 'Semua konfigurasi sistem poin berhasil disimpan.',
            'rules' => $updatedRules,
        ]);
    }

    /**
     * Kembalikan seluruh aturan poin ke nilai default sistem.
     */
    public function resetDefaults(): JsonResponse
    {
        $defaults = [
            'daily_login' => ['points' => 2, 'daily_cap' => 1, 'is_active' => true],
            'content_submitted' => ['points' => 5, 'daily_cap' => null, 'is_active' => true],
            'content_published' => ['points' => 25, 'daily_cap' => null, 'is_active' => true],
            'content_viewed' => ['points' => 1, 'daily_cap' => 10, 'is_active' => false],
            'like_received' => ['points' => 2, 'daily_cap' => null, 'is_active' => true],
            'comment_given' => ['points' => 1, 'daily_cap' => 10, 'is_active' => true],
            'comment_received' => ['points' => 3, 'daily_cap' => null, 'is_active' => true],
            'share_given' => ['points' => 1, 'daily_cap' => 10, 'is_active' => true],
            'share_received' => ['points' => 2, 'daily_cap' => null, 'is_active' => true],
            'save_received' => ['points' => 2, 'daily_cap' => null, 'is_active' => true],
            'review_done' => ['points' => 3, 'daily_cap' => null, 'is_active' => true],
            'course_completed' => ['points' => 15, 'daily_cap' => null, 'is_active' => true],
        ];

        DB::transaction(function () use ($defaults) {
            foreach ($defaults as $action => $data) {
                PointRule::where('action', $action)->update([
                    'points' => $data['points'],
                    'daily_cap' => $data['daily_cap'],
                    'is_active' => $data['is_active'],
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'message' => 'Konfigurasi sistem poin telah direset ke nilai bawaan sistem.',
            'rules' => PointRule::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * Nonaktifkan seluruh aturan sistem poin sekaligus (is_active = false).
     */
    public function disableAll(): JsonResponse
    {
        PointRule::query()->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Semua aturan sistem poin berhasil dinonaktifkan.',
            'rules' => PointRule::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    /**
     * Aktifkan seluruh aturan sistem poin sekaligus (is_active = true).
     */
    public function enableAll(): JsonResponse
    {
        PointRule::query()->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Semua aturan sistem poin berhasil diaktifkan.',
            'rules' => PointRule::orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
