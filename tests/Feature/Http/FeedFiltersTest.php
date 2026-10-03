<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Models\Vote;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\FollowService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FeedFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_without_filters_nothing_is_narrowed_and_the_defaults_are_reported(): void
    {
        $user = User::factory()->create();
        $opened = $this->makePost();
        $this->openPost($user, $opened);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['new' => false, 'langs' => false, 'period' => 'all'])
                ->has('posts.data', 1));
    }

    public function test_only_new_hides_posts_the_user_opened_or_voted_on(): void
    {
        $user = User::factory()->create();
        $opened = $this->makePost();
        $voted = $this->makePost();
        $unseen = $this->makePost();
        $this->openPost($user, $opened);
        Vote::factory()->onPost($voted)->create(['user_id' => $user->id, 'positive' => false]);

        $this->assertSame([$unseen->id], $this->ids($user, '/?new=1'));
    }

    public function test_only_my_languages_is_a_strict_filter(): void
    {
        $user = User::factory()->create();
        $this->app->make(UserRepositoryInterface::class)->syncSpeakingLanguages($user->id, ['uk']);
        $ukrainian = $this->makePost('uk');
        $this->makePost('ja');

        $this->assertSame([$ukrainian->id], $this->ids($user, '/?langs=1'));
    }

    public function test_the_period_drops_older_posts(): void
    {
        $user = User::factory()->create();
        $week = $this->makePost(daysAgo: 2);
        $month = $this->makePost(daysAgo: 20);
        $this->makePost(daysAgo: 60);

        $this->assertSame([$week->id], $this->ids($user, '/?period=week'));
        $this->assertSame([$week->id, $month->id], $this->ids($user, '/?period=month'));
    }

    public function test_filters_combine(): void
    {
        $user = User::factory()->create();
        $this->app->make(UserRepositoryInterface::class)->syncSpeakingLanguages($user->id, ['uk']);
        $match = $this->makePost('uk', daysAgo: 1);
        $this->openPost($user, $this->makePost('uk', daysAgo: 1)); // seen
        $this->makePost('ja', daysAgo: 1);                         // other language
        $this->makePost('uk', daysAgo: 40);                        // too old

        $this->assertSame([$match->id], $this->ids($user, '/?new=1&langs=1&period=month'));
    }

    public function test_a_guest_gets_only_the_period_filter(): void
    {
        $recent = $this->makePost(daysAgo: 1);
        $this->makePost(daysAgo: 60);

        $this->get('/?new=1&langs=1&period=week')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('filters', ['new' => false, 'langs' => false, 'period' => 'week'])
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $recent->id));
    }

    public function test_an_unknown_period_falls_back_to_all_time(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/?period=forever')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.period', 'all'));
    }

    public function test_filters_never_bring_back_own_posts_or_joined_groups(): void
    {
        // Those stay in Following whatever the filters say.
        $user = User::factory()->create();
        $this->makePost(attributes: ['user_id' => $user->id]);

        $this->assertSame([], $this->ids($user, '/?period=all'));
    }

    public function test_the_filters_narrow_following_groups_too_keeping_newest_first(): void
    {
        $user = User::factory()->create();
        $older = $this->makePost(daysAgo: 3);
        $newer = $this->makePost(daysAgo: 1);
        $seen = $this->makePost(daysAgo: 2);
        $old = $this->makePost(daysAgo: 40);
        foreach ([$older, $newer, $seen, $old] as $post) {
            $this->join($user, $post->group);
        }
        $this->openPost($user, $seen);

        $this->assertSame([$newer->id, $older->id], $this->ids($user, '/?feed=following&source=groups&new=1&period=month'));
    }

    public function test_the_filters_narrow_following_people_too(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $this->app->make(UserRepositoryInterface::class)->syncSpeakingLanguages($user->id, ['uk']);
        $this->app->make(FollowService::class)->follow($user->id, $author->id);
        $ukrainian = $this->makePost('uk', attributes: ['user_id' => $author->id]);
        $this->makePost('ja', attributes: ['user_id' => $author->id]);

        $this->assertSame([$ukrainian->id], $this->ids($user, '/?feed=following&source=people&langs=1'));
    }

    public function test_the_filters_are_reported_on_every_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/?feed=following&source=people&new=1&period=week')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('feed', 'following')
                ->where('filters', ['new' => true, 'langs' => false, 'period' => 'week']));
    }

    /** @return list<string> */
    private function ids(User $user, string $url): array
    {
        $ids = [];

        $this->actingAs($user)->get($url)
            ->assertInertia(function (AssertableInertia $page) use (&$ids) {
                $ids = array_column($page->toArray()['props']['posts']['data'], 'id');
            });

        return $ids;
    }

    private function makePost(string $languageCode = 'ja', int $daysAgo = 0, array $attributes = []): Post
    {
        return Post::factory()->create([
            'group_id' => Group::factory()->create(['language_code' => $languageCode]),
            'created_at' => now()->subDays($daysAgo),
            ...$attributes,
        ]);
    }

    private function join(User $user, Group $group): void
    {
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
    }

    private function openPost(User $user, Post $post): void
    {
        $this->actingAs($user)->get(route('posts.show', [$post->group->slug, $post->slug]))->assertOk();
    }
}
