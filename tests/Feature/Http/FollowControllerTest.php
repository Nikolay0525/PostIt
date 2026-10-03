<?php

namespace Tests\Feature\Http;

use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FollowControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_verified_user_can_follow_and_unfollow_an_author(): void
    {
        [$follower, $author] = User::factory()->count(2)->create();

        $this->actingAs($follower)->postJson("/users/{$author->id}/follow")
            ->assertOk()
            ->assertExactJson(['is_following' => true, 'followers_count' => 1]);

        $this->assertDatabaseHas('user_user_subscriptions', ['user_follower_id' => $follower->id, 'user_author_id' => $author->id]);

        $this->actingAs($follower)->deleteJson("/users/{$author->id}/follow")
            ->assertOk()
            ->assertExactJson(['is_following' => false, 'followers_count' => 0]);

        $this->assertDatabaseMissing('user_user_subscriptions', ['user_follower_id' => $follower->id]);
    }

    public function test_following_twice_is_harmless(): void
    {
        [$follower, $author] = User::factory()->count(2)->create();

        $this->actingAs($follower)->postJson("/users/{$author->id}/follow");
        $this->actingAs($follower)->postJson("/users/{$author->id}/follow")
            ->assertOk()
            ->assertJson(['followers_count' => 1]);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson("/users/{$user->id}/follow")->assertForbidden();

        $this->assertDatabaseCount('user_user_subscriptions', 0);
    }

    public function test_an_unverified_user_cannot_follow(): void
    {
        $user = User::factory()->unverified()->create();
        $author = User::factory()->create();

        $this->actingAs($user)->postJson("/users/{$author->id}/follow")->assertForbidden();
    }

    public function test_a_guest_cannot_follow(): void
    {
        $author = User::factory()->create();

        $this->postJson("/users/{$author->id}/follow")->assertUnauthorized();
    }

    public function test_following_an_unknown_user_is_a_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/users/'.Str::uuid().'/follow')->assertNotFound();
        $this->actingAs($user)->deleteJson('/users/'.Str::uuid().'/follow')->assertNotFound();
    }
}
