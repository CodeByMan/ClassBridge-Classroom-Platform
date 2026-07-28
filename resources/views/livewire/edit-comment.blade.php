<div class="gc-modal">
    <form action="{{ route('post.comment.update', [$comment->post->id, $comment->id]) }}" method="POST">
        @method('PUT')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Edit comment</h2><p class="mt-1 text-sm text-gray-500">Update your class comment.</p></div></div>
        <div class="gc-modal__body">
            <div class="gc-field"><x-input-label for="edit-comment-content" :value="__('Comment')" /><textarea id="edit-comment-content" name="content" class="gc-textarea" required maxlength="1000">{{ old('content', $comment->content) }}</textarea><x-input-error :messages="$errors->get('content')" /></div>
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--primary">Save</button></div>
        </div>
    </form>
</div>
