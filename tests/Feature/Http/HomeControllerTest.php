<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\SpeakingLanguage;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Services\FollowService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
                ->component('Feed/Index')
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

    public function test_a_guest_gets_no_side_panel(): void
    {
        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('side_panel', null));
    }

    public function test_the_side_panel_shows_every_attached_subscription_and_only_those(): void
    {
        $user = User::factory()->create();

        foreach (['Delta', 'Alpha', 'Charlie', 'Bravo'] as $name) {
            $this->join($user, Group::factory()->create(['name' => $name]));
        }
        UserGroupSubscription::query()
            ->whereIn('group_id', Group::where('name', 'Alpha')->pluck('id'))
            ->update(['attached_on_feed' => false]);

        $author = User::factory()->create();
        app(FollowService::class)->follow($user->id, $author->id);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('side_panel.groups.0.name', 'Bravo')
                ->where('side_panel.groups.1.name', 'Charlie')
                ->where('side_panel.groups.2.name', 'Delta')
                ->has('side_panel.groups', 3)
                ->has('side_panel.people', 1)
                ->where('side_panel.people.0.username', $author->username));
    }

    public function test_a_side_panel_group_carries_its_language_code(): void
    {
        $user = User::factory()->create();
        $this->join($user, Group::factory()->create(['language_code' => SpeakingLanguage::factory()->create(['code' => 'xyz'])->code]));

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('side_panel.groups.0.language_code', 'xyz'));
    }

    public function test_side_panel_groups_with_more_new_posts_come_first_then_by_name(): void
    {
        $user = User::factory()->create();
        $this->travel(-1)->days();
        [$alpha, $bravo, $charlie] = collect(['Alpha', 'Bravo', 'Charlie'])
            ->map(fn (string $name) => tap(Group::factory()->create(['name' => $name]), fn (Group $group) => $this->join($user, $group)));
        // From before joining: not new.
        Post::factory()->count(3)->create(['group_id' => $alpha->id, 'created_at' => now()->subDay()]);
        $this->travelBack();

        // The factory backdates posts up to a month, so these say they're from after joining.
        Post::factory()->count(2)->create(['group_id' => $charlie->id, 'created_at' => now()]);
        Post::factory()->create(['group_id' => $bravo->id, 'created_at' => now()]);
        // The user's own post and one they already opened: not new either.
        Post::factory()->create(['group_id' => $bravo->id, 'user_id' => $user->id, 'created_at' => now()]);
        $seen = Post::factory()->create(['group_id' => $bravo->id, 'created_at' => now()]);
        DB::table('post_views')->insert(['post_id' => $seen->id, 'user_id' => $user->id, 'viewed_at' => now()]);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('side_panel.groups.0.name', 'Charlie')
                ->where('side_panel.groups.0.new_posts_count', 2)
                ->where('side_panel.groups.1.name', 'Bravo')
                ->where('side_panel.groups.1.new_posts_count', 1)
                ->where('side_panel.groups.2.name', 'Alpha')
                ->where('side_panel.groups.2.new_posts_count', 0));
    }

    public function test_a_post_counts_for_both_its_group_and_its_author_until_it_is_read_once(): void
    {
        [$user, $author, $quiet] = User::factory()->count(3)->create();
        $group = Group::factory()->create(['is_private' => false]);
        $hidden = Group::factory()->create(['is_private' => true]);
        $this->travel(-1)->days();
        $this->join($user, $group);
        app(FollowService::class)->follow($user->id, $author->id);
        app(FollowService::class)->follow($user->id, $quiet->id);
        $this->travelBack();

        $post = Post::factory()->create(['group_id' => $group->id, 'user_id' => $author->id, 'created_at' => now()]);
        // In a private group the user isn't in: not counted, it would give away a post they can't open.
        Post::factory()->create(['group_id' => $hidden->id, 'user_id' => $author->id, 'created_at' => now()]);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('side_panel.groups.0.new_posts_count', 1)
                ->where('side_panel.people.0.username', $author->username)
                ->where('side_panel.people.0.new_posts_count', 1)
                ->where('side_panel.people.1.new_posts_count', 0));

        DB::table('post_views')->insert(['post_id' => $post->id, 'user_id' => $user->id, 'viewed_at' => now()]);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('side_panel.groups.0.new_posts_count', 0)
                ->where('side_panel.people.0.new_posts_count', 0));
    }

    private function join(User $user, Group $group): void
    {
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
    }
}
