<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class FallbackAndHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_returns_404_for_authenticated_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get('/this-page-does-not-exist')
            ->assertNotFound();
    }

    public function test_security_headers_are_present(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $contentSecurityPolicy = $this->get(route('welcome'))
            ->headers->get('Content-Security-Policy');

        $this->assertIsString($contentSecurityPolicy);
        $this->assertStringContainsString("script-src 'self' 'unsafe-eval'", $contentSecurityPolicy);
        $this->assertStringContainsString("script-src-attr 'none'", $contentSecurityPolicy);
        $this->assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $contentSecurityPolicy);
    }

    public function test_inline_scripts_receive_exact_csp_hashes_outside_local_environment(): void
    {
        $inlineScript = 'window.classBridgeCspTest = true;';
        $request = Request::create('/csp-test');
        $middleware = new SecurityHeaders;

        $response = $middleware->handle(
            $request,
            fn () => response("<html><body><script>{$inlineScript}</script></body></html>")
        );

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy');
        $expectedHash = base64_encode(hash('sha256', $inlineScript, true));

        $this->assertIsString($contentSecurityPolicy);
        $this->assertStringContainsString("'sha256-{$expectedHash}'", $contentSecurityPolicy);
        $this->assertStringNotContainsString("'unsafe-inline'", $contentSecurityPolicy);
    }

    public function test_every_dashboard_inline_script_is_authorised_by_the_csp(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->get(route('home'))
            ->assertOk();

        $content = $response->getContent();
        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy');

        $this->assertIsString($content);
        $this->assertIsString($contentSecurityPolicy);

        preg_match_all(
            '/<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)<\/script>/is',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches[1], 'Expected framework-managed inline dashboard scripts.');

        foreach ($matches[1] as $script) {
            $hash = base64_encode(hash('sha256', $script, true));
            $this->assertStringContainsString("'sha256-{$hash}'", $contentSecurityPolicy);
        }
    }
}
