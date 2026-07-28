<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $currentUser): bool
    {
        return true;
    }

    public function view(User $currentUser, User $user): bool
    {
        if ($currentUser->is($user)) {
            return true;
        }

        return $currentUser->groups()
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->exists();
    }

    public function create(User $currentUser): bool
    {
        return true;
    }

    public function update(User $currentUser, User $user): bool
    {
        return $currentUser->is($user);
    }

    public function delete(User $currentUser, User $user): bool
    {
        return $currentUser->is($user);
    }

    public function restore(User $currentUser, User $user): bool
    {
        return $currentUser->is($user);
    }

    public function forceDelete(User $currentUser, User $user): bool
    {
        return $currentUser->is($user);
    }
}
