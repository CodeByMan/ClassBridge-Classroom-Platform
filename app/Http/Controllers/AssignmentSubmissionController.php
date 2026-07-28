<?php

namespace App\Http\Controllers;

use App\Http\Requests\GradeAssignmentSubmissionRequest;
use App\Http\Requests\ReturnAssignmentSubmissionRequest;
use App\Http\Requests\StoreAssignmentSubmissionRequest;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssignmentSubmissionController extends Controller
{
    public function store(StoreAssignmentSubmissionRequest $request, Group $group, Post $post): RedirectResponse
    {
        $uploadedFile = $request->file('submission_file');
        $storedPath = $uploadedFile->storeAs(
            'post-'.$post->id,
            Str::uuid().'.zip',
            'submissions'
        );

        if (! is_string($storedPath)) {
            return back()->withErrors(['submission_file' => 'The ZIP file could not be stored. Please try again.']);
        }

        $previousPath = null;

        try {
            DB::transaction(function () use ($request, $post, $uploadedFile, $storedPath, &$previousPath): void {
                $submission = PostUser::query()->firstOrNew([
                    'post_id' => $post->id,
                    'user_id' => $request->user()->id,
                ]);

                $previousPath = $submission->submission_path;
                $submission->fill([
                    'finished' => true,
                    'post_answer' => null,
                    'submission_path' => $storedPath,
                    'submission_name' => $this->safeOriginalName($uploadedFile->getClientOriginalName()),
                    'grade' => null,
                    'graded_at' => null,
                    'returned_at' => null,
                ]);
                $submission->save();
            });
        } catch (Throwable $exception) {
            Storage::disk('submissions')->delete($storedPath);
            throw $exception;
        }

        if ($previousPath && $previousPath !== $storedPath) {
            Storage::disk('submissions')->delete($previousPath);
        }

        return redirect()
            ->route('group.post.show', [$group, $post])
            ->with('success', $previousPath ? 'Submission replaced successfully.' : 'Assignment submitted successfully.');
    }

    public function download(Group $group, Post $post, PostUser $submission): StreamedResponse
    {
        $this->ensureScopedSubmission($post, $submission);
        $this->authorize('download', $submission);

        abort_unless(Storage::disk('submissions')->exists($submission->submission_path), 404);

        return Storage::disk('submissions')->download(
            $submission->submission_path,
            $submission->submission_name ?: 'assignment-submission.zip',
            ['Content-Type' => 'application/zip']
        );
    }

    public function grade(
        GradeAssignmentSubmissionRequest $request,
        Group $group,
        Post $post,
        PostUser $submission
    ): RedirectResponse {
        $this->ensureScopedSubmission($post, $submission);
        $validated = $request->validated();

        $submission->forceFill([
            'grade' => $validated['grade'],
            'graded_at' => now(),
        ])->save();

        return back()->with('success', 'Marks saved successfully.');
    }

    public function returnWork(
        ReturnAssignmentSubmissionRequest $request,
        Group $group,
        Post $post,
        PostUser $submission
    ): RedirectResponse {
        $this->ensureScopedSubmission($post, $submission);
        $returned = (bool) $request->validated('returned');

        $submission->forceFill([
            'returned_at' => $returned ? now() : null,
        ])->save();

        return back()->with(
            'success',
            $returned ? 'Work returned to the student.' : 'Returned status removed.'
        );
    }

    private function ensureScopedSubmission(Post $post, PostUser $submission): void
    {
        abort_unless($submission->post_id === $post->id, 404);
    }

    private function safeOriginalName(string $name): string
    {
        $filename = basename(str_replace('\\', '/', $name));
        $filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename) ?: 'assignment-submission.zip';
        $filename = Str::limit($filename, 240, '');

        return str_ends_with(strtolower($filename), '.zip') ? $filename : $filename.'.zip';
    }
}
