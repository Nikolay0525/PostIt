<?php

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\FollowService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FollowServiceTest extends TestCase
{
    use RefreshDatabase;

    private FollowService $follows;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->follows = $this->app->make(FollowService::class);
    }

    public function test_a_user_can_follow_and_unfollow_an_author(): void
    {
        [$follower, $author] = User::factory()->count(2)->create();

        $this->follows->follow($follower->id, $author->id);

        $this->assertTrue($this->follows->isFollowing($follower->id, $author->id));
        // One-directional: the author does not follow back.
        $this->assertFalse($this->follows->isFollowing($author->id, $follower->id));

        $this->follows->unfollow($follower->id, $author->id);

        $this->assertFalse($this->follows->isFollowing($follower->id, $author->id));
    }

    public function test_following_twice_keeps_a_single_link(): void
    {
        [$follower, $author] = User::factory()->count(2)->create();

        $this->follows->follow($follower->id, $author->id);
        $this->follows->follow($follower->id, $author->id);

        $this->assertSame(1, $this->follows->followersCount($author->id));
    }

    public function test_unfollowing_someone_not_followed_does_nothing(): void
    {
        [$follower, $author] = User::factory()->count(2)->create();

        $this->follows->unfollow($follower->id, $author->id);

        $this->assertFalse($this->follows->isFollowing($follower->id, $author->id));
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        try {
            $this->follows->follow($user->id, $user->id);
            $this->fail('Following oneself should be refused.');
        } catch (InvalidArgumentException) {
            $this->assertSame(0, $this->follows->followersCount($user->id));
        }
    }

    public function test_a_guest_follows_no_one(): void
    {
        $author = User::factory()->create();

        $this->assertFalse($this->follows->isFollowing(null, $author->id));
    }

    public function test_followers_are_counted_per_author(): void
    {
        [$alice, $bob, $carol] = User::factory()->count(3)->create();

        $this->follows->follow($alice->id, $carol->id);
        $this->follows->follow($bob->id, $carol->id);
        $this->follows->follow($carol->id, $alice->id);

        $this->assertSame(2, $this->follows->followersCount($carol->id));
        $this->assertSame(1, $this->follows->followersCount($alice->id));
        $this->assertSame(0, $this->follows->followersCount($bob->id));
    }
}
