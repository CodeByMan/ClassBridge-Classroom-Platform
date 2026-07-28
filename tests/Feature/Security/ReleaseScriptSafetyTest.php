<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ReleaseScriptSafetyTest extends TestCase
{
    public function test_release_script_preserves_an_existing_application_key(): void
    {
        $script = file_get_contents(base_path('scripts/verify-release.ps1'));

        $this->assertIsString($script);
        $this->assertStringContainsString("Select-String -Path .env -Pattern '^APP_KEY=base64:.+$' -Quiet", $script);
        $this->assertStringNotContainsString('key:generate --force', $script);
    }

    public function test_release_script_checks_each_native_command_and_cleans_temporary_database(): void
    {
        $script = file_get_contents(base_path('scripts/verify-release.ps1'));

        $this->assertIsString($script);
        $this->assertStringContainsString('composer update --with-all-dependencies', $script);
        $this->assertStringContainsString('if (-not (Test-Path package-lock.json)) { npm install }', $script);
        $this->assertStringContainsString('npm ci', $script);
        $this->assertStringContainsString('Invoke-NativeCommand', $script);
        $this->assertStringContainsString('if ($exitCode -ne 0)', $script);
        $this->assertStringContainsString('finally {', $script);
        $this->assertStringContainsString('Remove-Item $testingDb -Force', $script);
        $this->assertStringContainsString('php artisan migrate:fresh --seed --force', $script);
    }
}
