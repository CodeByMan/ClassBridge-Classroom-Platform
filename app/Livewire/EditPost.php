<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

class EditPost extends ModalComponent
{
    public Post $post;

    public bool $showDiv = false;

    public bool $hasSubmissions = false;

    public function mount(Post $post): void
    {
        Gate::authorize('update', $post);
        $this->post = $post;
        $this->showDiv = $post->deadline !== null;
        $this->hasSubmissions = $post->postusers()->exists();
    }

    public function render()
    {
        return view('livewire.edit-post');
    }

    public function toggleDeadline(): void
    {
        $this->showDiv = ! $this->showDiv;
    }
}
