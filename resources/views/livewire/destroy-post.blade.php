<div class="gc-modal">
    <form action="{{ route('group.post.destroy', [$post->group->id, $post->id]) }}" method="POST">
        @method('DELETE')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Delete assignment?</h2><p class="mt-1 text-sm text-gray-500">Submissions and comments for this item will also be removed.</p></div></div>
        <div class="gc-modal__body">
            <div class="gc-field"><x-input-label for="delete-post-name" :value="__('Title')" /><x-text-input id="delete-post-name" type="text" :value="$post->name" disabled /></div>
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--danger">Delete</button></div>
        </div>
    </form>
</div>
