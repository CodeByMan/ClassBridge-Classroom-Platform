@if (isset($posts))
    <div class="gc-assignment-list">
        @forelse ($posts as $post)
            @php
                $currentSubmission = $post->postusers->firstWhere('user_id', auth()->id());
                $isLateSubmission = $currentSubmission?->finished && $post->deadline && $currentSubmission->updated_at->gt($post->deadline);
            @endphp
            <a class="gc-assignment-card" href="{{ route('group.post.show', [$post->group, $post]) }}">
                <div class="gc-assignment-card__inner">
                    <span class="gc-assignment-icon {{ $post->type === \App\Enums\PostType::Announcement ? 'gc-assignment-icon--yellow' : 'gc-assignment-icon--blue' }}" aria-hidden="true">
                        <i class="{{ $post->type === \App\Enums\PostType::Announcement ? 'fa-solid fa-bullhorn' : 'fa-solid fa-clipboard-list' }}"></i>
                    </span>
                    <div class="min-w-0">
                        <h3 class="gc-assignment-title truncate">{{ $post->name }}</h3>
                        <div class="gc-assignment-meta">
                            <span>{{ $post->group->name }}</span><span>•</span>
                            @if ($post->deadline)
                                <span>Due {{ $post->deadline->format('M j, Y \a\t H:i') }}</span>
                            @else
                                <span>No due date</span>
                            @endif
                        </div>
                    </div>
                    <div data-testid="assignment-status-{{ $post->id }}">
                        @if ($post->user_id === auth()->id() || $post->type === \App\Enums\PostType::Announcement)
                            <span class="gc-status gc-status--info"><i class="fa-solid fa-circle-info"></i>Posted</span>
                        @elseif (! $currentSubmission || ! $currentSubmission->finished)
                            <span class="gc-status gc-status--todo"><i class="fa-regular fa-circle"></i>Assigned</span>
                        @elseif ($isLateSubmission)
                            <span class="gc-status gc-status--late"><i class="fa-solid fa-clock"></i>Turned in late</span>
                        @else
                            <span class="gc-status gc-status--done"><i class="fa-solid fa-check"></i>{{ $currentSubmission?->grade !== null ? 'Graded '.$currentSubmission->grade.'/'.$post->total_marks : 'Turned in' }}</span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="gc-empty"><i class="fa-regular fa-clipboard"></i><p>No assignments match the selected filters.</p></div>
        @endforelse
    </div>

    @if ($posts->hasPages())
        <div class="mt-7" id="pagination">{{ $posts->onEachSide(1)->links() }}</div>
    @endif
@endif
