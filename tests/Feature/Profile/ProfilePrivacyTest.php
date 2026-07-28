<?php

namespace Tests\Feature\Profile;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_unrelated_user_cannot_enumerate_another_profile(): void
    {
        $viewer = User::factory()->create(['email_verified_at' => now()]);
        $profile = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($viewer)
            ->get(route('user.show', $profile))
            ->assertForbidden();
    }

    public function test_shared_group_member_sees_limited_profile_without_email_or_private_group_count(): void
    {
        $viewer = User::factory()->create(['email_verified_at' => now()]);
        $profile = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Shared group',
            'user_id' => $viewer->id,
            'invite_key' => 'SHARED0001',
            'public' => false,
        ]);
        $group->users()->attach([$viewer->id, $profile->id]);

        $this->actingAs($viewer)
            ->get(route('user.show', $profile))
            ->assertOk()
            ->assertSee($profile->name)
            ->assertDontSee($profile->email)
            ->assertDontSee('private group');
    }

    public function test_owner_can_see_own_private_profile_details(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('user.show', $user))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('private group');
    }
}
