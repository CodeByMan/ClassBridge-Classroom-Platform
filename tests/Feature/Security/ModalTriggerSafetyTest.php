<?php

namespace Tests\Feature\Security;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ModalTriggerSafetyTest extends TestCase
{
    public function test_modal_triggers_pass_only_numeric_model_ids(): void
    {
        $viewFiles = [
            resource_path('views/app/user/show.blade.php'),
            resource_path('views/app/group/index.blade.php'),
            resource_path('views/app/post/show.blade.php'),
        ];

        foreach ($viewFiles as $viewFile) {
            $contents = file_get_contents($viewFile);

            $this->assertIsString($contents);
            $this->assertStringNotContainsString("json_encode(['user' => \$user])", $contents);
            $this->assertStringNotContainsString("json_encode(['group' => \$group])", $contents);
            $this->assertStringNotContainsString("json_encode(['post' => \$post])", $contents);
            $this->assertStringNotContainsString("json_encode(['comment' => \$comment])", $contents);
        }
    }

    public function test_first_party_views_do_not_use_inline_dom_event_handlers(): void
    {
        foreach ($this->bladeFiles() as $viewFile) {
            $contents = file_get_contents($viewFile);

            $this->assertIsString($contents);
            $this->assertDoesNotMatchRegularExpression('/\son(?:click|change|submit|load|error)=/i', $contents, $viewFile);
            $this->assertDoesNotMatchRegularExpression('/<div[^>]+type=["\']button["\']/i', $contents, $viewFile);
        }
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    public function test_known_duplicate_control_ids_are_removed(): void
    {
        $views = file_get_contents(resource_path('views/app/post/show.blade.php'))
            .file_get_contents(resource_path('views/app/group/users.blade.php'))
            .file_get_contents(resource_path('views/app/user/show.blade.php'));

        $this->assertStringNotContainsString('id="dropdownDefault"', $views);
        $this->assertStringNotContainsString('id="finished"', $views);
    }
}
