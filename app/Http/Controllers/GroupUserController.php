<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinGroupRequest;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GroupUserController extends Controller
{
    public function index(Group $group): View
    {
        $this->authorize('view', $group);

        return view('app.group.users', [
            'group' => $group,
            'members' => $group->users()->orderBy('name')->paginate(20),
            'navigationGroups' => request()->user()->groups()->select('groups.id', 'groups.name')->orderBy('name')->get(),
        ]);
    }

    public function store(JoinGroupRequest $request): RedirectResponse
    {
        $group = Group::where('invite_key', $request->validated('invite_key'))->firstOrFail();
        $request->user()->groups()->syncWithoutDetaching([$group->id]);

        return redirect()->route('group.show', $group)
            ->with('success', 'You joined the group successfully.');
    }

    public function joinPublic(Group $group): RedirectResponse
    {
        abort_unless($group->public, 404);
        $this->authorize('join', $group);

        request()->user()->groups()->syncWithoutDetaching([$group->id]);

        return redirect()->route('group.show', $group)
            ->with('success', 'You joined the public group successfully.');
    }
}
