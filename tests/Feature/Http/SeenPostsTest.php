<?php

namespace Tests\Feature\Http;

use App\Http\Requests\MarkPostsSeenRequest;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$first->id, $second->id]])->assertNoContent();
        // Seen again on a later visit: still one row each.
        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$first->id]])->assertNoContent();

        $this->assertDatabaseCount('post_impressions', 2);
        $this->assertDatabaseHas('post_impressions', ['user_id' => $user->id, 'post_id' => $second->id]);
        // Scrolling past is not opening: no view.
        $this->assertDatabaseCount('post_views', 0);
    }

    public function test_posts_the_user_may_not_see_are_skipped(): void
    {
        $user = User::factory()->create();
        $visible = Post::factory()->create();
        $private = Post::factory()->create(['group_id' => Group::factory()->create(['is_private' => true])->id]);
        $deleted = Post::factory()->create(['is_deleted' => true]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$visible->id, $private->id, $deleted->id, (string) Str::uuid()]])
            ->assertNoContent();

        $this->assertDatabaseCount('post_impressions', 1);
        $this->assertDatabaseHas('post_impressions', ['user_id' => $user->id, 'post_id' => $visible->id]);
    }

    public function test_a_private_groups_post_counts_for_its_members(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['is_private' => true]);
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
        $post = Post::factory()->create(['group_id' => $group->id]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$post->id]])->assertNoContent();

        $this->assertDatabaseHas('post_impressions', ['user_id' => $user->id, 'post_id' => $post->id]);
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

    public function test_a_seen_post_stops_counting_as_new_on_the_side_panel(): void
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $this->travel(-1)->days();
        UserGroupSubscription::query()->insert(['user_id' => $user->id, 'group_id' => $group->id, 'created_at' => now()]);
        $this->travelBack();
        // The factory backdates posts up to a month; these are from after joining.
        [$seen, $unseen] = Post::factory()->count(2)->create(['group_id' => $group->id, 'created_at' => now()]);

        $this->actingAs($user)->postJson('/posts/seen', ['ids' => [$seen->id]]);

        $this->actingAs($user)->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('side_panel.groups.0.new_posts_count', 1));
    }
}
