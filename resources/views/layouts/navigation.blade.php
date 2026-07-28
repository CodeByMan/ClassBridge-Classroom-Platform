@php
    $currentUser = auth()->user();
    $drawerGroups = $currentUser->relationLoaded('groups')
        ? $currentUser->groups
        : $currentUser->groups()->select('groups.id', 'groups.name', 'groups.public')->orderBy('name')->get();
    $pageLabel = isset($group)
        ? $group->name
        : (request()->routeIs('user.show') ? 'Profile' : null);
@endphp

<header class="gc-topbar" aria-label="Application header">
    <div class="gc-topbar__left">
        <button type="button" class="gc-icon-button" data-drawer-toggle aria-expanded="false"
            aria-controls="application-drawer" aria-label="Toggle navigation drawer">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>

        <a href="{{ route('home') }}" class="gc-brand" aria-label="ClassBridge home">
            <x-classbridge-mark />
            <span class="gc-brand__title"><strong>Class</strong><span>Bridge</span></span>
        </a>

        @if ($pageLabel)
            <span class="hidden sm:inline text-gray-300" aria-hidden="true">/</span>
            <span class="hidden sm:inline gc-context-label">{{ $pageLabel }}</span>
        @endif
    </div>

    <div class="gc-topbar__right">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button type="button" class="gc-icon-button" aria-label="Create or join a group">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                </button>
            </x-slot>
            <x-slot name="content">
                <x-dropdown-link as="button" data-dialog-open="group-create-dialog">
                    <span class="flex items-center gap-3"><i class="fa-solid fa-plus fa-fw"></i>Create group</span>
                </x-dropdown-link>
                <x-dropdown-link as="button" data-dialog-open="group-join-dialog">
                    <span class="flex items-center gap-3"><i class="fa-solid fa-right-to-bracket fa-fw"></i>Join group</span>
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>

        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button type="button" class="gc-account-trigger" aria-label="Open account menu for {{ $currentUser->name }}">
                    @if ($currentUser->image)
                        <img class="gc-avatar" src="{{ asset('storage/images/'.$currentUser->image) }}"
                            alt="{{ $currentUser->name }} profile image">
                    @else
                        <span class="gc-avatar-fallback" aria-hidden="true">{{ mb_substr($currentUser->name, 0, 1) }}</span>
                    @endif
                    <span class="gc-account-trigger__identity">
                        <strong>{{ $currentUser->name }}</strong>
                        <small>{{ $currentUser->email }}</small>
                    </span>
                    <i class="fa-solid fa-chevron-down gc-account-trigger__chevron" aria-hidden="true"></i>
                </button>
            </x-slot>
            <x-slot name="content">
                <div class="px-4 py-3 border-b border-gray-200">
                    <p class="font-medium truncate">{{ $currentUser->name }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ $currentUser->email }}</p>
                </div>
                <x-dropdown-link href="{{ route('user.show', $currentUser) }}">
                    <span class="flex items-center gap-3"><i class="fa-regular fa-user fa-fw"></i>Profile</span>
                </x-dropdown-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link as="button" type="submit" class="w-full">
                        <span class="flex items-center gap-3"><i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>Log out</span>
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>

<aside id="application-drawer" class="gc-drawer" aria-label="Primary navigation">
    <div class="gc-drawer__section">
        <a href="{{ route('home') }}" class="gc-nav-item {{ request()->routeIs('home') ? 'is-active' : '' }}">
            <i class="fa-solid fa-house" aria-hidden="true"></i>
            <span class="gc-nav-item__text">Home</span>
        </a>
    </div>

    <div class="gc-drawer__section">
        <div class="gc-drawer__label">Enrolled</div>
        @forelse ($drawerGroups as $navigationGroup)
            <a href="{{ route('group.show', $navigationGroup) }}"
                class="gc-nav-item {{ isset($group) && $group->id === $navigationGroup->id ? 'is-active' : '' }}">
                <span class="gc-nav-item__class-dot" aria-hidden="true">{{ mb_strtoupper(mb_substr($navigationGroup->name, 0, 1)) }}</span>
                <span class="gc-nav-item__text">{{ $navigationGroup->name }}</span>
            </a>
        @empty
            <p class="px-5 py-3 text-sm text-gray-500">No groups yet.</p>
        @endforelse
    </div>

    <div class="gc-drawer__section">
        <button type="button" class="gc-nav-item w-full text-left" data-dialog-open="group-create-dialog">
            <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Create group</span>
        </button>
        <button type="button" class="gc-nav-item w-full text-left" data-dialog-open="group-join-dialog">
            <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i><span>Join group</span>
        </button>
        <a href="{{ route('user.show', $currentUser) }}" class="gc-nav-item">
            <i class="fa-regular fa-user" aria-hidden="true"></i><span>Profile</span>
        </a>
    </div>
