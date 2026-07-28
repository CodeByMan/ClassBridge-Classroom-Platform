<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use Tests\TestCase;

class DatabaseReadinessRegistrationTest extends TestCase
{
    public function test_database_readiness_middleware_is_registered_in_the_web_stack(): void
    {
        $kernel = file_get_contents(base_path('app/Http/Kernel.php'));

        $this->assertIsString($kernel);
        $this->assertStringContainsString('EnsureDatabaseIsReady::class', $kernel);
    }

    public function test_setup_required_view_contains_the_recovery_command(): void
    {
        $view = file_get_contents(resource_path('views/errors/setup-required.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('scripts\\setup-local.ps1', $view);
    }
}
