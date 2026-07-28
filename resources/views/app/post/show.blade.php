@if (isset($post))
@php
    $isReviewer = Auth::id() === $post->user_id;
    $isAnnouncement = $post->type === \App\Enums\PostType::Announcement;
    $isFileUpload = $post->type === \App\Enums\PostType::FileUpload;
    $submissionWindowOpen = $post->deadline === null || now()->lessThanOrEqualTo($post->deadline);
    $studentWorkState = $submission?->returned_at ? 'Returned' : ($submission?->finished ? 'Turned in' : 'Assigned');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div><h1 class="gc-page-title">{{ $group->name }}</h1><p class="gc-page-subtitle">Classwork details</p></div>
        <div class="flex items-center gap-2">
            <a href="{{ route('group.show', $group) }}" class="gc-button gc-button--text"><i class="fa-solid fa-arrow-left"></i>Back to stream</a>
            @if (! $group->public)
                @can('update', $group)<button type="button" class="gc-button gc-button--outline gc-button--compact" data-show-invite>Class code</button>@endcan
            @endif
        </div>
    </x-slot>

    <div class="gc-page-narrow">
        <nav class="gc-tabs" aria-label="Class sections">
            <a href="{{ route('group.show', $group) }}" class="gc-tab is-active">Stream</a>
            <a href="{{ route('group.show', $group) }}#classwork" class="gc-tab">Classwork</a>
            <a href="{{ route('group.user.index', $group) }}" class="gc-tab">People</a>
        </nav>

        <div class="gc-detail-grid mt-6">
            <div class="grid gap-5">
                <article class="gc-card gc-card__body gc-assignment-detail">
                    <div class="gc-assignment-detail__heading">
                        <span class="gc-assignment-icon {{ $isAnnouncement ? 'gc-assignment-icon--yellow' : 'gc-assignment-icon--blue' }}" aria-hidden="true">
                            <i class="{{ $isAnnouncement ? 'fa-solid fa-bullhorn' : ($isFileUpload ? 'fa-solid fa-file-zipper' : 'fa-solid fa-clipboard-list') }}"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h1 class="gc-assignment-detail__title">{{ $post->name }}</h1>
                                    <p class="gc-assignment-detail__meta">
                                        {{ $post->user->name }} • {{ $post->created_at->format('M j, Y') }}
                                        @if ($post->created_at != $post->updated_at) • Edited {{ $post->updated_at->format('M j, Y') }} @endif
                                    </p>
                                </div>
                                @can('update', $post)
                                    <x-dropdown align="right" width="48">
                                        <x-slot name="trigger"><button type="button" class="gc-icon-button" aria-label="Classwork actions"><i class="fa-solid fa-ellipsis-vertical"></i></button></x-slot>
                                        <x-slot name="content">
                                            <x-dropdown-link as="button" data-livewire-modal="edit-post" data-argument-name="post" data-argument-id="{{ $post->id }}"><span class="flex items-center gap-3"><i class="fa-solid fa-pen fa-fw"></i>Edit</span></x-dropdown-link>
                                            <x-dropdown-link as="button" data-livewire-modal="destroy-post" data-argument-name="post" data-argument-id="{{ $post->id }}"><span class="flex items-center gap-3 text-red-700"><i class="fa-solid fa-trash fa-fw"></i>Delete</span></x-dropdown-link>
                                        </x-slot>
                                    </x-dropdown>
                                @endcan
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="gc-status gc-status--info">
                                    {{ $isAnnouncement ? 'Announcement' : ($isFileUpload ? 'ZIP assignment' : 'Assignment') }}
                                </span>
                                @if ($isFileUpload)
                                    <span class="gc-status gc-status--info"><i class="fa-solid fa-award"></i>{{ $post->total_marks }} marks</span>
                                @endif
                                @if ($post->deadline)
                                    <span class="gc-status {{ now()->greaterThan($post->deadline) ? 'gc-status--late' : 'gc-status--done' }}"><i class="fa-regular fa-clock"></i>Due {{ $post->deadline->format('M j, Y \a\t H:i') }}</span>
                                @else
                                    <span class="gc-status gc-status--info">No due date</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="gc-assignment-detail__content">{{ $post->content }}</div>
                </article>

                @if ($isReviewer && ! $isAnnouncement)
                    <section class="gc-card">
                        <div class="gc-card__header">
                            <div><h2 class="gc-card__title">Student work</h2><p class="gc-card__subtitle">{{ $finished_user_count }} turned in</p></div>
                        </div>
                        <div class="gc-card__body">
                            @forelse ($submissions as $submissionItem)
                                @if ($loop->first)<div class="gc-submission-grid {{ $isFileUpload ? 'gc-submission-grid--files' : '' }}">@endif
                                <article class="gc-submission">
                                    <a href="{{ route('user.show', $submissionItem->user) }}" class="gc-submission__user">
                                        <img src="{{ $submissionItem->user->image ? asset('storage/images/'.$submissionItem->user->image) : asset('storage/images/default.svg') }}" class="gc-avatar gc-avatar--sm" alt="{{ $submissionItem->user->name }} profile image">
                                        <span class="gc-submission__identity"><strong>{{ $submissionItem->user->name }}</strong><small>{{ $submissionItem->user->email }}</small></span>
                                    </a>

                                    @if ($isFileUpload)
                                        <div class="gc-file-submission">
                                            <div class="gc-file-row">
                                                <i class="fa-solid fa-file-zipper text-blue-600" aria-hidden="true"></i>
                                                <span class="gc-file-row__details"><strong class="gc-file-name">{{ $submissionItem->submission_name }}</strong><small>Submitted {{ $submissionItem->updated_at->format('M j, Y H:i') }}</small></span>
                                                <a class="gc-icon-button" href="{{ route('group.post.submission.download', [$group, $post, $submissionItem]) }}" aria-label="Download {{ $submissionItem->user->name }} submission"><i class="fa-solid fa-download"></i></a>
                                            </div>
                                            <div class="gc-submission__status-row">
                                                <span class="gc-status {{ $submissionItem->returned_at ? 'gc-status--done' : 'gc-status--info' }}">
                                                    <i class="fa-solid {{ $submissionItem->returned_at ? 'fa-rotate-left' : 'fa-paper-plane' }}" aria-hidden="true"></i>
                                                    {{ $submissionItem->returned_at ? 'Returned' : 'Turned in' }}
                                                </span>
                                            </div>
                                            <form method="POST" action="{{ route('group.post.submission.grade', [$group, $post, $submissionItem]) }}" class="gc-grade-form" data-working-form>
                                                @csrf
                                                @method('PUT')
                                                <label for="grade-{{ $submissionItem->id }}">Marks</label>
                                                <div class="gc-grade-form__controls">
                                                    <input id="grade-{{ $submissionItem->id }}" name="grade" type="number" min="0" max="{{ $post->total_marks }}" value="{{ $submissionItem->grade }}" class="gc-input" required inputmode="numeric">
                                                    <span>/ {{ $post->total_marks }}</span>
                                                    <button class="gc-button gc-button--primary gc-button--compact" type="submit" data-submit-label="Saving…">Save marks</button>
                                                </div>
                                                @if ($submissionItem->graded_at)<small class="text-green-700">Draft saved {{ $submissionItem->graded_at->format('M j, H:i') }}</small>@endif
                                            </form>
                                            <form method="POST" action="{{ route('group.post.submission.return', [$group, $post, $submissionItem]) }}" class="gc-return-form" data-working-form>
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="returned" value="{{ $submissionItem->returned_at ? 0 : 1 }}">
                                                <button type="submit" class="gc-button {{ $submissionItem->returned_at ? 'gc-button--outline' : 'gc-button--green' }} w-full" data-submit-label="Updating…">
                                                    <i class="fa-solid {{ $submissionItem->returned_at ? 'fa-arrow-rotate-left' : 'fa-reply' }}" aria-hidden="true"></i>
                                                    {{ $submissionItem->returned_at ? 'Undo return' : 'Return work' }}
                                                </button>
                                                <small>{{ $submissionItem->returned_at ? 'The student can see the returned status and released marks.' : 'Return the work when feedback and marks are ready.' }}</small>
                                            </form>
                                        </div>
                                    @else
                                        <p class="gc-submission__answer">{{ $post->type === \App\Enums\PostType::Completion ? 'Marked as completed' : $submissionItem->post_answer }}</p>
                                        <div class="gc-submission__status-row mt-4">
                                            <span class="gc-status {{ $submissionItem->returned_at ? 'gc-status--done' : 'gc-status--info' }}">
                                                <i class="fa-solid {{ $submissionItem->returned_at ? 'fa-rotate-left' : 'fa-paper-plane' }}" aria-hidden="true"></i>
                                                {{ $submissionItem->returned_at ? 'Returned' : 'Turned in' }}
                                            </span>
                                        </div>
                                        <form method="POST" action="{{ route('group.post.submission.return', [$group, $post, $submissionItem]) }}" class="gc-return-form mt-4" data-working-form>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="returned" value="{{ $submissionItem->returned_at ? 0 : 1 }}">
                                            <button type="submit" class="gc-button {{ $submissionItem->returned_at ? 'gc-button--outline' : 'gc-button--green' }} w-full" data-submit-label="Updating…">
                                                <i class="fa-solid {{ $submissionItem->returned_at ? 'fa-arrow-rotate-left' : 'fa-reply' }}" aria-hidden="true"></i>
                                                {{ $submissionItem->returned_at ? 'Undo return' : 'Return work' }}
                                            </button>
                                        </form>
                                    @endif
                                </article>
                                @if ($loop->last)</div>@endif
                            @empty
                                <div class="gc-empty border-0"><i class="fa-regular fa-paper-plane"></i><p>No submissions yet.</p></div>
                            @endforelse
                            @if (method_exists($submissions, 'links'))<div class="mt-6">{{ $submissions->links() }}</div>@endif
                        </div>
                    </section>
                @endif

                <section class="gc-card">
                    <div class="gc-card__header"><div><h2 class="gc-card__title">Class comments</h2><p class="gc-card__subtitle">Discuss this post with the class.</p></div></div>
                    <div class="gc-card__body">
                        <div class="gc-comment-list">
                            @forelse ($comments as $comment)
                                <article class="gc-comment">
                                    <span class="gc-avatar-fallback gc-avatar--sm" aria-hidden="true">{{ mb_substr($comment->user->name, 0, 1) }}</span>
                                    <div><div class="gc-comment__author">{{ $comment->user->name }}</div><p class="gc-comment__content">{{ $comment->content }}</p></div>
                                    @can('update', $comment)
                                        <x-dropdown align="right" width="48">
                                            <x-slot name="trigger"><button type="button" class="gc-icon-button" aria-label="Comment actions"><i class="fa-solid fa-ellipsis-vertical"></i></button></x-slot>
                                            <x-slot name="content">
                                                <x-dropdown-link as="button" data-livewire-modal="edit-comment" data-argument-name="comment" data-argument-id="{{ $comment->id }}"><span class="flex items-center gap-3"><i class="fa-solid fa-pen fa-fw"></i>Edit</span></x-dropdown-link>
                                                <x-dropdown-link as="button" data-livewire-modal="destroy-comment" data-argument-name="comment" data-argument-id="{{ $comment->id }}"><span class="flex items-center gap-3 text-red-700"><i class="fa-solid fa-trash fa-fw"></i>Delete</span></x-dropdown-link>
                                            </x-slot>
                                        </x-dropdown>
                                    @endcan
                                </article>
                            @empty
                                <p class="text-gray-500">No comments yet.</p>
                            @endforelse
                        </div>
                        <div class="mt-5">{{ $comments->links() }}</div>
                        @can('store', [App\Models\Comment::class, $post])
                            <form action="{{ route('post.comment.store', $post) }}" method="POST" class="gc-comment-form">
                                @csrf
                                <div class="gc-field"><label class="sr-only" for="post-comment-content">Add a class comment</label><textarea name="content" id="post-comment-content" rows="2" class="gc-textarea min-h-0" placeholder="Add a class comment" required maxlength="1000"></textarea></div>
                                <button type="submit" class="gc-icon-button text-blue-700" aria-label="Post comment"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        @endcan
                    </div>
                </section>
            </div>

            <aside>
                @if (! $isReviewer && ! $isAnnouncement)
                    <section class="gc-card gc-work-card">
                        <div class="gc-card__header">
                            <div><h2 class="gc-card__title">Your work</h2><p class="gc-work-card__status">{{ $studentWorkState }}</p></div>
                            <span class="gc-status {{ $submission?->returned_at ? 'gc-status--done' : ($submission?->finished ? 'gc-status--info' : 'gc-status--todo') }}">{{ $submission?->returned_at ? 'Returned' : ($submission?->finished ? 'Submitted' : 'To do') }}</span>
                        </div>

                        @if ($isFileUpload)
                            <div class="gc-card__body grid gap-4">
                                @if ($submission?->submission_path)
                                    <div class="gc-file-row">
                                        <i class="fa-solid fa-file-zipper text-blue-600" aria-hidden="true"></i>
                                        <span class="gc-file-row__details"><strong class="gc-file-name">{{ $submission->submission_name }}</strong><small>Submitted {{ $submission->updated_at->format('M j, Y H:i') }}</small></span>
                                        <a class="gc-icon-button" href="{{ route('group.post.submission.download', [$group, $post, $submission]) }}" aria-label="Download your submission"><i class="fa-solid fa-download"></i></a>
                                    </div>
                                    @if ($submission->returned_at && $submission->grade !== null)
                                        <div class="gc-grade-result"><small>Marks awarded</small><strong>{{ $submission->grade }} <span>/ {{ $post->total_marks }}</span></strong></div>
                                    @elseif ($submission->grade !== null)
                                        <p class="text-sm text-gray-600">Your teacher has saved marks. They will appear after the work is returned.</p>
                                    @else
                                        <p class="text-sm text-gray-600">Your teacher has not graded this submission yet.</p>
                                    @endif
                                @endif

                                @if ($submissionWindowOpen)
                                    <form method="POST" action="{{ route('group.post.submission.store', [$group, $post]) }}" enctype="multipart/form-data" class="grid gap-4" data-working-form>
                                        @csrf
                                        <div class="gc-field">
                                            <label for="submission-file">{{ $submission?->submission_path ? 'Replace ZIP submission' : 'Upload ZIP submission' }}</label>
                                            <input id="submission-file" name="submission_file" type="file" accept=".zip,application/zip" class="gc-file-input" required>
                                            <small>ZIP files only, up to 10 MB. Replacing a file clears any previous marks.</small>
                                            <x-input-error :messages="$errors->get('submission_file')" />
                                        </div>
                                        <button type="submit" class="gc-button gc-button--primary w-full" data-submit-label="Uploading…">{{ $submission?->submission_path ? 'Replace submission' : 'Turn in ZIP file' }}</button>
                                    </form>
                                @else
                                    <div class="gc-alert gc-alert--warning"><i class="fa-solid fa-lock"></i><span>The submission deadline has passed. Uploads and replacements are closed.</span></div>
                                @endif
                            </div>
                        @else
                            <form method="POST" action="{{ route('group.post.finish', [$post->group, $post]) }}" class="gc-card__body">
                                @csrf
                                @if ($post->type === \App\Enums\PostType::Completion)
                                    <p class="text-gray-600 mb-5">Mark this assignment as completed when you finish the work.</p>
                                    <input type="hidden" name="finished" value="{{ $submission?->finished ? 0 : 1 }}">
                                    <button type="submit" class="gc-button {{ $submission?->finished ? 'gc-button--outline' : 'gc-button--primary' }} w-full">{{ $submission?->finished ? 'Unsubmit' : 'Mark as done' }}</button>
                                @elseif ($post->type === \App\Enums\PostType::TextAnswer)
                                    <div class="gc-field">
                                        <label for="assignment-answer">Your answer</label>
                                        <textarea id="assignment-answer" name="post_answer" rows="7" class="gc-textarea" placeholder="Type your answer" {{ $submission?->finished ? 'disabled' : 'required' }}>{{ $submission?->post_answer }}</textarea>
                                    </div>
                                    <input type="hidden" name="finished" value="{{ $submission?->finished ? 0 : 1 }}">
                                    <button type="submit" class="gc-button {{ $submission?->finished ? 'gc-button--outline' : 'gc-button--primary' }} w-full mt-5">{{ $submission?->finished ? 'Unsubmit' : 'Turn in' }}</button>
                                @endif
                            </form>
                        @endif
                    </section>
                @else
                    <section class="gc-card gc-card__body">
                        <h2 class="gc-card__title text-base">{{ $isAnnouncement ? 'Announcement' : 'Assignment overview' }}</h2>
                        <p class="gc-card__subtitle mt-2">{{ $isReviewer ? 'Review student work, download ZIP files, and save marks here.' : 'No submission is required for this post.' }}</p>
                    </section>
                @endif
            </aside>
        </div>
    </div>

    @if (! $group->public)
        @can('update', $group)
            <div id="toast-bottom-right" class="gc-toast" role="alert" style="visibility:hidden;opacity:0;transition:opacity .2s ease"><div class="flex items-center gap-3"><div class="flex-1"><small class="block opacity-75">Class code</small><strong class="text-lg tracking-widest">{{ $post->group->invite_key }}</strong></div><button type="button" class="gc-icon-button text-white" data-hide-invite aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div></div>
        @endcan
    @endif
</x-app-layout>
@endif
