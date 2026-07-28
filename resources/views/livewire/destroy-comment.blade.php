<div class="gc-modal">
    <form action="{{ route('post.comment.destroy', [$comment->post->id, $comment->id]) }}" method="POST">
        @method('DELETE')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Delete comment?</h2><p class="mt-1 text-sm text-gray-500">This action cannot be undone.</p></div></div>
        <div class="gc-modal__body">
            <div class="rounded-md bg-gray-50 border border-gray-200 p-4 text-gray-700">{{ $comment->content }}</div>
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--danger">Delete</button></div>
        </div>
    </form>
</div>
