<?php

declare(strict_types=1);

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class CompiledAssetIntegrityTest extends TestCase
{
    public function test_compiled_frontend_contains_the_password_visibility_feature(): void
    {
        $manifestPath = public_path('build/manifest.json');

        $this->assertFileExists(
            $manifestPath,
            'The Vite manifest is missing. Run npm ci and npm run build before release verification.'
        );

        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $cssFile = $manifest['resources/css/app.css']['file'] ?? null;
        $jsFile = $manifest['resources/js/app.js']['file'] ?? null;

        $this->assertIsString($cssFile);
        $this->assertIsString($jsFile);

        $cssPath = public_path('build/'.$cssFile);
        $jsPath = public_path('build/'.$jsFile);

        $this->assertFileExists($cssPath);
        $this->assertFileExists($jsPath);
        $this->assertStringContainsString('.gc-password-field', (string) file_get_contents($cssPath));
        $this->assertStringContainsString('data-password-toggle', (string) file_get_contents($jsPath));
        $this->assertStringContainsString('passwordVisible', (string) file_get_contents($jsPath));
        $this->assertStringContainsString('.gc-account-trigger', (string) file_get_contents($cssPath));
    }
}
