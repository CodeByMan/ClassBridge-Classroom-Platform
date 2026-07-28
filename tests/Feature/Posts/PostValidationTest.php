<?php

namespace Tests\Feature\Posts;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_owner_can_create_a_valid_assignment(): void
    {
        [$owner, $group] = $this->ownerAndGroup();

        $this->actingAs($owner)->post(route('group.post.store', $group), [
            'name' => 'Homework',
            'content' => 'Complete exercise 1.',
            'type' => PostType::TextAnswer->value,
            'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
        ])->assertRedirect();

        $this->assertDatabaseHas('posts', [
            'name' => 'Homework',
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'type' => PostType::TextAnswer->value,
        ]);
    }

    public function test_non_owner_cannot_create_an_assignment_for_group(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group->users()->attach($member);

        $this->actingAs($member)->post(route('group.post.store', $group), [
            'name' => 'Unauthorized',
            'type' => PostType::Completion->value,
        ])->assertForbidden();

        $this->assertDatabaseMissing('posts', ['name' => 'Unauthorized']);
    }

    public function test_assignment_input_is_validated(): void
    {
        [$owner, $group] = $this->ownerAndGroup();

        $this->actingAs($owner)
            ->from(route('group.show', $group))
            ->post(route('group.post.store', $group), [
                'name' => str_repeat('x', 33),
                'content' => str_repeat('x', 1025),
                'type' => 9,
                'deadline' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['name', 'content', 'type', 'deadline']);
    }

    public function test_zip_assignment_requires_valid_total_marks(): void
    {
        [$owner, $group] = $this->ownerAndGroup();

        $this->actingAs($owner)
            ->from(route('group.show', $group))
            ->post(route('group.post.store', $group), [
                'name' => 'ZIP work',
                'content' => 'Upload a ZIP file.',
                'type' => PostType::FileUpload->value,
                'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('total_marks');

        $this->actingAs($owner)
            ->from(route('group.show', $group))
            ->post(route('group.post.store', $group), [
                'name' => 'ZIP work',
                'content' => 'Upload a ZIP file.',
                'type' => PostType::FileUpload->value,
                'deadline' => now()->addDay()->format('Y-m-d H:i:s'),
                'total_marks' => 1001,
            ])
            ->assertSessionHasErrors('total_marks');
    }

    public function test_mismatched_group_and_post_update_returns_not_found(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $otherGroup = Group::forceCreate([
            'name' => 'Other',
            'user_id' => $owner->id,
            'invite_key' => 'OTHERPOST1',
            'public' => false,
        ]);
        $post = Post::forceCreate([
            'name' => 'Homework',
            'type' => PostType::Completion->value,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($owner)->put(route('group.post.update', [$otherGroup, $post]), [
            'name' => 'Changed',
            'type' => PostType::Completion->value,
        ])->assertNotFound();
    }

    public function test_owner_can_remove_an_existing_deadline(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $post = Post::forceCreate([
            'name' => 'Timed assignment',
            'content' => 'Details',
            'type' => PostType::Completion->value,
            'deadline' => now()->addDay(),
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($owner)->put(route('group.post.update', [$group, $post]), [
            'name' => $post->name,
            'content' => $post->content,
            'type' => $post->type->value,
            'deadline_enabled' => '0',
        ])->assertRedirect();

        $this->assertNull($post->fresh()->deadline);
    }

    public function test_assignment_type_cannot_change_after_a_submission_exists(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group->users()->attach($member);
        $post = Post::forceCreate([
            'name' => 'Submitted assignment',
            'content' => 'Original details',
            'type' => PostType::TextAnswer->value,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);
        $post->postusers()->create([
            'user_id' => $member->id,
            'finished' => true,
            'post_answer' => 'Existing answer',
        ]);

        $this->actingAs($owner)
            ->from(route('group.post.show', [$group, $post]))
            ->put(route('group.post.update', [$group, $post]), [
                'name' => $post->name,
                'content' => $post->content,
                'type' => PostType::Completion->value,
                'deadline_enabled' => '0',
            ])
            ->assertRedirect(route('group.post.show', [$group, $post]))
            ->assertSessionHasErrors('type');

        $this->assertSame(2, $post->fresh()->type->value);
        $this->assertDatabaseHas('post_user', [
            'post_id' => $post->id,
            'user_id' => $member->id,
            'post_answer' => 'Existing answer',
        ]);
    }

    public function test_assignment_type_can_change_before_submissions_exist(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $post = Post::forceCreate([
            'name' => 'Unsubmitted assignment',
            'content' => 'Original details',
            'type' => PostType::TextAnswer->value,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        $this->actingAs($owner)
            ->put(route('group.post.update', [$group, $post]), [
                'name' => $post->name,
                'content' => $post->content,
                'type' => PostType::Completion->value,
                'deadline_enabled' => '0',
            ])
            ->assertRedirect();

        $this->assertSame(1, $post->fresh()->type->value);
    }

    public function test_file_assignment_total_marks_cannot_change_after_submission_exists(): void
    {
        [$owner, $group] = $this->ownerAndGroup();
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group->users()->attach($member);
        $post = Post::forceCreate([
            'name' => 'ZIP assignment',
            'content' => 'Upload work.',
            'type' => PostType::FileUpload->value,
            'deadline' => now()->addDay(),
            'total_marks' => 100,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);
        $post->postusers()->create([
            'user_id' => $member->id,
            'finished' => true,
            'submission_path' => 'post-'.$post->id.'/work.zip',
            'submission_name' => 'work.zip',
        ]);

        $this->actingAs($owner)
            ->from(route('group.post.show', [$group, $post]))
            ->put(route('group.post.update', [$group, $post]), [
                'name' => $post->name,
                'content' => $post->content,
                'type' => PostType::FileUpload->value,
                'total_marks' => 80,
                'deadline_enabled' => '1',
                'deadline' => $post->deadline->format('Y-m-d H:i:s'),
            ])
            ->assertSessionHasErrors('total_marks');

        $this->assertSame(100, $post->fresh()->total_marks);
    }

    private function ownerAndGroup(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Course',
            'user_id' => $owner->id,
            'invite_key' => 'COURSE0001',
            'public' => false,
        ]);
        $group->users()->attach($owner);

        return [$owner, $group];
    }
}
