<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Models\Vote;
use App\Repositories\Contracts\UserRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RecommendationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_members_recommendations_skip_their_own_posts_and_their_groups(): void
    {
        $user = User::factory()->create();
        $new = Post::factory()->create(['created_at' => now()]);
        Post::factory()->create(['user_id' => $user->id, 'created_at' => now()]);
        $inMyGroup = Post::factory()->create(['created_at' => now()]);
        $this->join($user, $inMyGroup->group);

        $this->assertSame([$new->id], $this->recommendedFor($user));
    }

    public function test_posts_in_the_users_languages_come_first_then_the_rest(): void
    {
        $user = User::factory()->create();
        $this->app->make(UserRepositoryInterface::class)->syncSpeakingLanguages($user->id, ['uk']);

        // The Japanese post is far more popular, but the Ukrainian one is in a language the user
        // speaks — a priority, so the Japanese one still follows rather than disappearing.
        $japanese = $this->postIn('ja', score: 10);
        $ukrainian = $this->postIn('uk', score: 1);

        $this->assertSame([$ukrainian->id, $japanese->id], $this->recommendedFor($user));
    }

    public function test_within_the_users_languages_the_more_popular_post_comes_first(): void
    {
        $user = User::factory()->create();
        $this->app->make(UserRepositoryInterface::class)->syncSpeakingLanguages($user->id, ['uk', 'en']);

        $quiet = $this->postIn('en', score: 1);
        $popular = $this->postIn('uk', score: 5);

        $this->assertSame([$popular->id, $quiet->id], $this->recommendedFor($user));
    }

    public function test_private_groups_and_old_posts_are_never_recommended(): void
    {
        $user = User::factory()->create();
        Post::factory()->create(['group_id' => Group::factory()->private(), 'created_at' => now()]);
        Post::factory()->create(['created_at' => now()->subDays(8)]);

        $this->assertSame([], $this->recommendedFor($user));
    }

    public function test_a_guest_gets_plain_trending(): void
    {
        $post = Post::factory()->create(['created_at' => now()]);

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('posts.data.0.id', $post->id));
    }

    /** @return list<string> */
    private function recommendedFor(User $user): array
    {
        $ids = [];

        $this->actingAs($user)->get('/')
            ->assertInertia(function (AssertableInertia $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['posts']['data'], 'id');
            });

        return $ids;
    }

    // A recent post in a public group of that language, with `score` upvotes from other users.
    private function postIn(string $languageCode, int $score): Post
    {
        $post = Post::factory()->create([
            'group_id' => Group::factory()->create(['language_code' => $languageCode]),
            'created_at' => now(),
        ]);

        Vote::factory()->onPost($post)->count($score)->create(['positive' => true]);

        return $post;
    }

    private function join(User $user, Group $group): void
    {
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
    }
}
