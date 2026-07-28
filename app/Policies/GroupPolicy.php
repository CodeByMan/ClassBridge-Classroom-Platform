<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Group $group): bool
    {
        return $group->public || $group->users()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->groups()->where('groups.user_id', $user->id)->count() < 20;
    }

    public function join(User $user, Group $group): bool
    {
        return $group->public && ! $group->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Group $group): bool
    {
        return $user->id === $group->user_id;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->id === $group->user_id;
    }

    public function restore(User $user, Group $group): bool
    {
        return $user->id === $group->user_id;
    }

    public function forceDelete(User $user, Group $group): bool
    {
        return $user->id === $group->user_id;
    }
}
