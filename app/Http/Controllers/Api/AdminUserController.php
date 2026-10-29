<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * List users with optional search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Search term
        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('nip', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('department', 'ilike', "%{$search}%")
                    ->orWhere('position', 'ilike', "%{$search}%");
            });
        }

        // Role filter
        if ($role = $request->query('role')) {
            if (in_array($role, ['employee', 'reviewer', 'admin'], true)) {
                $query->where('role', $role);
            }
        }

        // Department filter
        if ($department = $request->query('department')) {
            $query->where('department', $department);
        }

        $sort = $request->query('sort', 'created_at');
        $order = strtolower($request->query('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sort, ['name', 'nip', 'points', 'created_at'], true)) {
            $query->orderBy($sort, $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $users = $query->paginate((int) $request->query('per_page', 10));

        // Get available departments for filters
        $departments = User::whereNotNull('department')
            ->select('department')
            ->distinct()
            ->pluck('department');

        return response()->json([
            'users' => $users,
            'departments' => $departments,
            'stats' => [
                'total_users' => User::count(),
                'total_employees' => User::where('role', 'employee')->count(),
                'total_reviewers' => User::where('role', 'reviewer')->count(),
                'total_admins' => User::where('role', 'admin')->count(),
            ],
        ]);
    }

    /**
     * Create a new user (employee / reviewer / admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'min:6', 'max:12', 'regex:/^[0-9]+$/', 'unique:users,nip'],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', Rule::in(['employee', 'reviewer', 'admin'])],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'bio' => ['nullable', 'string', 'max:500'],
        ], [
            'nip.regex' => 'Employee ID (NIP) must consist of numbers only (6-12 digits).',
            'nip.unique' => 'Employee ID (NIP) is already registered in the system.',
            'email.unique' => 'Email address is already in use.',
        ]);

        $initialPoints = (int) ($validated['points'] ?? 0);
        $passwordPlain = !empty($validated['password']) ? $validated['password'] : $validated['nip'];

        $user = User::create([
            'nip' => $validated['nip'],
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'password' => Hash::make($passwordPlain),
            'role' => $validated['role'],
            'department' => $validated['department'] ?? 'Store Operations',
            'position' => $validated['position'] ?? 'Team Member',
            'location' => $validated['location'] ?? 'Retail Store',
            'points' => $initialPoints,
            'current_streak' => 0,
            'longest_streak' => 0,
            'is_active' => true,
            'must_change_password' => true,
            'bio' => $validated['bio'] ?? null,
        ]);

        // If initial bonus points are awarded, log transaction
        if ($initialPoints > 0) {
            PointTransaction::create([
                'user_id' => $user->id,
                'points' => $initialPoints,
                'action' => 'admin_bonus',
                'description' => 'Welcome onboarding bonus points from Admin',
            ]);
        }

        // Welcome notification
        AppNotification::create([
            'user_id' => $user->id,
            'actor_id' => $request->user()->id,
            'type' => 'welcome',
            'title' => 'Welcome to GrowHub!',
            'message' => 'Your employee account is ready. Explore knowledge feeds and complete learning modules to earn points!',
            'link' => '/dashboard',
        ]);

        return response()->json([
            'message' => "User '{$user->name}' (NIP: {$user->nip}) created successfully.",
            'user' => $user,
        ], 201);
    }

    /**
     * Update an existing user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:4'],
            'role' => ['required', Rule::in(['employee', 'reviewer', 'admin'])],
            'department' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'points' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'must_change_password' => ['nullable', 'boolean'],
            'bio' => ['nullable', 'string', 'max:500'],
        ], [
            'email.unique' => 'Email address is already in use.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'role' => $validated['role'],
            'department' => $validated['department'] ?? $user->department,
            'position' => $validated['position'] ?? $user->position,
            'location' => $validated['location'] ?? $user->location,
            'bio' => $validated['bio'] ?? $user->bio,
        ];

        if (isset($validated['is_active'])) {
            $updateData['is_active'] = (bool) $validated['is_active'];
        }

        if (isset($validated['points'])) {
            $updateData['points'] = (int) $validated['points'];
        }

        if (isset($validated['must_change_password'])) {
            $updateData['must_change_password'] = (bool) $validated['must_change_password'];
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
            // When admin edits/resets password, require user to change it on their next login
            $updateData['must_change_password'] = true;
        }

        $user->update($updateData);

        return response()->json([
            'message' => "User '{$user->name}' (NIP: {$user->nip}) updated successfully.",
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Reset user password to default NIP and require password change.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $newPassword = $user->nip;
        $user->update([
            'password' => Hash::make($newPassword),
            'must_change_password' => true,
        ]);

        return response()->json([
            'message' => "Password user '{$user->name}' (NIP: {$user->nip}) berhasil direset ke default (NIP). User wajib mengganti password saat login berikutnya.",
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Delete an existing user.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        // Prevent deleting own account
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot delete your own account while logged in.',
            ], 422);
        }

        $name = $user->name;
        $nip = $user->nip;

        $user->delete();

        return response()->json([
            'message' => "User '{$name}' (NIP: {$nip}) deleted successfully.",
        ]);
    }
}
