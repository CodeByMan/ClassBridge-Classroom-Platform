@if (isset($group))
<x-app-layout>
    <x-slot name="header">
        <div><h1 class="gc-page-title">{{ $group->name }}</h1><p class="gc-page-subtitle">People</p></div>
    </x-slot>

    <div class="gc-page-narrow">
        <nav class="gc-tabs" aria-label="Class sections">
            <a href="{{ route('group.show', $group) }}" class="gc-tab">Stream</a>
            <a href="{{ route('group.show', $group) }}#classwork" class="gc-tab">Classwork</a>
            <a href="{{ route('group.user.index', $group) }}" class="gc-tab is-active">People</a>
        </nav>

        <section class="gc-section">
            <div class="gc-section-heading"><div><h2>Class members</h2><p>People who belong to {{ $group->name }}.</p></div><span class="gc-status gc-status--info">{{ $members->total() }} members</span></div>
            <div class="gc-card gc-people-list">
                @forelse ($members as $member)
                    <a href="{{ route('user.show', $member) }}" class="gc-person">
                        <img src="{{ $member->image ? asset('storage/images/'.$member->image) : asset('storage/images/default.svg') }}" class="gc-avatar" alt="{{ $member->name }} profile image">
                        <span class="font-medium flex-1">{{ $member->name }}</span>
                        <i class="fa-solid fa-chevron-right text-gray-400" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="gc-empty border-0"><p>No members found.</p></div>
                @endforelse
            </div>
            @if ($members->hasPages())<div class="mt-7">{{ $members->links() }}</div>@endif
        </section>
    </div>
</x-app-layout>
@endif
