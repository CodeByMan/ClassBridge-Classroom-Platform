<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $viewer = request()->user();
        $canViewPrivateDetails = $viewer->is($user);

        return view('app.user.show', [
            'user' => $user,
            'canViewPrivateDetails' => $canViewPrivateDetails,
            'private_group_count' => $canViewPrivateDetails
                ? $user->groups()->where('public', false)->count()
                : null,
            'finished_post_count' => $user->postusers()->where('finished', true)->count(),
            'navigationGroups' => $viewer->groups()->select('groups.id', 'groups.name')->orderBy('name')->get(),
        ]);
    }
}
