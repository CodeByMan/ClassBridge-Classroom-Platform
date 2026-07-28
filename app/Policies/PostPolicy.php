<?php

namespace App\Policies;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->group->public
            || $post->group->users()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function store(User $user, Group $group): bool
    {
        return $user->id === $group->user_id;
    }

    public function finish(User $user, Post $post): bool
    {
        return $user->id !== $post->user_id
            && in_array($post->type, [PostType::Completion, PostType::TextAnswer], true)
            && $post->group->users()->whereKey($user->id)->exists();
    }

    public function submitFile(User $user, Post $post): bool
    {
        return $user->id !== $post->user_id
            && $post->type === PostType::FileUpload
            && ($post->deadline === null || now()->lessThanOrEqualTo($post->deadline))
            && $post->group->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function restore(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
