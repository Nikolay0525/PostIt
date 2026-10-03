<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Services\FollowService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_recommended_is_the_default_tab_for_guests_and_members(): void
    {
        $post = Post::factory()->create(['created_at' => now()]);
        $member = User::factory()->create();
        $this->join($member, $post->group);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Home')
                ->where('feed', 'recommended')
                ->where('source', 'groups')
                ->where('posts.data.0.id', $post->id));

        // Having subscriptions no longer switches the default.
        $this->actingAs($member)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('feed', 'recommended'));
    }

    public function test_unknown_tab_values_fall_back_to_the_defaults(): void
    {
        $this->get('/?feed=nonsense&source=nonsense')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('feed', 'recommended')
                ->where('source', 'groups'));
    }

    public function test_a_guest_on_the_following_tab_gets_no_posts(): void
    {
        Post::factory()->create();

        $this->get('/?feed=following')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('feed', 'following')
                ->where('posts', null));
    }

    public function test_following_groups_shows_posts_from_the_users_groups_only(): void
    {
        $user = User::factory()->create();
        $mine = Post::factory()->create();
        Post::factory()->create(); // a group the user isn't in
        $this->join($user, $mine->group);

        $this->actingAs($user)->get('/?feed=following&source=groups')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('source', 'groups')
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $mine->id));
    }

    public function test_following_people_shows_posts_by_followed_authors_newest_first(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $older = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()->subDay()]);
        $newer = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()]);
        Post::factory()->create(); // someone not followed
        $this->app->make(FollowService::class)->follow($user->id, $author->id);

        $this->actingAs($user)->get('/?feed=following&source=people')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('source', 'people')
                ->has('posts.data', 2)
                ->where('posts.data.0.id', $newer->id)
                ->where('posts.data.1.id', $older->id));
    }

    public function test_following_people_hides_private_groups_the_follower_is_not_in(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $shared = Group::factory()->private()->create();
        $inShared = Post::factory()->create(['user_id' => $author->id, 'group_id' => $shared->id]);
        Post::factory()->create(['user_id' => $author->id, 'group_id' => Group::factory()->private()]);
        $this->join($user, $shared);
        $this->app->make(FollowService::class)->follow($user->id, $author->id);

        $this->actingAs($user)->get('/?feed=following&source=people')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $inShared->id));
    }

    public function test_a_followed_authors_post_in_the_users_group_shows_in_both_lists(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $this->join($user, $post->group);
        $this->app->make(FollowService::class)->follow($user->id, $author->id);

        foreach (['groups', 'people'] as $source) {
            $this->actingAs($user)->get("/?feed=following&source={$source}")
                ->assertInertia(fn (AssertableInertia $page) => $page
                    ->has('posts.data', 1)
                    ->where('posts.data.0.id', $post->id));
        }
    }

    private function join(User $user, Group $group): void
    {
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
    }
}
