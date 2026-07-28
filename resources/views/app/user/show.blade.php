@if (isset($user))
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="gc-page-title">Profile</h1>
            <p class="gc-page-subtitle">Classroom activity and account information</p>
        </div>
    </x-slot>

    <div class="gc-page-narrow">
        <section class="gc-card gc-profile-card">
            <div class="gc-profile-photo-column">
                <img id="profile-image-preview"
                    src="{{ $user->image ? asset('storage/images/'.$user->image) : asset('storage/images/default.svg') }}"
                    class="gc-avatar gc-avatar--lg"
                    alt="{{ $user->name }} profile image">

                @can('update', $user)
                    <form action="{{ route('user.image.update', $user) }}" method="POST" enctype="multipart/form-data"
                        class="gc-profile-photo-form" data-working-form>
                        @csrf
                        @method('PUT')

                        <label for="profile-image-input" class="gc-button gc-button--outline gc-button--compact cursor-pointer">
                            <i class="fa-solid fa-camera"></i>
                            Choose photo
                        </label>
                        <input type="file" name="image" id="profile-image-input"
                            accept="image/jpeg,image/png,image/webp" class="sr-only"
                            data-image-preview-input="profile-image-preview" required>
                        <span class="gc-file-name" data-file-name-for="profile-image-input">JPG, PNG or WebP · max 2 MB</span>

                        @if ($errors->has('image'))
                            <p class="gc-field-error">{{ $errors->first('image') }}</p>
                        @endif

                        <button type="submit" class="gc-button gc-button--primary gc-button--compact"
                            data-submit-label="Uploading…">
                            Upload photo
                        </button>
                    </form>
                @endcan
            </div>

            <div class="min-w-0">
                <h2 class="gc-profile-name">{{ $user->name }}</h2>

                @if ($canViewPrivateDetails)
                    <a href="mailto:{{ $user->email }}" class="mt-1 inline-block text-blue-700">{{ $user->email }}</a>
                @endif

                <div class="gc-stats">
                    <div class="gc-stat">
                        <strong>{{ $finished_post_count }}</strong>
                        {{ Str::plural('completed assignment', $finished_post_count) }}
                    </div>
                    @if ($canViewPrivateDetails)
                        <div class="gc-stat">
                            <strong>{{ $private_group_count }}</strong>
                            {{ Str::plural('private group', $private_group_count) }}
                        </div>
                    @endif
                </div>

                <div class="gc-profile-bio mt-7">
                    <div class="flex items-start gap-3">
                        <i class="fa-regular fa-address-card text-gray-500 mt-1" aria-hidden="true"></i>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-medium text-gray-800">About</h3>
                            <p class="mt-1 text-gray-700 whitespace-pre-line">{{ $user->bio }}</p>
                        </div>
                        @can('update', $user)
                            <button type="button" class="gc-button gc-button--outline gc-button--compact"
                                data-profile-bio-toggle="profile-bio-form" aria-expanded="{{ $errors->has('bio') ? 'true' : 'false' }}">
                                <i class="fa-solid fa-pen"></i>
                                Edit bio
                            </button>
                        @endcan
                    </div>

                    @can('update', $user)
                        <form id="profile-bio-form" action="{{ route('user.bio.update', $user) }}" method="POST"
                            class="gc-profile-edit-form" data-working-form
                            @if (! $errors->has('bio')) hidden @endif>
                            @csrf
                            @method('PUT')

                            <div class="gc-field">
                                <label for="profile-bio">Profile bio</label>
                                <textarea id="profile-bio" name="bio" class="gc-textarea" required maxlength="256"
                                    placeholder="Write a short introduction">{{ old('bio', $user->bio) }}</textarea>
                                <div class="gc-field-help"><span>Visible to people who can view your profile.</span><span>Maximum 256 characters</span></div>
                                @if ($errors->has('bio'))
                                    <p class="gc-field-error">{{ $errors->first('bio') }}</p>
                                @endif
                            </div>

                            <div class="gc-form-actions">
                                <button type="button" class="gc-button gc-button--text"
                                    data-profile-bio-toggle="profile-bio-form">Cancel</button>
                                <button type="submit" class="gc-button gc-button--primary" data-submit-label="Saving…">Save bio</button>
                            </div>
                        </form>
                    @endcan
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
@endif
