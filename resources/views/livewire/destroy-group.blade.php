<div class="gc-modal">
    <form action="{{ route('group.destroy', $group->id) }}" method="POST">
        @method('DELETE')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Delete class?</h2><p class="mt-1 text-sm text-gray-500">This permanently removes the class and its content.</p></div></div>
        <div class="gc-modal__body">
            <div class="gc-field"><x-input-label for="delete-group-name" :value="__('Class name')" /><x-text-input id="delete-group-name" type="text" :value="$group->name" disabled /></div>
            <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-md bg-gray-50 p-4"><strong class="block text-xl">{{ $group->users()->count() }}</strong><span class="text-sm text-gray-500">members</span></div><div class="rounded-md bg-gray-50 p-4"><strong class="block text-xl">{{ $group->posts()->count() }}</strong><span class="text-sm text-gray-500">posts</span></div></div>
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--danger">Delete class</button></div>
        </div>
    </form>
</div>
