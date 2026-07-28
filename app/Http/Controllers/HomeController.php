<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user()->load('groups');
        $userId = $user->id;

        $posts = Post::query()
            ->with([
                'group:id,name,public',
                'postusers' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->whereHas('group.users', fn ($query) => $query->whereKey($userId))
            ->orderByRaw('deadline IS NULL')
            ->orderBy('deadline')
            ->paginate(6);

        $discoverableGroups = Group::query()
            ->where('public', true)
            ->whereDoesntHave('users', fn ($query) => $query->whereKey($userId))
            ->latest()
            ->limit(10)
            ->get();

        return view('app.home.index', [
            'user' => $user,
            'groups' => $user->groups,
            'group_count' => $user->groups->count(),
            'posts' => $posts,
            'discoverableGroups' => $discoverableGroups,
        ]);
    }
}
