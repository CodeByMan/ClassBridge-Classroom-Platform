<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_authentication_routes_are_disabled_by_default(): void
    {
        $this->get(route('auth.google'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
    }

    public function test_login_and_registration_pages_show_disabled_google_buttons(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Continue with Google')
            ->assertSee('Coming soon')
            ->assertSee('aria-disabled="true"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Register with Google')
            ->assertSee('Coming soon')
            ->assertSee('aria-disabled="true"', false);
    }
}
