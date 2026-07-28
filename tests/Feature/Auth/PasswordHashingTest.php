<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordHashingTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_passwords_are_hashed_once(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->assertTrue(Hash::check('secret-password', $user->password));
    }

    public function test_existing_password_hashes_are_not_hashed_again(): void
    {
        $hash = Hash::make('secret-password', ['rounds' => 10]);

        $user = User::factory()->create(['password' => $hash]);

        $this->assertSame($hash, $user->password);
        $this->assertTrue(Hash::check('secret-password', $user->password));
    }
}
