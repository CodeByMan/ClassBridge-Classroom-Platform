<?php

namespace Tests\Feature\Posts;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentStatusRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_submission_is_rendered_as_assigned_even_when_another_user_submitted(): void
    {
        [$member, $post] = $this->assignment();
        $other = User::factory()->create(['email_verified_at' => now()]);
        $post->group->users()->attach($other);
        PostUser::create([
            'post_id' => $post->id,
            'user_id' => $other->id,
            'finished' => true,
        ]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="assignment-status-'.$post->id.'"', false)
            ->assertSee('gc-status gc-status--todo', false)
            ->assertSee('Assigned');
    }

    public function test_incomplete_submission_is_rendered_as_assigned(): void
    {
        [$member, $post] = $this->assignment();
        PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => false,
        ]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="assignment-status-'.$post->id.'"', false)
            ->assertSee('gc-status gc-status--todo', false)
            ->assertSee('Assigned');
    }

    public function test_completed_submission_on_time_is_rendered_as_turned_in(): void
    {
        [$member, $post] = $this->assignment();
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
        ]);
        $submission->forceFill([
            'updated_at' => $post->deadline->copy()->subMinute(),
        ])->saveQuietly();

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="assignment-status-'.$post->id.'"', false)
            ->assertSee('gc-status gc-status--done', false)
            ->assertSee('Turned in')
            ->assertDontSee('Turned in late');
    }

    public function test_completed_submission_after_deadline_is_rendered_as_turned_in_late(): void
    {
        [$member, $post] = $this->assignment();
        $submission = PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
        ]);
        $submission->forceFill([
            'updated_at' => $post->deadline->copy()->addMinute(),
        ])->saveQuietly();

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="assignment-status-'.$post->id.'"', false)
            ->assertSee('gc-status gc-status--late', false)
            ->assertSee('Turned in late');
    }

    public function test_completed_assignment_without_deadline_is_not_late(): void
    {
        [$member, $post] = $this->assignment(null);
        PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
        ]);

        $this->actingAs($member)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('data-testid="assignment-status-'.$post->id.'"', false)
            ->assertSee('gc-status gc-status--done', false)
            ->assertSee('Turned in')
            ->assertDontSee('Turned in late');
    }

    private function assignment($deadline = 'default'): array
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Status group',
            'user_id' => $owner->id,
            'invite_key' => 'STATUS0001',
            'public' => false,
        ]);
        $group->users()->attach([$owner->id, $member->id]);

        $post = Post::forceCreate([
            'name' => 'Status assignment',
            'content' => 'Status test',
            'type' => PostType::Completion->value,
            'deadline' => $deadline === 'default' ? now()->addHour() : $deadline,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);

        return [$member, $post];
    }
}
