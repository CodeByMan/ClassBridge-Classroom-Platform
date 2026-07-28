<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Group::class, 'group');
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $group = DB::transaction(function () use ($request, $validated): Group {
            $group = new Group([
                'name' => $validated['name'],
                'public' => $validated['public'],
            ]);
            $group->user()->associate($request->user());
            $group->invite_key = Str::random(10);
            $group->save();
            $group->users()->syncWithoutDetaching([$request->user()->id]);

            return $group;
        });

        return redirect()->route('group.show', $group)
            ->with('success', 'Class created successfully.');
    }

    public function show(Group $group): View
    {
        $currentUserId = request()->user()->id;
        $user = request()->user()->load('groups')->loadCount('groups');
        $posts = $group->posts()
            ->with([
                'group:id,name,public',
                'postusers' => fn ($query) => $query->where('user_id', $currentUserId),
            ])
            ->latest()
            ->paginate(6);

        return view('app.group.index', [
            'group' => $group,
            'user' => $user,
            'posts' => $posts,
            'isMember' => $group->users()->whereKey($currentUserId)->exists(),
        ]);
    }

    public function destroy(Group $group): RedirectResponse
    {
        $paths = DB::table('post_user')
            ->join('posts', 'posts.id', '=', 'post_user.post_id')
            ->where('posts.group_id', $group->id)
            ->whereNotNull('post_user.submission_path')
            ->pluck('post_user.submission_path')
            ->all();

        $group->delete();

        if ($paths !== []) {
            Storage::disk('submissions')->delete($paths);
        }

        return redirect()->route('home')->with('success', 'Class deleted successfully.');
    }
}
