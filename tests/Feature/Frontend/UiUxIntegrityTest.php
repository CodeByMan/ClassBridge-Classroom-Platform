<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiUxIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_publish_complete_favicon_metadata(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('favicon.ico', false)
            ->assertSee('favicon-32x32.png', false)
            ->assertSee('apple-touch-icon.png', false)
            ->assertSee('site.webmanifest', false);
    }

    public function test_dashboard_uses_responsive_account_and_submission_contracts(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('gc-account-trigger', false)
            ->assertSee('gc-account-trigger__identity', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($css);
        $this->assertStringContainsString('.gc-file-name', $css);
        $this->assertStringContainsString('overflow-wrap: anywhere', $css);
        $this->assertStringContainsString('.gc-submission-grid--files', $css);
    }
}
