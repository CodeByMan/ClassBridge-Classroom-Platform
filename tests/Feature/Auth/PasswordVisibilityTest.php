<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_has_an_accessible_password_visibility_control(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('aria-label="Show password"', false)
            ->assertSee('data-password-show-icon', false)
            ->assertSee('data-password-hide-icon', false)
            ->assertSee('data-password-visible="false"', false);
    }

    public function test_registration_page_has_visibility_controls_for_both_password_fields(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_password_reset_and_confirmation_pages_use_visibility_controls(): void
    {
        $this->get(route('password.reset', [
            'token' => 'classbridge-test-token',
            'email' => 'student@classbridge.test',
        ]))
            ->assertOk()
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);

        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('data-password-toggle="password"', false);
    }

    public function test_successful_login_redirects_to_a_rendered_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/home');

        $this->assertAuthenticatedAs($user);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Classes');
    }
}
