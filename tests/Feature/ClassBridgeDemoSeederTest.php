<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Database\Seeders\ClassBridgeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClassBridgeDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_verified_teacher_student_and_assignment_data(): void
    {
        Storage::fake('submissions');
        $this->seed(ClassBridgeDemoSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'teacher@classbridge.test']);
        $this->assertDatabaseHas('users', ['email' => 'student@classbridge.test']);
        $this->assertSame(6, User::count());
        $this->assertSame(4, Group::count());
        $this->assertGreaterThanOrEqual(10, Post::count());
        $this->assertGreaterThanOrEqual(8, PostUser::count());
        $this->assertTrue(Post::where('type', PostType::Announcement)->exists());
        $this->assertTrue(Post::where('type', PostType::Completion)->exists());
        $this->assertTrue(Post::where('type', PostType::TextAnswer)->exists());
        $this->assertTrue(Post::where('type', PostType::FileUpload)->exists());
        $this->assertTrue(PostUser::whereNotNull('submission_path')->whereNotNull('grade')->exists());
        $this->assertTrue(Post::whereNotNull('deadline')->where('deadline', '>', now())->exists());
        $this->assertNotNull(User::where('email', 'student@classbridge.test')->firstOrFail()->email_verified_at);
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        Storage::fake('submissions');
        $this->seed(ClassBridgeDemoSeeder::class);
        $this->seed(ClassBridgeDemoSeeder::class);

        $this->assertSame(6, User::count());
        $this->assertSame(4, Group::count());
        $this->assertSame(1, User::where('email', 'teacher@classbridge.test')->count());
        $this->assertSame(1, Group::where('invite_key', 'WEBDEV2026')->count());
    }
}
