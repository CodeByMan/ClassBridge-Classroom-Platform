<?php

namespace App\Livewire;

use App\Models\Post;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

class DestroyPost extends ModalComponent
{
    public Post $post;

    public bool $showDiv = false;

    public function mount(Post $post): void
    {
        Gate::authorize('delete', $post);
        $this->post = $post;
        $this->showDiv = $post->deadline !== null;
    }

    public function render()
    {
        return view('livewire.destroy-post');
    }

    public function toggleDeadline(): void
    {
        $this->showDiv = ! $this->showDiv;
    }
}
