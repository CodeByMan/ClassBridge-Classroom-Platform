<?php

namespace App\Http\Controllers;

use App\Enums\PostType;
use App\Http\Requests\FinishPostRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Post::class, 'post');
    }

    public function store(StorePostRequest $request, Group $group): RedirectResponse
    {
        $validated = $request->validated();
        $type = PostType::from((int) $validated['type']);
        $validated['type'] = $type;
        $validated['total_marks'] = $type === PostType::FileUpload
            ? (int) $validated['total_marks']
            : null;

        $post = new Post($validated);
        $post->user()->associate($request->user());
        $post->group()->associate($group);
        $post->save();

        return back()->with('success', 'Classwork created successfully.');
    }

    public function show(Group $group, Post $post): View
    {
        $post->load(['user', 'group']);
        $requestUser = request()->user();
        $canReviewSubmissions = $requestUser->id === $post->user_id;

        $comments = $post->comments()
            ->with('user:id,name')
            ->oldest()
            ->paginate(20, ['*'], 'comments_page')
            ->withQueryString();

        $submissions = $canReviewSubmissions
            ? $post->postusers()
                ->with('user:id,name,image,email')
                ->where('finished', true)
                ->latest('updated_at')
                ->paginate(18, ['*'], 'submissions_page')
                ->withQueryString()
            : collect();

        return view('app.post.show', [
            'post' => $post,
            'comments' => $comments,
            'group' => $group,
            'submissions' => $submissions,
            'submission' => $post->postusers()->where('user_id', $requestUser->id)->first(),
            'finished_user_count' => $post->postusers()->where('finished', true)->count(),
            'navigationGroups' => $requestUser->groups()->select('groups.id', 'groups.name')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdatePostRequest $request, Group $group, Post $post): RedirectResponse
    {
        $validated = $request->validated();
        $deadlineEnabled = (bool) $validated['deadline_enabled'];
        unset($validated['deadline_enabled']);

        if (! $deadlineEnabled) {
            $validated['deadline'] = null;
        }

        $requestedType = PostType::from((int) $validated['type']);

        $hasSubmissions = $post->postusers()->exists();

        if ($requestedType !== $post->type && $hasSubmissions) {
            throw ValidationException::withMessages([
                'type' => 'The assignment type cannot be changed after submissions exist.',
            ]);
        }

        if (
            $post->type === PostType::FileUpload
            && $hasSubmissions
            && (int) $validated['total_marks'] !== (int) $post->total_marks
        ) {
            throw ValidationException::withMessages([
                'total_marks' => 'Total marks cannot be changed after submissions exist.',
            ]);
        }

        $validated['type'] = $requestedType;
        $validated['total_marks'] = $requestedType === PostType::FileUpload
            ? (int) $validated['total_marks']
            : null;
        $post->update($validated);

        return back()->with('success', 'Classwork updated successfully.');
    }

    public function destroy(Group $group, Post $post): RedirectResponse
    {
        $paths = $post->postusers()
            ->whereNotNull('submission_path')
            ->pluck('submission_path')
            ->all();

        $post->delete();

        if ($paths !== []) {
            Storage::disk('submissions')->delete($paths);
        }

        return redirect()->route('group.show', $group)->with('success', 'Classwork deleted successfully.');
    }

    public function finish(FinishPostRequest $request, Group $group, Post $post): RedirectResponse
    {
        $validated = $request->validated();
        $submission = PostUser::firstOrNew([
            'user_id' => $request->user()->id,
            'post_id' => $post->id,
        ]);
        $submission->finished = $validated['finished'];
        $submission->returned_at = null;

        if (array_key_exists('post_answer', $validated)) {
            $submission->post_answer = $validated['post_answer'];
        }

        $submission->save();

        return redirect()->route('group.post.show', [$group, $post]);
    }
}
