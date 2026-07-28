<?php

namespace Tests\Feature\Comments;

use App\Enums\PostType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_member_can_add_a_valid_comment(): void
    {
        [$member, $post] = $this->memberAndPost();

        $this->actingAs($member)->post(route('post.comment.store', $post), [
            'content' => 'This is clear.',
        ])->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $member->id,
            'content' => 'This is clear.',
        ]);
    }

    public function test_outsider_cannot_comment_on_private_group_post(): void
    {
        [, $post] = $this->memberAndPost();
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($outsider)->post(route('post.comment.store', $post), [
            'content' => 'Unauthorized comment',
        ])->assertForbidden();
    }

    public function test_comment_content_is_required_and_bounded(): void
    {
        [$member, $post] = $this->memberAndPost();

        $this->actingAs($member)
            ->from(route('group.post.show', [$post->group, $post]))
            ->post(route('post.comment.store', $post), ['content' => str_repeat('x', 513)])
            ->assertSessionHasErrors('content');
    }

    public function test_comment_cannot_be_updated_through_another_post(): void
    {
        [$member, $post] = $this->memberAndPost();
        $otherPost = Post::forceCreate([
            'name' => 'Other assignment',
            'type' => PostType::Completion->value,
            'user_id' => $post->user_id,
            'group_id' => $post->group_id,
        ]);
        $comment = Comment::make(['content' => 'Original']);
        $comment->user()->associate($member);
        $post->comments()->save($comment);

        $this->actingAs($member)
            ->put(route('post.comment.update', [$otherPost, $comment]), ['content' => 'Changed'])
            ->assertNotFound();
    }

    private function memberAndPost(): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Course',
            'user_id' => $owner->id,
            'invite_key' => 'COMMENT001',
            'public' => false,
        ]);
        $group->users()->attach([$owner->id, $member->id]);
        $post = Post::forceCreate([
            'name' => 'Assignment',
            'type' => PostType::Completion->value,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        return [$member, $post];
    }
}