</aside>
<div class="gc-drawer-backdrop" data-drawer-backdrop aria-hidden="true"></div>

<div id="group-create-dialog" class="gc-dialog" role="dialog" aria-modal="true"
    aria-labelledby="group-create-title" hidden
    @if (old('form_context') === 'group_create') data-open-on-load @endif>
    <button type="button" class="gc-dialog__backdrop" data-dialog-close aria-label="Close create group dialog"></button>
    <div class="gc-dialog__panel" role="document">
        <div class="gc-modal__header">
            <div>
                <h2 id="group-create-title" class="text-xl font-medium">Create group</h2>
                <p class="mt-1 text-sm text-gray-500">Create a class and become its owner.</p>
            </div>
            <button type="button" class="gc-icon-button" data-dialog-close aria-label="Close create group dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('group.store') }}" method="POST" class="gc-modal__body" data-working-form>
            @csrf
            <input type="hidden" name="form_context" value="group_create">
            <div class="gc-field">
                <label for="create-group-name">Group name</label>
                <input class="gc-input" type="text" id="create-group-name" name="name" required maxlength="100"
                    value="{{ old('form_context') === 'group_create' ? old('name') : '' }}" placeholder="e.g. Web Development">
                @if (old('form_context') === 'group_create' && $errors->has('name'))
                    <p class="gc-field-error">{{ $errors->first('name') }}</p>
                @endif
            </div>

            <label class="mt-5 flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="public" value="0">
                <input type="checkbox" id="create-group-public" name="public" value="1"
                    @checked(old('form_context') === 'group_create' && old('public'))
                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>
                    <strong class="block font-medium">Public group</strong>
                    <small class="text-gray-500">Other users can discover and join it.</small>
                </span>
            </label>

            <div class="gc-modal__actions">
                <button data-dialog-close type="button" class="gc-button gc-button--text">Cancel</button>
                <button type="submit" class="gc-button gc-button--primary" data-submit-label="Creating…">Create group</button>
            </div>
        </form>
    </div>
</div>

<div id="group-join-dialog" class="gc-dialog" role="dialog" aria-modal="true"
    aria-labelledby="group-join-title" hidden
    @if (old('form_context') === 'group_join') data-open-on-load @endif>
    <button type="button" class="gc-dialog__backdrop" data-dialog-close aria-label="Close join group dialog"></button>
    <div class="gc-dialog__panel" role="document">
        <div class="gc-modal__header">
            <div>
                <h2 id="group-join-title" class="text-xl font-medium">Join group</h2>
                <p class="mt-1 text-sm text-gray-500">Enter the ten-character invite code.</p>
            </div>
            <button type="button" class="gc-icon-button" data-dialog-close aria-label="Close join group dialog">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('group.join') }}" method="POST" class="gc-modal__body" data-working-form>
            @csrf
            <input type="hidden" name="form_context" value="group_join">
            <div class="gc-field">
                <label for="join-group-invite-key">Invite code</label>
                <input type="text" id="join-group-invite-key" name="invite_key" class="gc-input gc-code-input"
                    value="{{ old('form_context') === 'group_join' ? old('invite_key') : '' }}" placeholder="Enter code" required minlength="10" maxlength="10"
                    autocomplete="off" autocapitalize="none" spellcheck="false">
                @if (old('form_context') === 'group_join' && $errors->has('invite_key'))
                    <p class="gc-field-error">{{ $errors->first('invite_key') }}</p>
                @endif
            </div>

            <div class="gc-modal__actions">
                <button data-dialog-close type="button" class="gc-button gc-button--text">Cancel</button>
                <button type="submit" class="gc-button gc-button--primary" data-submit-label="Joining…">Join group</button>
            </div>
        </form>
    </div>
</div>
