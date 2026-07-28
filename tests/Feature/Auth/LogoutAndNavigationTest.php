<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_logout_control_is_a_submit_button(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('type="submit"', false);
    }

    public function test_account_trigger_shows_the_authenticated_users_identity(): void
    {
        $user = User::factory()->create([
            'name' => 'Aisha Khan',
            'email' => 'aisha@example.test',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('gc-account-trigger', false)
            ->assertSee('Aisha Khan')
            ->assertSee('aisha@example.test');
    }

    public function test_home_navigation_does_not_repeat_the_classbridge_brand(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(1, substr_count((string) $response->getContent(), 'gc-brand__title'));
        $response->assertDontSee('gc-context-label">ClassBridge', false);
    }
}
