<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_a_valid_profile_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->put(route('user.image.update', $user), [
                'image' => $this->validImage('avatar.png'),
            ])
            ->assertRedirect();

        $filename = $user->fresh()->image;
        $this->assertNotSame('avatar.png', $filename);
        Storage::disk('public')->assertExists('images/'.$filename);
    }

    public function test_executable_content_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $file = UploadedFile::fake()->createWithContent('avatar.php', '<?php echo "unsafe";');

        $this->actingAs($user)
            ->from(route('user.show', $user))
            ->put(route('user.image.update', $user), ['image' => $file])
            ->assertSessionHasErrors('image');

        $this->assertSame([], Storage::disk('public')->allFiles('images'));
    }

    public function test_file_larger_than_two_megabytes_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $file = $this->validImage('large.png', 2_100_000);

        $this->actingAs($user)
            ->from(route('user.show', $user))
            ->put(route('user.image.update', $user), ['image' => $file])
            ->assertSessionHasErrors('image');
    }

    public function test_fake_image_content_is_rejected_even_with_an_image_extension(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $file = UploadedFile::fake()->createWithContent('avatar.png', 'not a real image');

        $this->actingAs($user)
            ->from(route('user.show', $user))
            ->put(route('user.image.update', $user), ['image' => $file])
            ->assertSessionHasErrors('image');
    }

    public function test_user_cannot_replace_another_users_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($otherUser)
            ->put(route('user.image.update', $owner), [
                'image' => $this->validImage('avatar.png'),
            ])
            ->assertForbidden();

        $this->assertNull($owner->fresh()->image);
    }

    public function test_replacing_an_image_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/old-avatar.jpg', 'old image');
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'image' => 'old-avatar.jpg',
        ]);

        $this->actingAs($user)
            ->put(route('user.image.update', $user), [
                'image' => $this->validImage('new-avatar.png'),
            ])
            ->assertRedirect();

        Storage::disk('public')->assertMissing('images/old-avatar.jpg');
        Storage::disk('public')->assertExists('images/'.$user->fresh()->image);
    }

    public function test_replacing_legacy_default_avatar_does_not_delete_shared_asset(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('images/default.svg', '<svg></svg>');
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'image' => 'default.svg',
        ]);

        $this->actingAs($user)
            ->put(route('user.image.update', $user), [
                'image' => $this->validImage('new-avatar.png'),
            ])
            ->assertRedirect();

        Storage::disk('public')->assertExists('images/default.svg');
        Storage::disk('public')->assertExists('images/'.$user->fresh()->image);
    }

    private function validImage(string $originalName, int $paddingBytes = 0): UploadedFile
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'classbridge-image-');
        file_put_contents($temporaryPath, file_get_contents(base_path('tests/Fixtures/profile.png')));

        if ($paddingBytes > 0) {
            file_put_contents($temporaryPath, str_repeat("\0", $paddingBytes), FILE_APPEND);
        }

        return new UploadedFile($temporaryPath, $originalName, 'image/png', null, true);
    }
}
