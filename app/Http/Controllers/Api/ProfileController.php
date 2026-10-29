<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    /** Profil publik user lain / diri sendiri */
    public function show(Request $request, User $user)
    {
        $published = $user->contents()->published();

        return response()->json([
            'user' => $user->only(['id', 'name', 'nip', 'department', 'position', 'location', 'bio', 'points', 'current_streak', 'longest_streak', 'avatar_url', 'level_info', 'role', 'created_at']),
            'rank' => User::where('is_active', true)->where('points', '>', $user->points)->count() + 1,
            'stats' => [
                'contents' => (clone $published)->count(),
                'likes' => (int) (clone $published)->sum('likes_count'),
                'comments' => (int) (clone $published)->sum('comments_count'),
                'views' => (int) (clone $published)->sum('views_count'),
                'communities' => $user->communities()->count(),
                'courses' => $user->completedCourses()->count(),
            ],
            'contents' => Content::published()->where('user_id', $user->id)
                ->with([ContentController::AUTHOR_COLUMNS, 'category', 'media'])
                ->withViewerState($request->user())
                ->latest('published_at')->limit(12)->get(),
            'communities' => $user->communities()->select('communities.id', 'name', 'slug', 'icon', 'color')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email,'.$user->id],
            'bio' => ['nullable', 'string', 'max:300'],
            'location' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($data);

        return response()->json(['message' => 'Profil diperbarui.', 'user' => $user->fresh()]);
    }

    public function avatar(Request $request)
    {
        $request->validate(['avatar' => ['required', 'image', 'max:3072']]);
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }
        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

        return response()->json(['message' => 'Foto profil diperbarui.', 'user' => $user->fresh()]);
    }

    public function password(Request $request)
    {
        $user = $request->user();

        // Self-service password change is restricted: only admin can freely change, or users required to change
        if ($user->role !== 'admin' && ! $user->must_change_password) {
            return response()->json([
                'message' => 'Reset password mandiri dinonaktifkan. Password hanya dapat direset oleh Administrator.',
            ], 403);
        }

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Password saat ini salah.']);
        }

        $user->update([
            'password' => $data['password'],
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Password berhasil diubah.']);
    }
}
