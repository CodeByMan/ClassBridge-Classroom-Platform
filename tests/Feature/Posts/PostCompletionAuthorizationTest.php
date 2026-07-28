<?php

namespace Tests\Feature\Posts;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostCompletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_member_can_complete_a_post(): void
    {
        [$group, $post, $member] = $this->groupPostAndMember();

        $response = $this->actingAs($member)->post(route('group.post.finish', [$group, $post]), [
            'finished' => true,
            'post_answer' => 'My answer',
        ]);

        $response->assertRedirect(route('group.post.show', [$group, $post]));
        $this->assertDatabaseHas('post_user', [
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
            'post_answer' => 'My answer',
        ]);
    }

    public function test_teacher_can_return_and_undo_a_text_assignment_submission(): void
    {
        [$group, $post, $member] = $this->groupPostAndMember();
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
            'post_answer' => 'Submitted response',
        ]);

        $this->actingAs($post->user)
            ->put(route('group.post.submission.return', [$group, $post, $submission]), ['returned' => true])
            ->assertRedirect();

        $this->assertNotNull($submission->fresh()->returned_at);

        $this->actingAs($member)
            ->post(route('group.post.finish', [$group, $post]), [
                'finished' => false,
                'post_answer' => 'Submitted response',
            ])
            ->assertRedirect(route('group.post.show', [$group, $post]));

        $submission->refresh();
        $this->assertFalse($submission->finished);
        $this->assertNull($submission->returned_at);
    }

    public function test_assignment_author_cannot_submit_their_own_assignment(): void
    {
        [$group, $post] = $this->groupPostAndMember();
        $author = $post->user;

        $this->actingAs($author)
            ->post(route('group.post.finish', [$group, $post]), [
                'finished' => true,
                'post_answer' => 'Reviewer answer',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('post_user', [
            'post_id' => $post->id,
            'user_id' => $author->id,
        ]);
    }

    public function test_non_member_cannot_complete_a_post(): void
    {
        [$group, $post] = $this->groupPostAndMember();
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($outsider)
            ->post(route('group.post.finish', [$group, $post]), ['finished' => true])
            ->assertForbidden();

        $this->assertDatabaseMissing('post_user', [
            'post_id' => $post->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_post_from_another_group_is_not_resolved(): void
    {
        [$group, $post, $member] = $this->groupPostAndMember();
        $otherGroup = Group::forceCreate([
            'name' => 'Other group',
            'user_id' => $member->id,
            'invite_key' => 'OTHERKEY01',
            'public' => false,
        ]);
        $otherGroup->users()->attach($member);

        $this->actingAs($member)
            ->post(route('group.post.finish', [$otherGroup, $post]), ['finished' => true])
            ->assertNotFound();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        [$group, $post] = $this->groupPostAndMember();

        $this->post(route('group.post.finish', [$group, $post]), ['finished' => true])
            ->assertRedirect(route('login'));
    }

    public function test_text_answer_is_required_for_text_assignments(): void
    {
        [$group, $post, $member] = $this->groupPostAndMember();

        $this->actingAs($member)
            ->from(route('group.post.show', [$group, $post]))
            ->post(route('group.post.finish', [$group, $post]), ['finished' => true])
            ->assertSessionHasErrors('post_answer');
    }

    public function test_announcement_post_cannot_receive_a_completion_record(): void
    {
        [$group, $post, $member] = $this->groupPostAndMember();
        $post->update(['type' => PostType::Announcement->value]);

        $this->actingAs($member)
            ->post(route('group.post.finish', [$group, $post]), ['finished' => true])
            ->assertForbidden();

        $this->assertDatabaseMissing('post_user', [
            'post_id' => $post->id,
            'user_id' => $member->id,
        ]);
    }

    private function groupPostAndMember(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Test group',
            'user_id' => $owner->id,
            'invite_key' => 'TESTKEY001',
            'public' => false,
        ]);
        $group->users()->attach([$owner->id, $member->id]);
        $post = Post::forceCreate([
            'name' => 'Assignment',
            'content' => 'Answer this assignment.',
            'type' => PostType::TextAnswer->value,
            'deadline' => now()->addDay(),
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        return [$group, $post, $member];
    }
}
