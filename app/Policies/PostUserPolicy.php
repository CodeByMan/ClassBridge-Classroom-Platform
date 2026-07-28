<?php

namespace App\Policies;

use App\Enums\PostType;
use App\Models\PostUser;
use App\Models\User;

class PostUserPolicy
{
    public function view(User $user, PostUser $submission): bool
    {
        return $user->id === $submission->user_id
            || $user->id === $submission->post->user_id;
    }

    public function download(User $user, PostUser $submission): bool
    {
        return $submission->submission_path !== null
            && $submission->post->type === PostType::FileUpload
            && $this->view($user, $submission);
    }

    public function grade(User $user, PostUser $submission): bool
    {
        return $submission->post->type === PostType::FileUpload
            && $user->id === $submission->post->user_id;
    }

    public function returnWork(User $user, PostUser $submission): bool
    {
        return $submission->finished
            && $submission->post->type !== PostType::Announcement
            && $user->id === $submission->post->user_id;
    }
}
