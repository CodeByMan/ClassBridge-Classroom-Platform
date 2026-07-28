<div class="gc-modal">
    <form action="{{ route('user.bio.update', $user->id) }}" method="POST">
        @method('PUT')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Edit profile bio</h2><p class="mt-1 text-sm text-gray-500">Add a short introduction visible on your profile.</p></div></div>
        <div class="gc-modal__body">
            <div class="gc-field"><x-input-label for="edit-profile-bio" :value="__('Bio')" /><textarea id="edit-profile-bio" name="bio" class="gc-textarea" required maxlength="500">{{ old('bio', $user->bio) }}</textarea><x-input-error :messages="$errors->get('bio')" /></div>
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--primary">Save</button></div>
        </div>
    </form>
</div>
