<?php

namespace Database\Seeders;

use App\Enums\PostType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ClassBridgeDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $password = Hash::make((string) config('demo.password', 'Demo1234!'));

            $users = [
                'teacher' => $this->user('Aisha Khan', 'teacher@classbridge.test', 'Web development teacher focused on practical, accessible learning experiences.', $password),
                'teacher_two' => $this->user('Omar Siddiqui', 'omar.teacher@classbridge.test', 'Database and writing teacher who enjoys project-based classroom activities.', $password),
                'student' => $this->user('Ali Raza', 'student@classbridge.test', 'Student exploring Laravel, databases, and modern frontend development.', $password),
                'sara' => $this->user('Sara Ahmed', 'sara@classbridge.test', 'Student interested in UI design, accessibility, and collaborative projects.', $password),
                'hamza' => $this->user('Hamza Malik', 'hamza@classbridge.test', 'Student practicing responsive layouts and backend fundamentals.', $password),
                'fatima' => $this->user('Fatima Noor', 'fatima@classbridge.test', 'Student focused on visual design, communication, and user research.', $password),
            ];

            $groups = [
                'web' => $this->group('Web Development Fundamentals', 'WEBDEV2026', false, $users['teacher']),
                'database' => $this->group('Database Systems', 'DATABASE26', true, $users['teacher_two']),
                'design' => $this->group('UI UX Design Studio', 'UIDESIGN26', true, $users['teacher']),
                'writing' => $this->group('Creative Writing Lab', 'WRITING026', true, $users['teacher_two']),
            ];

            $groups['web']->users()->sync([$users['teacher']->id, $users['student']->id, $users['sara']->id, $users['hamza']->id]);
            $groups['database']->users()->sync([$users['teacher_two']->id, $users['student']->id, $users['sara']->id]);
            $groups['design']->users()->sync([$users['teacher']->id, $users['sara']->id, $users['fatima']->id]);
            $groups['writing']->users()->sync([$users['teacher_two']->id, $users['hamza']->id, $users['fatima']->id]);

            $webWelcome = $this->post($groups['web'], $users['teacher'], 'Welcome to Web Development', 'Review the course overview, introduce yourself, and check the upcoming assignments.', PostType::Announcement, null, now()->subDays(8));
            $semanticHtml = $this->post($groups['web'], $users['teacher'], 'Semantic HTML Reflection', 'Explain how semantic HTML improves accessibility and maintainability. Include two practical examples.', PostType::TextAnswer, now()->addDays(3)->setTime(17, 0), now()->subDays(3));
            $cssGrid = $this->post($groups['web'], $users['teacher'], 'CSS Grid Practice', 'Build the responsive card layout and mark the assignment complete after testing desktop and mobile widths.', PostType::Completion, now()->addDays(7)->setTime(18, 0), now()->subDays(2));
            $portfolioZip = $this->post($groups['web'], $users['teacher'], 'Laravel Portfolio ZIP', 'Package your Laravel portfolio exercise as a ZIP file. Include source code and a short README. Uploads may be replaced before the deadline.', PostType::FileUpload, now()->addDays(6)->setTime(20, 0), now()->subDay(), 100);

            $databaseWelcome = $this->post($groups['database'], $users['teacher_two'], 'Database Course Welcome', 'This public class covers relational design, migrations, indexes, and practical query optimization.', PostType::Announcement, null, now()->subDays(6));
            $normalization = $this->post($groups['database'], $users['teacher_two'], 'Normalize a Bookstore Schema', 'Convert the sample bookstore table into a clear third-normal-form design and explain your relationships.', PostType::TextAnswer, now()->addDays(5)->setTime(16, 30), now()->subDays(2));
            $indexes = $this->post($groups['database'], $users['teacher_two'], 'Create Migration Indexes', 'Add appropriate indexes to the sample migration and confirm the migration can roll back safely.', PostType::Completion, now()->addDays(10)->setTime(15, 0), now()->subDay());

            $designWelcome = $this->post($groups['design'], $users['teacher'], 'Design Studio Welcome', 'Share one interface you admire and explain the design decision that makes it effective.', PostType::Announcement, null, now()->subDays(5));
            $accessibility = $this->post($groups['design'], $users['teacher'], 'Accessibility Review', 'Review one page for keyboard access, contrast, labels, focus states, and semantic structure.', PostType::TextAnswer, now()->addDays(4)->setTime(14, 0), now()->subDays(2));

            $writingWelcome = $this->post($groups['writing'], $users['teacher_two'], 'Writing Lab Introduction', 'Introduce yourself and describe the kind of writing you would like to improve this term.', PostType::Announcement, null, now()->subDays(4));
            $storyOutline = $this->post($groups['writing'], $users['teacher_two'], 'Short Story Outline', 'Submit a concise outline containing a protagonist, central conflict, setting, and intended ending.', PostType::TextAnswer, now()->addDays(8)->setTime(19, 0), now()->subDay());

            $this->submission($semanticHtml, $users['student'], true, 'Semantic elements communicate purpose to browsers and assistive technologies.', now()->subDay());
            $this->submission($semanticHtml, $users['sara'], false, null, now()->subHours(6));
            $this->submission($cssGrid, $users['hamza'], true, null, now()->subHours(5));
            $this->submission($normalization, $users['student'], true, 'I separated authors, books, customers, orders, and order items so each fact is stored once.', now()->subHours(8));
            $this->submission($indexes, $users['sara'], true, null, now()->subHours(3));
            $this->submission($accessibility, $users['sara'], true, 'The page needs stronger visible focus states, descriptive labels, and a corrected heading hierarchy.', now()->subHours(4));
            $this->submission($storyOutline, $users['fatima'], true, 'A student organizes a final storytelling night to save her neighborhood library.', now()->subHours(7));

            $this->seedFileSubmission($portfolioZip, $users['student'], 86, now()->subHours(5));

            $this->comment($webWelcome, $users['student'], 'Excited to start. The upcoming deadlines are showing correctly on my dashboard.', now()->subDays(6));
            $this->comment($webWelcome, $users['teacher'], 'Great—begin with the semantic HTML reflection and ask questions here.', now()->subDays(6)->addHour());
            $this->comment($portfolioZip, $users['student'], 'The ZIP upload and marks card are working in the demo.', now()->subHours(4));
            $this->comment($databaseWelcome, $users['student'], 'The sample data is helpful for testing search and sorting.', now()->subDays(2));
        });
    }

    private function user(string $name, string $email, string $bio, string $password): User
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->fill(['name' => $name, 'password' => $password, 'bio' => $bio, 'image' => null]);
        $user->forceFill(['email_verified_at' => now()]);
        $user->save();

        return $user;
    }

    private function group(string $name, string $inviteKey, bool $public, User $owner): Group
    {
        $group = Group::firstOrNew(['invite_key' => $inviteKey]);
        $group->fill(['name' => $name, 'public' => $public]);
        $group->user()->associate($owner);
        $group->save();

        return $group;
    }

    private function post(Group $group, User $owner, string $name, string $content, PostType $type, ?Carbon $deadline, Carbon $createdAt, ?int $totalMarks = null): Post
    {
        $post = Post::firstOrNew(['group_id' => $group->id, 'name' => $name]);
        $post->fill(['name' => $name, 'content' => $content, 'type' => $type, 'deadline' => $deadline, 'total_marks' => $totalMarks]);
        $post->group()->associate($group);
        $post->user()->associate($owner);
        $post->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt]);
        $post->save();

        return $post;
    }

    private function submission(Post $post, User $user, bool $finished, ?string $answer, Carbon $submittedAt): void
    {
        $submission = PostUser::firstOrNew(['post_id' => $post->id, 'user_id' => $user->id]);
        $submission->fill(['finished' => $finished, 'post_answer' => $answer]);
        $submission->forceFill(['created_at' => $submittedAt, 'updated_at' => $submittedAt]);
        $submission->save();
    }

    private function seedFileSubmission(Post $post, User $user, int $grade, Carbon $submittedAt): void
    {
        $path = 'post-'.$post->id.'/demo-ali-raza.zip';
        Storage::disk('submissions')->put($path, file_get_contents(database_path('seeders/files/demo-submission.zip')));

        $submission = PostUser::firstOrNew(['post_id' => $post->id, 'user_id' => $user->id]);
        $submission->fill([
            'finished' => true,
            'post_answer' => null,
            'submission_path' => $path,
            'submission_name' => 'ali-raza-laravel-portfolio.zip',
            'grade' => $grade,
            'graded_at' => $submittedAt->copy()->addHours(2),
            'returned_at' => $submittedAt->copy()->addHours(3),
        ]);
        $submission->forceFill(['created_at' => $submittedAt, 'updated_at' => $submittedAt]);
        $submission->save();
    }

    private function comment(Post $post, User $user, string $content, Carbon $createdAt): void
    {
        $comment = Comment::firstOrNew(['post_id' => $post->id, 'user_id' => $user->id, 'content' => $content]);
        $comment->post()->associate($post);
        $comment->user()->associate($user);
        $comment->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt]);
        $comment->save();
    }
}
