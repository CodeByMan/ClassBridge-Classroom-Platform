<div class="gc-modal">
    <form action="{{ route('group.post.update', [$post->group->id, $post->id]) }}" method="POST">
        @method('PUT')
        @csrf
        <div class="gc-modal__header"><div><h2 class="text-xl font-medium">Edit classwork</h2><p class="mt-1 text-sm text-gray-500">Update the title, instructions, type, marks, or due date.</p></div></div>
        <div class="gc-modal__body grid gap-4">
            <div class="gc-field"><x-input-label for="edit-post-name" :value="__('Title')" /><x-text-input id="edit-post-name" type="text" name="name" :value="old('name', $post->name)" required maxlength="32" /><x-input-error :messages="$errors->get('name')" /></div>
            <div class="gc-field"><x-input-label for="edit-post-content" :value="__('Instructions')" /><textarea id="edit-post-content" name="content" class="gc-textarea" maxlength="1024">{{ old('content', $post->content) }}</textarea><x-input-error :messages="$errors->get('content')" /></div>
            <div class="gc-field">
                <label for="edit-post-type">Type</label>
                <select required name="type" id="edit-post-type" class="gc-select" {{ $hasSubmissions ? 'disabled' : '' }}>
                    <option value="{{ \App\Enums\PostType::Announcement->value }}" @selected($post->type === \App\Enums\PostType::Announcement)>Announcement</option>
                    <option value="{{ \App\Enums\PostType::Completion->value }}" @selected($post->type === \App\Enums\PostType::Completion)>Completion checkbox</option>
                    <option value="{{ \App\Enums\PostType::TextAnswer->value }}" @selected($post->type === \App\Enums\PostType::TextAnswer)>Text answer</option>
                    <option value="{{ \App\Enums\PostType::FileUpload->value }}" @selected($post->type === \App\Enums\PostType::FileUpload)>ZIP file submission</option>
                </select>
                @if ($hasSubmissions)<input type="hidden" name="type" value="{{ $post->type->value }}"><p class="text-sm text-gray-500">Type is locked because submissions already exist.</p>@endif
                <x-input-error :messages="$errors->get('type')" />
            </div>
            <div class="gc-field">
                <label for="edit-total-marks">Total marks <span class="text-gray-500 font-normal">(ZIP assignments)</span></label>
                <input id="edit-total-marks" type="number" name="total_marks" value="{{ old('total_marks', $post->total_marks ?? 100) }}" min="1" max="1000" class="gc-input" {{ $hasSubmissions && $post->type === \App\Enums\PostType::FileUpload ? 'disabled' : '' }}>
                @if ($hasSubmissions && $post->type === \App\Enums\PostType::FileUpload)
                    <input type="hidden" name="total_marks" value="{{ $post->total_marks }}">
                    <p class="text-sm text-gray-500">Total marks are locked because submissions already exist.</p>
                @endif
                <x-input-error :messages="$errors->get('total_marks')" />
            </div>
            <div><input type="hidden" name="deadline_enabled" value="0"><label class="flex items-center gap-3"><input type="checkbox" id="edit-deadline-switcher" name="deadline_enabled" value="1" wire:click="toggleDeadline" {{ $showDiv ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-blue-600"><span>Include a due date</span></label></div>
            @if ($showDiv)<div class="gc-field"><label for="edit-post-deadline">Due date</label><input type="datetime-local" id="edit-post-deadline" name="deadline" value="{{ old('deadline', $post->deadline?->format('Y-m-d\TH:i')) }}" class="gc-input"></div>@endif
            <div class="gc-modal__actions"><button type="button" class="gc-button gc-button--text" wire:click="closeModal">Cancel</button><button type="submit" class="gc-button gc-button--primary">Save</button></div>
        </div>
    </form>
</div>
