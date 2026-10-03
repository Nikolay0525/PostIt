<?php

namespace Tests\Feature\Http;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\Achievement;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Services\FollowService;
use App\Services\ImageService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    // A 1×1 PNG: UploadedFile::fake()->image() would need the GD extension, which isn't enabled.
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        Storage::fake(ImageService::DISK);
    }

    public function test_anyone_can_see_a_profile_with_public_information_only(): void
    {
        $user = User::factory()->create([
            'username' => 'olena',
            'status_emoji' => '🎮',
            'status_text' => 'Граю в ретро',
            'bio' => 'Люблю старі ігри.',
        ]);
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->get('/users/olena')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Users/Show')
                ->where('profile', [
                    'id' => $user->id,
                    'username' => 'olena',
                    'avatar_url' => null,
                    'status_emoji' => '🎮',
                    'status_text' => 'Граю в ретро',
                    'bio' => 'Люблю старі ігри.',
                    'joined_at' => $user->created_at->toJSON(),
                    'followers_count' => 0,
                ])
                ->where('is_self', false)
                ->where('is_following', false)
                ->where('edit', null)
                ->where('posts.data.0.id', $post->id)
                ->where('posts.data.0.author.username', 'olena'));
    }

    public function test_an_unknown_username_is_a_404(): void
    {
        $this->get('/users/nobody')->assertNotFound();
    }

    public function test_a_username_with_non_latin_letters_and_spaces_resolves(): void
    {
        User::factory()->create(['username' => 'Олена К']);

        $this->get('/users/'.rawurlencode('Олена К'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('profile.username', 'Олена К'));
    }

    public function test_the_viewer_sees_their_own_profile_and_their_follow_state(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $follower = User::factory()->create();
        $this->app->make(FollowService::class)->follow($follower->id, $author->id);

        $this->actingAs($author)->get('/users/author')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('is_self', true)
                ->where('profile.followers_count', 1)
                ->where('edit.status_emojis', UpdateProfileRequest::STATUS_EMOJIS));

        $this->actingAs($follower)->get('/users/author')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('is_self', false)
                ->where('is_following', true)
                ->where('edit', null));
    }

    public function test_private_group_posts_are_shown_to_its_members_only(): void
    {
        $author = User::factory()->create(['username' => 'author']);
        $member = User::factory()->create();
        $group = Group::factory()->private()->create();
        Post::factory()->create(['user_id' => $author->id, 'group_id' => $group->id]);
        $this->join($member, $group);

        $this->get('/users/author')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('posts.data', 0));

        $this->actingAs($member)->get('/users/author')
            ->assertInertia(fn (AssertableInertia $page) => $page->has('posts.data', 1));
    }

    public function test_the_groups_list_hides_private_groups_the_viewer_is_not_in(): void
    {
        $user = User::factory()->create(['username' => 'olena']);
        $viewer = User::factory()->create();
        $public = Group::factory()->create(['name' => 'B public']);
        $sharedPrivate = Group::factory()->private()->create(['name' => 'A shared']);
        $otherPrivate = Group::factory()->private()->create(['name' => 'C other']);
        Group::factory()->create(); // the user isn't in this one
        foreach ([$public, $sharedPrivate, $otherPrivate] as $group) {
            $this->join($user, $group);
        }
        $this->join($viewer, $sharedPrivate);

        $this->get('/users/olena')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups', [['id' => $public->id, 'slug' => $public->slug, 'name' => 'B public', 'is_private' => false]]));

        // Sorted by name.
        $this->actingAs($viewer)->get('/users/olena')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('groups.0.id', $sharedPrivate->id)
                ->where('groups.1.id', $public->id)
                ->has('groups', 2));
    }

    public function test_only_unlocked_achievements_are_shown(): void
    {
        $user = User::factory()->create(['username' => 'olena']);
        $unlocked = Achievement::create(['title' => 'First post', 'description' => 'Publish a post', 'target_property' => 'posts_created', 'target_value' => '1', 'comparison_type' => '>=', 'icon_url' => '']);
        $inProgress = Achievement::create(['title' => 'Ten posts', 'description' => 'Publish ten posts', 'target_property' => 'posts_created', 'target_value' => '10', 'comparison_type' => '>=', 'icon_url' => '']);
        DB::table('user_achievements')->insert([
            ['user_id' => $user->id, 'achievement_id' => $unlocked->id, 'current_value' => '1', 'is_completed' => true, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'achievement_id' => $inProgress->id, 'current_value' => '3', 'is_completed' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->get('/users/olena')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('achievements', 1)
                ->where('achievements.0.title', 'First post'));
    }

    public function test_the_owner_can_set_and_clear_status_and_bio(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/profile', ['status_emoji' => '🎮', 'status_text' => '  Граю  ', 'bio' => 'Про мене'])
            ->assertOk()
            ->assertExactJson(['status_emoji' => '🎮', 'status_text' => 'Граю', 'bio' => 'Про мене']);

        // A partial update leaves the other fields alone; an empty string clears a field.
        $this->actingAs($user)
            ->patchJson('/profile', ['status_text' => ''])
            ->assertExactJson(['status_emoji' => '🎮', 'status_text' => null, 'bio' => 'Про мене']);
    }

    public function test_profile_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/profile', [
                'status_emoji' => 'not an emoji',
                'status_text' => str_repeat('a', UpdateProfileRequest::STATUS_TEXT_MAX_LENGTH + 1),
                'bio' => str_repeat('a', UpdateProfileRequest::BIO_MAX_LENGTH + 1),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status_emoji', 'status_text', 'bio']);

        $this->assertNull($user->fresh()->status_emoji);
    }

    public function test_a_guest_cannot_edit_a_profile(): void
    {
        $this->patchJson('/profile', ['bio' => 'x'])->assertUnauthorized();
        $this->postJson('/profile/avatar', ['avatar' => $this->png()])->assertUnauthorized();
        $this->deleteJson('/profile/avatar')->assertUnauthorized();
    }

    public function test_a_user_can_upload_and_remove_their_avatar(): void
    {
        $user = User::factory()->create(['username' => 'olena']);

        $response = $this->actingAs($user)
            ->postJson('/profile/avatar', ['avatar' => $this->png()])
            ->assertOk();

        $path = $user->fresh()->avatar_url;
        Storage::disk(ImageService::DISK)->assertExists($path);
        // A ready-to-use link, not the stored path — on the profile too.
        $response->assertExactJson(['avatar_url' => ImageService::url($path)]);
        $this->get('/users/olena')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('profile.avatar_url', ImageService::url($path)));

        $this->deleteJson('/profile/avatar')->assertExactJson(['avatar_url' => null]);

        $this->assertNull($user->fresh()->avatar_url);
        Storage::disk(ImageService::DISK)->assertMissing($path);
    }

    public function test_an_avatar_must_be_an_image_of_an_allowed_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/profile/avatar', [])->assertJsonValidationErrors('avatar');

        // A PDF renamed to .png is rejected by its contents, not its name.
        $this->actingAs($user)
            ->postJson('/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('photo.png', "%PDF-1.4\n%fake")])
            ->assertJsonValidationErrors('avatar');

        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_an_avatar_over_the_size_limit_is_rejected(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('photo.png', base64_decode(self::PNG))->size(3000);

        $this->actingAs($user)->postJson('/profile/avatar', ['avatar' => $file])->assertJsonValidationErrors('avatar');
    }

    private function join(User $user, Group $group): void
    {
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('photo.png', base64_decode(self::PNG));
    }
}
