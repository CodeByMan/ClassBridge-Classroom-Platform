<?php

namespace Tests\Feature\Auth;

use App\Models\Group;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_registration_normalizes_email_and_does_not_auto_join_public_groups(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $publicGroup = Group::forceCreate([
            'name' => 'Public group',
            'user_id' => $owner->id,
            'invite_key' => 'REGISTER01',
            'public' => true,
        ]);
        $publicGroup->users()->attach($owner);

        $this->post('/register', [
            'name' => 'Mixed Case User',
            'email' => ' MIXED@EXAMPLE.COM ',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(RouteServiceProvider::HOME);

        $user = User::where('email', 'mixed@example.com')->firstOrFail();
        $this->assertFalse($publicGroup->users()->whereKey($user->id)->exists());
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_succeeds_when_verification_mail_delivery_throws(): void
    {
        Event::listen(Registered::class, static function (): void {
            throw new RuntimeException('Simulated mail transport failure.');
        });

        $this->post('/register', [
            'name' => 'Mail Failure User',
            'email' => 'mail-failure@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(RouteServiceProvider::HOME)
            ->assertSessionHas('warning');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'mail-failure@example.com']);
    }
}
