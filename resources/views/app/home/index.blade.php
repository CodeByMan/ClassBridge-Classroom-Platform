@php
    $visiblePosts = collect($posts->items());
    $upcomingDeadlines = $visiblePosts
        ->filter(fn ($item) => $item->type !== \App\Enums\PostType::Announcement && $item->deadline && $item->deadline->isFuture())
        ->sortBy('deadline');
    $dueSoonCount = $upcomingDeadlines->filter(fn ($item) => $item->deadline->lte(now()->addDays(7)))->count();
@endphp
<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="gc-page-title">Classes</h1>
            <p class="gc-page-subtitle">Your enrolled groups and current classwork</p>
        </div>
        <div class="gc-toolbar">
            <div class="gc-field">
                <label for="groups">Visibility</label>
                <select id="groups" name="groups" class="gc-select">
                    <option value="1" selected>All groups</option>
                    <option value="2">Private only</option>
                    <option value="3">Public only</option>
                </select>
            </div>
            <div class="gc-field">
                <label for="sort">Sort classwork</label>
                <select id="sort" name="sort" class="gc-select">
                    <option value="1">Oldest first</option>
                    <option value="2">Newest first</option>
                    <option value="3">Latest deadline</option>
                    <option value="4" selected>Nearest deadline</option>
                    <option value="5">Title A–Z</option>
                    <option value="6">Title Z–A</option>
                </select>
            </div>
            <div class="gc-field">
                <label for="search">Search classwork</label>
                <div class="gc-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="search" name="search" maxlength="100" class="gc-input" placeholder="Search">
                </div>
            </div>
        </div>
    </x-slot>

    <div class="gc-page">
        <section class="gc-dashboard-summary" aria-label="Classroom summary">
            <article class="gc-summary-card gc-summary-card--blue">
                <span class="gc-summary-card__icon"><i class="fa-solid fa-school"></i></span>
                <div><strong>{{ $group_count }}</strong><span>{{ Str::plural('enrolled class', $group_count) }}</span></div>
            </article>
            <article class="gc-summary-card gc-summary-card--yellow">
                <span class="gc-summary-card__icon"><i class="fa-regular fa-calendar"></i></span>
                <div><strong>{{ $dueSoonCount }}</strong><span>due in the next 7 days</span></div>
            </article>
            <article class="gc-summary-card gc-summary-card--green">
                <span class="gc-summary-card__icon"><i class="fa-solid fa-list-check"></i></span>
                <div><strong>{{ $posts->total() }}</strong><span>{{ Str::plural('classwork item', $posts->total()) }}</span></div>
            </article>
        </section>

        <section class="gc-section" aria-labelledby="your-classes-heading">
            <div class="gc-section-heading">
                <div>
                    <h2 id="your-classes-heading">Your classes</h2>
                    <p>Open a class to view its assignments, deadlines and members.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="gc-button gc-button--outline gc-button--compact" data-dialog-open="group-join-dialog">
                        <i class="fa-solid fa-right-to-bracket"></i>Join class
                    </button>
                    <button type="button" class="gc-button gc-button--primary gc-button--compact" data-dialog-open="group-create-dialog">
                        <i class="fa-solid fa-plus"></i>Create class
                    </button>
                </div>
            </div>

            @forelse ($groups as $navigationGroup)
                @if ($loop->first)<div class="gc-class-grid">@endif
                <article class="gc-class-card gc-class-card--{{ (($loop->index % 4) + 1) }}">
                    <a href="{{ route('group.show', $navigationGroup) }}" class="gc-class-card__banner">
                        <div class="gc-class-card__name">{{ $navigationGroup->name }}</div>
                        <div class="gc-class-card__meta">{{ $navigationGroup->public ? 'Public class' : 'Private class' }}</div>
                        <span class="gc-class-card__letter" aria-hidden="true">{{ mb_strtoupper(mb_substr($navigationGroup->name, 0, 1)) }}</span>
                    </a>
                    <div class="gc-class-card__body">
                        <p>View classwork, deadlines, submissions and class discussions.</p>
                    </div>
                    <div class="gc-class-card__footer">
                        <a href="{{ route('group.user.index', $navigationGroup) }}" class="gc-icon-button" aria-label="View {{ $navigationGroup->name }} members">
                            <i class="fa-solid fa-users"></i>
                        </a>
                        <a href="{{ route('group.show', $navigationGroup) }}" class="gc-icon-button" aria-label="Open {{ $navigationGroup->name }}">
                            <i class="fa-regular fa-folder-open"></i>
                        </a>
                    </div>
                </article>
                @if ($loop->last)</div>@endif
            @empty
                <div class="gc-empty">
                    <i class="fa-solid fa-people-group" aria-hidden="true"></i>
                    <h2 class="text-lg font-medium text-gray-800">No classes yet</h2>
                    <p class="mt-1">Create your first class or join one using an invite code.</p>
                    <div class="mt-5 flex flex-wrap justify-center gap-2">
                        <button type="button" class="gc-button gc-button--outline" data-dialog-open="group-join-dialog">Join class</button>
                        <button type="button" class="gc-button gc-button--primary" data-dialog-open="group-create-dialog">Create class</button>
                    </div>
                </div>
            @endforelse
        </section>

        @if ($discoverableGroups->isNotEmpty())
            <section class="gc-section" aria-labelledby="discover-public-groups">
                <div class="gc-section-heading">
                    <div>
                        <h2 id="discover-public-groups">Discover public classes</h2>
                        <p>Join an open class without an invite code.</p>
                    </div>
                </div>
                <div class="gc-card">
                    @foreach ($discoverableGroups as $publicGroup)
                        <div class="gc-person">
                            <span class="gc-nav-item__class-dot" aria-hidden="true">{{ mb_strtoupper(mb_substr($publicGroup->name, 0, 1)) }}</span>
                            <a href="{{ route('group.show', $publicGroup) }}" class="min-w-0 flex-1 font-medium truncate">{{ $publicGroup->name }}</a>
                            <form method="POST" action="{{ route('group.join-public', $publicGroup) }}" data-working-form>
                                @csrf
                                <button type="submit" class="gc-button gc-button--outline gc-button--compact" data-submit-label="Joining…">Join</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="gc-section" aria-labelledby="upcoming-heading">
            <div class="gc-section-heading">
                <div>
                    <h2 id="upcoming-heading">Upcoming deadlines</h2>
                    <p>Nearest dated assignments from the currently loaded classwork.</p>
                </div>
            </div>

            <div class="gc-upcoming-grid">
                @forelse ($upcomingDeadlines->take(4) as $upcomingPost)
                    <a href="{{ route('group.post.show', [$upcomingPost->group, $upcomingPost]) }}" class="gc-upcoming-card">
                        <span class="gc-upcoming-card__date">
                            <strong>{{ $upcomingPost->deadline->format('d') }}</strong>
                            <small>{{ $upcomingPost->deadline->format('M') }}</small>
                        </span>
                        <span class="min-w-0">
                            <strong class="block truncate">{{ $upcomingPost->name }}</strong>
                            <small class="block mt-1 text-gray-500 truncate">{{ $upcomingPost->group->name }} · {{ $upcomingPost->deadline->format('H:i') }}</small>
                        </span>
                    </a>
                @empty
                    <div class="gc-empty gc-empty--compact"><i class="fa-regular fa-calendar-check"></i><p>No upcoming deadlines in the current results.</p></div>
                @endforelse
            </div>
        </section>

        <section class="gc-section" aria-labelledby="classwork-heading">
            <div class="gc-section-heading">
                <div>
                    <h2 id="classwork-heading">All classwork</h2>
                    <p>Assignments and announcements from all enrolled classes. Filters update this list dynamically.</p>
                </div>
            </div>
            <p id="content-filter-error" class="gc-alert gc-alert--error gc-alert--inline" role="alert" hidden></p>
            <div id="orderContent" data-content-filter data-url="{{ route('sort') }}">
                @include('app.post.index')
            </div>
            <div class="mt-12 text-center" id="loadingImage" hidden aria-live="polite">
                <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
                <span class="sr-only">Loading assignments</span>
            </div>
        </section>
    </div>
</x-app-layout>
