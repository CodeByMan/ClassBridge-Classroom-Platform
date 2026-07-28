<?php

namespace App\Livewire;

use App\Models\Comment;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

class DestroyComment extends ModalComponent
{
    public Comment $comment;

    public function mount(Comment $comment): void
    {
        Gate::authorize('delete', $comment);
        $this->comment = $comment;
    }

    public function render()
    {
        return view('livewire.destroy-comment');
    }
}
