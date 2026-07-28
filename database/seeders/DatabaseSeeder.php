<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(
                'ClassBridge demo data may only be seeded in local or testing environments.'
            );
        }

        $this->call(ClassBridgeDemoSeeder::class);
    }
}
