<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

class EditUserBio extends ModalComponent
{
    public User $user;

    public function mount(User $user): void
    {
        Gate::authorize('update', $user);
        $this->user = $user;
    }

    public function render()
    {
        return view('livewire.edit-user-bio');
    }
}
