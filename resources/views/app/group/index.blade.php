@if (isset($group))
@php
    $upcomingPosts = $posts->getCollection()
        ->filter(fn ($item) => $item->type !== \App\Enums\PostType::Announcement && $item->deadline && $item->deadline->isFuture())
        ->sortBy('deadline')
        ->take(3);
    $classworkHasErrors = old('form_context') === 'classwork';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="gc-page-title">{{ $group->name }}</h1>
            <p class="gc-page-subtitle">{{ $group->public ? 'Public class' : 'Private class' }} · {{ $posts->total() }} {{ Str::plural('post', $posts->total()) }}</p>
        </div>
        <div class="gc-toolbar">
            <div class="gc-field">
                <label for="sort">Sort classwork</label>
                <select id="sort" name="sort" class="gc-select">
                    <option value="1">Oldest first</option>
                    <option value="2" selected>Newest first</option>
                    <option value="3">Latest deadline</option>
                    <option value="4">Nearest deadline</option>
                    <option value="5">Title A–Z</option>
                    <option value="6">Title Z–A</option>
                </select>
            </div>
            <div class="gc-field">
                <label for="search">Search classwork</label>
                <div class="gc-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="search" name="search" class="gc-input" placeholder="Search" maxlength="100">
                </div>
            </div>
        </div>
    </x-slot>

    <div class="gc-page">
        <section class="gc-class-hero" aria-labelledby="class-title">
            <div class="gc-class-hero__content">
                <p class="gc-class-hero__eyebrow">{{ $group->public ? 'Public class' : 'Private class' }}</p>
                <h1 id="class-title">{{ $group->name }}</h1>
                <p>{{ $isMember ? 'You are enrolled in this class.' : 'Preview this public class, then join to participate.' }}</p>
            </div>

            @if (! $group->public)
                @can('update', $group)
                    <button type="button" class="gc-class-code text-left" data-show-invite>
                        <small>Class code</small>
                        <strong>{{ $group->invite_key }}</strong>
                    </button>
                @endcan
            @endif
        </section>

        <nav class="gc-tabs" aria-label="Class sections">
            <a href="{{ route('group.show', $group) }}" class="gc-tab is-active">Stream</a>
            <a href="#classwork" class="gc-tab">Classwork</a>
            <a href="{{ route('group.user.index', $group) }}" class="gc-tab">People</a>
            @can('delete', $group)
                <form method="POST" action="{{ route('group.destroy', $group) }}" class="ml-auto"
                    data-confirm-form="Delete {{ $group->name }}? This permanently removes its classwork, comments, memberships, and uploaded submissions.">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="gc-tab text-red-700"><i class="fa-regular fa-trash-can" aria-hidden="true"></i> Delete class</button>
                </form>
            @endcan
        </nav>

        @unless ($isMember)
            <div class="gc-alert gc-alert--warning mt-6">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span class="flex-1">This public class is visible to you. Join it to submit work or comments.</span>
                <form method="POST" action="{{ route('group.join-public', $group) }}" data-working-form>
                    @csrf
                    <button type="submit" class="gc-button gc-button--primary gc-button--compact" data-submit-label="Joining…">Join class</button>
                </form>
            </div>
        @endunless

        <div class="gc-stream-layout">
            <aside class="gc-stream-aside">
                <section class="gc-card gc-card__body">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="gc-card__title text-base">Upcoming</h2>
                        <i class="fa-regular fa-calendar text-blue-600" aria-hidden="true"></i>
                    </div>

                    @forelse ($upcomingPosts as $upcomingPost)
                        <a href="{{ route('group.post.show', [$group, $upcomingPost]) }}" class="gc-upcoming-item">
                            <strong>{{ $upcomingPost->name }}</strong>
                            <span>Due {{ $upcomingPost->deadline->format('M j, H:i') }}</span>
                        </a>
                    @empty
                        <p class="gc-card__subtitle mt-3">No upcoming deadlines in the currently loaded classwork.</p>
                    @endforelse

                    <a href="#classwork" class="gc-button gc-button--text gc-button--compact mt-3">View all classwork</a>
                </section>

                @if (! $group->public)
                    @can('update', $group)
                        <section class="gc-card gc-card__body">
                            <h2 class="gc-card__title text-base">Class code</h2>
                            <p class="gc-card__subtitle mt-1">Share this code with students.</p>
                            <button type="button" class="mt-3 text-2xl tracking-widest text-blue-700 font-medium" data-show-invite>
                                {{ $group->invite_key }}
                            </button>
                        </section>
                    @endcan
                @endif
            </aside>

            <div id="classwork">
                @can('delete', $group)
                    <section class="gc-card gc-create-panel mb-5">
                        <button type="button" data-collapse-trigger="classwork-create-form"
                            class="gc-create-panel__trigger" aria-expanded="{{ $classworkHasErrors ? 'true' : 'false' }}"
                            aria-controls="classwork-create-form">
                            <span class="gc-create-panel__icon"><i class="fa-solid fa-plus"></i></span>
                            <span>
                                <strong class="block text-gray-800">Create classwork</strong>
                                <small>Post an announcement or assignment</small>
                            </span>
                            <i class="fa-solid {{ $classworkHasErrors ? 'fa-chevron-up' : 'fa-chevron-down' }} ml-auto" data-collapse-icon></i>
                        </button>

                        <div id="classwork-create-form" class="gc-create-form"
                            @if (! $classworkHasErrors) hidden @else data-collapse-open-on-load @endif>
                            <form action="{{ route('group.post.store', $group) }}" method="POST" data-working-form>
                                @csrf
                                <input type="hidden" name="form_context" value="classwork">

                                <div class="gc-form-grid">
                                    <div class="grid content-start gap-4">
                                        <div class="gc-field">
                                            <label for="assignment-name">Title</label>
                                            <input type="text" id="assignment-name" name="name" class="gc-input"
                                                value="{{ $classworkHasErrors ? old('name') : '' }}"
                                                placeholder="Assignment title" required maxlength="32">
                                            @if ($classworkHasErrors && $errors->has('name'))
                                                <p class="gc-field-error">{{ $errors->first('name') }}</p>
                                            @endif
                                        </div>

                                        <div class="gc-field">
                                            <label for="assignment-content">Instructions</label>
                                            <textarea name="content" id="assignment-content" class="gc-textarea"
                                                placeholder="Assignment details" maxlength="1024">{{ $classworkHasErrors ? old('content') : '' }}</textarea>
                                            @if ($classworkHasErrors && $errors->has('content'))
                                                <p class="gc-field-error">{{ $errors->first('content') }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="grid content-start gap-4">
                                        <div class="gc-field">
                                            <label for="assignment-type">Type</label>
                                            <select required name="type" id="assignment-type" class="gc-select">
                                                <option value="" disabled @selected(! $classworkHasErrors || old('type') === null)>Choose type</option>
                                                <option value="{{ \App\Enums\PostType::Announcement->value }}" @selected($classworkHasErrors && (int) old('type') === \App\Enums\PostType::Announcement->value)>Announcement</option>
                                                <option value="{{ \App\Enums\PostType::Completion->value }}" @selected($classworkHasErrors && (int) old('type') === \App\Enums\PostType::Completion->value)>Completion checkbox</option>
                                                <option value="{{ \App\Enums\PostType::TextAnswer->value }}" @selected($classworkHasErrors && (int) old('type') === \App\Enums\PostType::TextAnswer->value)>Text answer</option>
                                                <option value="{{ \App\Enums\PostType::FileUpload->value }}" @selected($classworkHasErrors && (int) old('type') === \App\Enums\PostType::FileUpload->value)>ZIP file submission</option>
                                            </select>
                                            @if ($classworkHasErrors && $errors->has('type'))
                                                <p class="gc-field-error">{{ $errors->first('type') }}</p>
                                            @endif
                                        </div>

                                        <div class="gc-field">
                                            <label for="assignment-total-marks">Total marks <span class="text-gray-500 font-normal">(ZIP assignments)</span></label>
                                            <input type="number" id="assignment-total-marks" name="total_marks" class="gc-input"
                                                value="{{ $classworkHasErrors ? old('total_marks', 100) : 100 }}" min="1" max="1000" inputmode="numeric">
                                            @if ($classworkHasErrors && $errors->has('total_marks'))
                                                <p class="gc-field-error">{{ $errors->first('total_marks') }}</p>
                                            @endif
                                        </div>

                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" id="assignment-deadline-switcher" data-deadline-toggle
                                                data-target="assignment-deadline"
                                                @checked($classworkHasErrors && old('deadline'))
                                                class="w-4 h-4 rounded border-gray-300 text-blue-600">
                                            <span>Include a due date</span>
                                        </label>

                                        <div class="gc-field">
                                            <label class="sr-only" for="assignment-deadline">Due date</label>
                                            <input type="datetime-local" id="assignment-deadline" name="deadline"
                                                value="{{ $classworkHasErrors ? old('deadline') : '' }}"
                                                class="gc-input" @if (! ($classworkHasErrors && old('deadline'))) hidden @endif>
                                            @if ($classworkHasErrors && $errors->has('deadline'))
                                                <p class="gc-field-error">{{ $errors->first('deadline') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="gc-form-actions">
                                    <button type="button" class="gc-button gc-button--text" data-collapse-trigger="classwork-create-form">Cancel</button>
                                    <button type="submit" class="gc-button gc-button--primary" data-submit-label="Posting…">Post classwork</button>
                                </div>
                            </form>
                        </div>
                    </section>
                @endcan

                <div class="gc-section-heading gc-section-heading--compact">
                    <div>
                        <h2>Classwork</h2>
                        <p>Announcements and assignments loaded from the class database.</p>
                    </div>
                    <span class="gc-status gc-status--info">{{ $posts->total() }} total</span>
                </div>

                <p id="content-filter-error" class="gc-alert gc-alert--error gc-alert--inline" role="alert" hidden></p>
                <div id="orderContent" data-content-filter data-url="{{ route('sort') }}" data-group-id="{{ $group->id }}">
                    @include('app.post.index')
                </div>
                <div class="mt-12 text-center" id="loadingImage" hidden aria-live="polite">
                    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
                    <span class="sr-only">Loading assignments</span>
                </div>
            </div>
        </div>
    </div>

    @if (! $group->public)
        @can('update', $group)
            <div id="toast-bottom-right" class="gc-toast" role="alert" style="visibility:hidden;opacity:0;transition:opacity .2s ease">
                <div class="flex items-center gap-3">
                    <div class="flex-1">
                        <small class="block opacity-75">Class code</small>
                        <strong class="text-lg tracking-widest">{{ $group->invite_key }}</strong>
                    </div>
                    <button type="button" class="gc-icon-button text-white" data-hide-invite aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        @endcan
    @endif
</x-app-layout>
@endif
