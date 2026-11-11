<?php

namespace Tests\Feature\Http;

use App\Http\Requests\MarkPostsSeenRequest;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use App\Services\FollowService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SeenPostsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_batch_is_recorded_once_per_post(): void
    {
        $user = User::factory()->create();
        [$first, $second] = Post::factory()->count(2)->create();

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$first->id, $second->id]])->assertOk();
        // Seen again on a later visit: still one row each.
        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$first->id]])->assertOk();

        $this->assertDatabaseCount('post_views', 2);
        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $second->id]);
    }

    public function test_posts_the_user_may_not_see_are_skipped(): void
    {
        $user = User::factory()->create();
        $visible = Post::factory()->create();
        $private = Post::factory()->create(['group_id' => Group::factory()->create(['is_private' => true])->id]);
        $deleted = Post::factory()->create(['is_deleted' => true]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$visible->id, $private->id, $deleted->id, (string) Str::uuid()]])
            ->assertOk();

        $this->assertDatabaseCount('post_views', 1);
        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $visible->id]);
    }

    public function test_a_private_groups_post_counts_for_its_members(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => true]);
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
        $post = Post::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$post->id]])->assertOk();

        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    public function test_a_bad_batch_is_rejected_and_a_guest_is_refused(): void
    {
        $user = User::factory()->create();
        $tooMany = array_map(fn () => (string) Str::uuid(), range(0, MarkPostsSeenRequest::MAX_IDS));

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => []])->assertUnprocessable();
        $this->actingAs($user)->postJson('/posts/seen', ['ids' => ['not-a-uuid']])->assertUnprocessable();
        $this->actingAs($user)->postJson('/posts/seen', ['ids' => $tooMany])->assertUnprocessable();

        $this->app['auth']->forgetGuards();
        $this->postJson('/posts/seen', ['ids' => [(string) Str::uuid()]])->assertUnauthorized();
    }

    public function test_the_answer_is_the_side_panel_with_the_seen_post_no_longer_new(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $this->travel(-1)->days();
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
        $this->travelBack();
        // The factory backdates posts up to a month; these are from after joining.
        [$seen] = Post::factory()->count(2)->create(['group_id' => $group->id, 'created_at' => now()]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$seen->id]])
            ->assertOk()
            ->assertJsonPath('side_panel.groups.0.id', $group->id)
            ->assertJsonPath('side_panel.groups.0.new_posts_count', 1)
            ->assertJsonStructure(['side_panel' => ['groups', 'people', 'version']]);

        // And on the next page that shows the panel: the group page too, not only the home page.
        $this->actingAs($user)->get(route('groups.show', $group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('side_panel.groups.0.new_posts_count', 1));
    }

    public function test_read_all_in_a_group_notes_its_posts_since_joining(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $old = Post::factory()->create(['group_id' => $group->id, 'created_at' => now()->subDays(5)]);
        $this->travel(-1)->days();
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
        $this->travelBack();
        $new = Post::factory()->count(3)->create(['group_id' => $group->id, 'created_at' => now()]);
        $elsewhere = Post::factory()->create(['created_at' => now()]);

        $this->actingAs($user)->postJson("/groups/{$group->id}/seen")
            ->assertOk()
            ->assertJsonPath('side_panel.groups.0.new_posts_count', 0);

        $noted = DB::table('post_views')->where('user_id', $user->id)->pluck('post_id');
        $this->assertEqualsCanonicalizing($new->pluck('id')->all(), $noted->all());
        // Before joining it never counted, so it isn't written either; another group's post isn't touched.
        $this->assertNotContains($old->id, $noted);
        $this->assertNotContains($elsewhere->id, $noted);
    }

    public function test_read_all_notes_nothing_where_the_user_isnt_subscribed(): void
    {
        [$user, $author] = User::factory()->count(2)->create();
        $group = Group::factory()->create();
        Post::factory()->create(['group_id' => $group->id, 'user_id' => $author->id, 'created_at' => now()]);

        $this->actingAs($user)->postJson("/groups/{$group->id}/seen")->assertOk();
        $this->actingAs($user)->postJson("/users/{$author->id}/seen")->assertOk();

        $this->assertDatabaseCount('post_views', 0);
    }

    public function test_read_all_for_a_person_notes_their_visible_posts_since_the_follow(): void
    {
        [$user, $author] = User::factory()->count(2)->create();
        $this->travel(-1)->days();
        app(FollowService::class)->follow($user->id, $author->id);
        $this->travelBack();
        $visible = Post::factory()->create(['user_id' => $author->id, 'created_at' => now()]);
        $hidden = Post::factory()->create([
            'user_id' => $author->id,
            'group_id' => Group::factory()->create(['is_private' => true])->id,
            'created_at' => now(),
        ]);

        $this->actingAs($user)->postJson("/users/{$author->id}/seen")
            ->assertOk()
            ->assertJsonPath('side_panel.people.0.new_posts_count', 0);

        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $visible->id]);
        // A private group's post they can't open stays out, as everywhere else.
        $this->assertDatabaseMissing('post_views', ['user_id' => $user->id, 'post_id' => $hidden->id]);
    }

    public function test_a_vote_on_a_post_makes_it_seen_and_taking_it_back_does_not_undo_that(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)->postJson('/votes', ['target_type' => 'post', 'target_id' => $post->id, 'positive' => true])->assertOk();
        $this->actingAs($user)->postJson('/votes', ['target_type' => 'post', 'target_id' => $post->id, 'positive' => true])->assertOk();

        $this->assertDatabaseCount('votes', 0);
        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $post->id]);
    }
}
