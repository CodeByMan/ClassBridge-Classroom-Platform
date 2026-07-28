<?php

namespace Tests\Feature\Posts;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssignmentSubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('submissions');
    }

    public function test_teacher_can_create_a_zip_submission_assignment(): void
    {
        [$teacher, , $group] = $this->classroom();

        $this->actingAs($teacher)
            ->post(route('group.post.store', $group), [
                'name' => 'Portfolio ZIP',
                'content' => 'Upload your project archive.',
                'type' => PostType::FileUpload->value,
                'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
                'total_marks' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'group_id' => $group->id,
            'name' => 'Portfolio ZIP',
            'type' => PostType::FileUpload->value,
            'total_marks' => 100,
        ]);
    }

    public function test_student_can_upload_and_replace_zip_before_deadline(): void
    {
        [$teacher, $student, $group, $post] = $this->classroomWithFileAssignment();

        $this->actingAs($student)
            ->post(route('group.post.submission.store', [$group, $post]), [
                'submission_file' => $this->uploadedZip('first.zip'),
            ])
            ->assertRedirect(route('group.post.show', [$group, $post]));

        $submission = PostUser::where('post_id', $post->id)->where('user_id', $student->id)->firstOrFail();
        Storage::disk('submissions')->assertExists($submission->submission_path);
        $firstPath = $submission->submission_path;

        $submission->forceFill(['grade' => 75, 'graded_at' => now()])->save();

        $this->actingAs($student)
            ->post(route('group.post.submission.store', [$group, $post]), [
                'submission_file' => $this->uploadedZip('replacement.zip'),
            ])
            ->assertRedirect(route('group.post.show', [$group, $post]));

        $submission->refresh();
        Storage::disk('submissions')->assertMissing($firstPath);
        Storage::disk('submissions')->assertExists($submission->submission_path);
        $this->assertSame('replacement.zip', $submission->submission_name);
        $this->assertNull($submission->grade);
        $this->assertNull($submission->graded_at);
        $this->assertTrue($submission->finished);
    }

    public function test_non_zip_file_is_rejected(): void
    {
        [, $student, $group, $post] = $this->classroomWithFileAssignment();

        $this->actingAs($student)
            ->from(route('group.post.show', [$group, $post]))
            ->post(route('group.post.submission.store', [$group, $post]), [
                'submission_file' => UploadedFile::fake()->create('malware.exe', 30, 'application/x-msdownload'),
            ])
            ->assertSessionHasErrors('submission_file');

        $this->assertDatabaseCount('post_user', 0);
    }

    public function test_student_cannot_upload_or_replace_after_deadline(): void
    {
        [, $student, $group, $post] = $this->classroomWithFileAssignment();
        $post->update(['deadline' => now()->subMinute()]);

        $this->actingAs($student)
            ->post(route('group.post.submission.store', [$group, $post]), [
                'submission_file' => $this->uploadedZip('late.zip'),
            ])
            ->assertForbidden();
    }

    public function test_assignment_owner_and_outsider_cannot_upload_student_work(): void
    {
        [$teacher, , $group, $post] = $this->classroomWithFileAssignment();
        $outsider = User::factory()->create();
        $file = fn () => $this->uploadedZip('work.zip');

        $this->actingAs($teacher)
            ->post(route('group.post.submission.store', [$group, $post]), ['submission_file' => $file()])
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post(route('group.post.submission.store', [$group, $post]), ['submission_file' => $file()])
            ->assertForbidden();
    }

    public function test_teacher_can_download_and_grade_submission_and_student_can_view_marks(): void
    {
        [$teacher, $student, $group, $post] = $this->classroomWithFileAssignment();
        $path = 'post-'.$post->id.'/student.zip';
        Storage::disk('submissions')->put($path, 'zip-content');
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $student->id,
            'finished' => true,
            'submission_path' => $path,
            'submission_name' => 'student-solution.zip',
        ]);

        $this->actingAs($teacher)
            ->get(route('group.post.submission.download', [$group, $post, $submission]))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($teacher)
            ->put(route('group.post.submission.grade', [$group, $post, $submission]), ['grade' => 88])
            ->assertRedirect();

        $this->assertDatabaseHas('post_user', [
            'id' => $submission->id,
            'grade' => 88,
        ]);
        $this->assertNotNull($submission->fresh()->graded_at);

        $this->actingAs($student)
            ->get(route('group.post.show', [$group, $post]))
            ->assertOk()
            ->assertDontSee('Marks awarded')
            ->assertSee('They will appear after the work is returned.');

        $this->actingAs($teacher)
            ->put(route('group.post.submission.return', [$group, $post, $submission]), ['returned' => true])
            ->assertRedirect();

        $this->assertNotNull($submission->fresh()->returned_at);

        $this->actingAs($student)
            ->get(route('group.post.show', [$group, $post]))
            ->assertOk()
            ->assertSee('Returned')
            ->assertSee('88')
            ->assertSee('/ 100');

        $this->actingAs($teacher)
            ->put(route('group.post.submission.return', [$group, $post, $submission]), ['returned' => false])
            ->assertRedirect();

        $this->assertNull($submission->fresh()->returned_at);
    }

    public function test_marks_cannot_exceed_assignment_total(): void
    {
        [$teacher, $student, $group, $post] = $this->classroomWithFileAssignment();
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $student->id,
            'finished' => true,
            'submission_path' => 'post-'.$post->id.'/student.zip',
            'submission_name' => 'student.zip',
        ]);

        $this->actingAs($teacher)
            ->from(route('group.post.show', [$group, $post]))
            ->put(route('group.post.submission.grade', [$group, $post, $submission]), ['grade' => 101])
            ->assertSessionHasErrors('grade');
    }

    public function test_non_owner_cannot_grade_or_download_another_students_submission(): void
    {
        [, $student, $group, $post] = $this->classroomWithFileAssignment();
        $otherStudent = User::factory()->create();
        $group->users()->attach($otherStudent);
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $student->id,
            'finished' => true,
            'submission_path' => 'post-'.$post->id.'/student.zip',
            'submission_name' => 'student.zip',
        ]);
        Storage::disk('submissions')->put($submission->submission_path, 'zip-content');

        $this->actingAs($otherStudent)
            ->get(route('group.post.submission.download', [$group, $post, $submission]))
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->put(route('group.post.submission.grade', [$group, $post, $submission]), ['grade' => 50])
            ->assertForbidden();

        $this->actingAs($otherStudent)
            ->put(route('group.post.submission.return', [$group, $post, $submission]), ['returned' => true])
            ->assertForbidden();
    }

    private function uploadedZip(string $name): UploadedFile
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'classbridge-zip-');
        copy(database_path('seeders/files/demo-submission.zip'), $temporaryPath);

        return new UploadedFile($temporaryPath, $name, 'application/zip', null, true);
    }

    private function classroom(): array
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $group = Group::forceCreate([
            'name' => 'Laravel Class',
            'user_id' => $teacher->id,
            'invite_key' => 'ZIPCLASS01',
            'public' => false,
        ]);
        $group->users()->attach([$teacher->id, $student->id]);

        return [$teacher, $student, $group];
    }

    private function classroomWithFileAssignment(): array
    {
        [$teacher, $student, $group] = $this->classroom();
        $post = Post::forceCreate([
            'name' => 'Upload project',
            'content' => 'Submit a ZIP file.',
            'type' => PostType::FileUpload->value,
            'deadline' => now()->addDay(),
            'total_marks' => 100,
            'user_id' => $teacher->id,
            'group_id' => $group->id,
        ]);

        return [$teacher, $student, $group, $post];
    }
}
