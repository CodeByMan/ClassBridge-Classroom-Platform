<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function store(User $user, Post $post): bool
    {
        return $post->group->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
