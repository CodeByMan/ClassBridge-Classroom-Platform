<?php

namespace Tests\Feature\Posts;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostTypeConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_an_unknown_assignment_type(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $group = Group::forceCreate([
            'name' => 'Constraint group',
            'user_id' => $owner->id,
            'invite_key' => 'TYPECHECK1',
            'public' => false,
        ]);

        $this->expectException(QueryException::class);

        DB::table('posts')->insert([
            'name' => 'Invalid assignment',
            'type' => 9,
            'user_id' => $owner->id,
            'group_id' => $group->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
