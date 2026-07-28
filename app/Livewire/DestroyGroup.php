<?php

namespace App\Livewire;

use App\Models\Group;
use Illuminate\Support\Facades\Gate;
use LivewireUI\Modal\ModalComponent;

class DestroyGroup extends ModalComponent
{
    public Group $group;

    public function mount(Group $group): void
    {
        Gate::authorize('delete', $group);
        $this->group = $group;
    }

    public function render()
    {
        return view('livewire.destroy-group');
    }
}
