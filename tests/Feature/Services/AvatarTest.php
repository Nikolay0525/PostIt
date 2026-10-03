<?php

namespace Tests\Feature\Services;

use App\Enums\ImageOwnerType;
use App\Models\Image;
use App\Models\User;
use App\Services\ImageService;
use App\Services\ProfileService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    // A 1×1 PNG: UploadedFile::fake()->image() would need the GD extension, which isn't enabled.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private ProfileService $profiles;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        Storage::fake(ImageService::DISK);
        $this->profiles = $this->app->make(ProfileService::class);
    }

    public function test_uploading_an_avatar_stores_the_file_and_records_the_image(): void
    {
        $user = User::factory()->create();

        $user = $this->profiles->updateAvatar($user, $this->png());

        $image = Image::sole();
        $this->assertSame($user->id, $image->uploader_id);
        $this->assertSame(ImageOwnerType::User, $image->owner_type);
        $this->assertSame($user->id, $image->owner_id);
        $this->assertSame('png', $image->file_extension);
        $this->assertSame("avatars/{$image->file_name}.png", $image->url);

        // The user points at the same file, by its path on the disk.
        $this->assertSame($image->url, $user->avatar_url);
        Storage::disk(ImageService::DISK)->assertExists($image->url);
    }

    public function test_replacing_an_avatar_removes_the_previous_one(): void
    {
        $user = User::factory()->create();
        $user = $this->profiles->updateAvatar($user, $this->png());
        $oldPath = $user->avatar_url;

        $user = $this->profiles->updateAvatar($user, $this->png());

        $this->assertNotSame($oldPath, $user->avatar_url);
        $this->assertSame($user->avatar_url, Image::sole()->url);
        Storage::disk(ImageService::DISK)->assertMissing($oldPath);
        Storage::disk(ImageService::DISK)->assertExists($user->avatar_url);
    }

    public function test_removing_an_avatar_goes_back_to_the_default(): void
    {
        $user = User::factory()->create();
        $user = $this->profiles->updateAvatar($user, $this->png());
        $path = $user->avatar_url;

        $user = $this->profiles->removeAvatar($user);

        $this->assertNull($user->avatar_url);
        $this->assertSame(0, Image::count());
        Storage::disk(ImageService::DISK)->assertMissing($path);
    }

    public function test_removing_when_there_is_no_avatar_does_nothing(): void
    {
        $user = User::factory()->create();

        $user = $this->profiles->removeAvatar($user);

        $this->assertNull($user->avatar_url);
    }

    public function test_one_users_avatar_does_not_touch_anothers(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $bob = $this->profiles->updateAvatar($bob, $this->png());

        $this->profiles->updateAvatar($alice, $this->png());
        $this->profiles->removeAvatar($alice);

        $this->assertSame($bob->avatar_url, Image::sole()->url);
        Storage::disk(ImageService::DISK)->assertExists($bob->avatar_url);
    }

    public function test_the_stored_path_becomes_a_public_url(): void
    {
        $this->assertNull(ImageService::url(null));
        $this->assertStringEndsWith('/storage/avatars/a.png', ImageService::url('avatars/a.png'));
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(self::PNG));
    }
}
