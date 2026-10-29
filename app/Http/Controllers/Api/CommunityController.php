<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Community;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    public function categories()
    {
        return Category::withCount(['contents' => fn ($q) => $q->published()])->orderBy('sort_order')->get();
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $q = Community::withCount(['members', 'contents' => fn ($c) => $c->published()])
            ->withExists(['members as is_member' => fn ($m) => $m->where('user_id', $user->id)])
            ->with(['members' => fn ($m) => $m->select('users.id', 'users.name', 'users.avatar')->limit(5)]);

        if ($s = trim((string) $request->query('q'))) {
            $q->where('name', 'ilike', "%$s%");
        }
        if ($request->boolean('joined')) {
            $q->whereHas('members', fn ($m) => $m->where('user_id', $user->id));
        }

        return $q->orderByDesc('members_count')->get();
    }

    public function show(Request $request, Community $community)
    {
        $user = $request->user();

        $community->loadCount(['members', 'contents' => fn ($c) => $c->published()]);
        $community->loadExists(['members as is_member' => fn ($m) => $m->where('user_id', $user->id)]);
        $community->load('creator:id,name');

        $members = $community->members()
            ->select('users.id', 'users.name', 'users.nip', 'users.avatar', 'users.department', 'users.points')
            ->orderByDesc('users.points')->limit(10)->get();

        return response()->json(['community' => $community, 'top_members' => $members]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:communities,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:40'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);
        $user = $request->user();

        $community = Community::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'icon' => $data['icon'] ?? 'Users',
            'color' => $data['color'] ?? '#1e8c45',
            'created_by' => $user->id,
        ]);
        $community->members()->attach($user->id, ['role' => 'admin']);

        return response()->json($community, 201);
    }

    public function toggleJoin(Request $request, Community $community)
    {
        $user = $request->user();
        $isMember = $community->members()->where('user_id', $user->id)->exists();

        $isMember ? $community->members()->detach($user->id) : $community->members()->attach($user->id);

        return response()->json([
            'is_member' => ! $isMember,
            'members_count' => $community->members()->count(),
        ]);
    }
}
