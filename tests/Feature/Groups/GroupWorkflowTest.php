<?php

namespace Tests\Feature\Groups;

use App\Enums\PostType;
use App\Models\Group;
use App\Models\Post;
use App\Models\PostUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GroupWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_group_creation_attaches_creator_to_the_created_group(): void
    {
        $creator = User::factory()->create(['email_verified_at' => now()]);
        $unrelatedGroup = Group::forceCreate([
            'name' => 'Existing group',
            'user_id' => $creator->id,
            'invite_key' => 'EXISTING01',
            'public' => false,
        ]);

        $this->actingAs($creator)->post(route('group.store'), [
            'name' => 'New private group',
            'public' => false,
        ])->assertRedirect();

        $newGroup = Group::where('name', 'New private group')->firstOrFail();
        $this->assertTrue($newGroup->users()->whereKey($creator->id)->exists());
        $this->assertFalse($unrelatedGroup->users()->whereKey($creator->id)->exists());
    }

    public function test_public_group_creation_attaches_only_the_creator(): void
    {
        $creator = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($creator)->post(route('group.store'), [
            'name' => 'Public group',
            'public' => true,
        ])->assertRedirect();

        $group = Group::where('name', 'Public group')->firstOrFail();
        $this->assertTrue($group->users()->whereKey($creator->id)->exists());
        $this->assertFalse($group->users()->whereKey($otherUser->id)->exists());
    }

    public function test_user_explicitly_joins_a_public_group(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Discoverable group',
            'user_id' => $owner->id,
            'invite_key' => 'PUBLIC0001',
            'public' => true,
        ]);
        $group->users()->attach($owner);

        $this->actingAs($member)
            ->post(route('group.join-public', $group))
            ->assertRedirect(route('group.show', $group));

        $this->assertTrue($group->users()->whereKey($member->id)->exists());
    }

    public function test_private_group_cannot_be_joined_through_public_join_route(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Private group',
            'user_id' => $owner->id,
            'invite_key' => 'PRIVATE001',
            'public' => false,
        ]);

        $this->actingAs($member)
            ->post(route('group.join-public', $group))
            ->assertNotFound();
    }

    public function test_group_page_keeps_the_requested_group_when_navigation_contains_multiple_groups(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $requestedGroup = Group::forceCreate([
            'name' => 'Requested Group',
            'user_id' => $user->id,
            'invite_key' => 'REQUEST001',
            'public' => false,
        ]);
        $otherGroup = Group::forceCreate([
            'name' => 'Other Group',
            'user_id' => $user->id,
            'invite_key' => 'OTHER00002',
            'public' => false,
        ]);
        $user->groups()->attach([$requestedGroup->id, $otherGroup->id]);

        $this->actingAs($user)
            ->get(route('group.show', $requestedGroup))
            ->assertOk()
            ->assertSee('Requested Group')
            ->assertSee('REQUEST001')
            ->assertSee(route('group.user.index', $requestedGroup), false)
            ->assertSee(route('group.post.store', $requestedGroup), false);
    }

    public function test_assignment_page_keeps_the_post_group_when_navigation_contains_multiple_groups(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $requestedGroup = Group::forceCreate([
            'name' => 'Assignment Group',
            'user_id' => $owner->id,
            'invite_key' => 'ASSIGN0001',
            'public' => false,
        ]);
        $otherGroup = Group::forceCreate([
            'name' => 'Navigation Group',
            'user_id' => $owner->id,
            'invite_key' => 'NAVIGATE01',
            'public' => false,
        ]);
        $owner->groups()->attach([$requestedGroup->id, $otherGroup->id]);
        $post = Post::forceCreate([
            'name' => 'Scoped assignment',
            'content' => 'Keep this assignment scoped to its group.',
            'type' => PostType::Completion->value,
            'deadline' => now()->addDay(),
            'user_id' => $owner->id,
            'group_id' => $requestedGroup->id,
        ]);

        $this->actingAs($owner)
            ->get(route('group.post.show', [$requestedGroup, $post]))
            ->assertOk()
            ->assertSee('Assignment Group')
            ->assertSee('ASSIGN0001')
            ->assertSee(route('group.user.index', $requestedGroup), false)
            ->assertSee('data-livewire-modal="edit-post"', false)
            ->assertSee('data-argument-name="post"', false)
            ->assertSee('data-argument-id="'.$post->id.'"', false)
            ->assertSee('Edit');
    }

    public function test_members_page_keeps_the_requested_group_when_navigation_contains_multiple_groups(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $requestedGroup = Group::forceCreate([
            'name' => 'Members Group',
            'user_id' => $owner->id,
            'invite_key' => 'MEMBER0001',
            'public' => false,
        ]);
        $otherGroup = Group::forceCreate([
            'name' => 'Other Members Group',
            'user_id' => $owner->id,
            'invite_key' => 'MEMBER0002',
            'public' => false,
        ]);
        $owner->groups()->attach([$requestedGroup->id, $otherGroup->id]);

        $this->actingAs($owner)
            ->get(route('group.user.index', $requestedGroup))
            ->assertOk()
            ->assertSee('Members Group')

            ->assertSee(route('group.show', $requestedGroup), false)
            ->assertSee(route('group.user.index', $requestedGroup), false);
    }

    public function test_group_creation_validates_required_and_bounded_input(): void
    {
        $creator = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($creator)
            ->from(route('home'))
            ->post(route('group.store'), [
                'name' => str_repeat('a', 101),
                'public' => 'not-a-boolean',
            ])
            ->assertSessionHasErrors(['name', 'public']);
    }

    public function test_valid_invite_key_joins_group_only_once(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Private group',
            'user_id' => $owner->id,
            'invite_key' => 'JOINKEY001',
            'public' => false,
        ]);

        $this->actingAs($member)->post(route('group.join'), ['invite_key' => $group->invite_key]);
        $this->actingAs($member)->post(route('group.join'), ['invite_key' => $group->invite_key]);

        $this->assertSame(1, $group->users()->whereKey($member->id)->count());
    }

    public function test_invalid_invite_key_fails_validation_without_membership(): void
    {
        $member = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($member)
            ->from(route('home'))
            ->post(route('group.join'), ['invite_key' => 'BADKEY0000'])
            ->assertSessionHasErrors('invite_key');

        $this->assertSame(0, $member->groups()->count());
    }

    public function test_non_member_cannot_view_private_group_members(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Private group',
            'user_id' => $owner->id,
            'invite_key' => 'MEMBERS001',
            'public' => false,
        ]);
        $group->users()->attach($owner);

        $this->actingAs($outsider)
            ->get(route('group.user.index', $group))
            ->assertForbidden();
    }

    public function test_only_owner_can_delete_class_and_related_records_and_files_are_removed(): void
    {
        Storage::fake('submissions');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $member = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Deletable class',
            'user_id' => $owner->id,
            'invite_key' => 'DELETE0001',
            'public' => false,
        ]);
        $group->users()->attach([$owner->id, $member->id]);
        $post = Post::forceCreate([
            'name' => 'ZIP assignment',
            'content' => 'Upload work.',
            'type' => PostType::FileUpload->value,
            'deadline' => now()->addDay(),
            'total_marks' => 100,
            'user_id' => $owner->id,
            'group_id' => $group->id,
        ]);
        $path = 'post-'.$post->id.'/submission.zip';
        Storage::disk('submissions')->put($path, 'content');
        PostUser::create([
            'post_id' => $post->id,
            'user_id' => $member->id,
            'finished' => true,
            'submission_path' => $path,
            'submission_name' => 'submission.zip',
        ]);

        $this->actingAs($member)
            ->delete(route('group.destroy', $group))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('group.destroy', $group))
            ->assertRedirect(route('home'));

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('submissions')->assertMissing($path);
    }
}
