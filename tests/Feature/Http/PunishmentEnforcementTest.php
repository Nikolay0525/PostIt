<?php

namespace Tests\Feature\Http;

use App\Enums\SanctionKind;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\Punishment;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * A mute keeps a member from contributing in the group; a ban keeps them from that and from joining.
 * Both are only in that group, and only while they last.
 */
class PunishmentEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->group = Group::factory()->create(['is_private' => false]);
        $this->member = User::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($this->group->id, $this->member->id);
    }

    private function punish(SanctionKind $kind, ?\DateTimeInterface $endsAt = null, ?Group $group = null, array $extra = []): Punishment
    {
        return Punishment::create([
            'user_id' => $this->member->id,
            'group_id' => ($group ?? $this->group)->id,
            'kind' => $kind,
            'step' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => $endsAt ?? now()->addDay(),
        ] + $extra);
    }

    private function somebodysPost(): Post
    {
        return Post::factory()->create(['group_id' => $this->group->id]);
    }

    public function test_an_unpunished_member_can_take_part(): void
    {
        $post = $this->somebodysPost();

        $this->assertTrue($this->member->can('create', [Post::class, $this->group]));
        $this->assertTrue($this->member->can('create', [Comment::class, $post]));
        $this->assertTrue($this->member->can('vote', $post));
    }

    public function test_a_muted_member_can_read_and_report_but_not_post_comment_or_vote(): void
    {
        $this->punish(SanctionKind::Mute);
        $post = $this->somebodysPost();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $own = Post::factory()->create(['group_id' => $this->group->id, 'user_id' => $this->member->id]);

        $this->assertFalse($this->member->can('create', [Post::class, $this->group]));
        $this->assertFalse($this->member->can('create', [Comment::class, $post]));
        $this->assertFalse($this->member->can('vote', $post));
        $this->assertFalse($this->member->can('vote', $comment));
        $this->assertFalse($this->member->can('update', $own));

        $this->assertTrue($this->member->can('view', $post));
        $this->assertTrue($this->member->can('report', $post));
        $this->assertTrue($this->member->can('report', $comment));
    }

    public function test_a_muted_member_is_refused_by_the_server_not_only_hidden_in_the_page(): void
    {
        $this->punish(SanctionKind::Mute);
        $post = $this->somebodysPost();

        $this->actingAs($this->member)->postJson('/comments', ['post_id' => $post->id, 'text' => 'Hello'])->assertForbidden();
        $this->actingAs($this->member)->postJson('/votes', ['target_type' => 'post', 'target_id' => $post->id, 'positive' => true])->assertForbidden();
    }

    public function test_a_banned_member_cannot_contribute_and_cannot_join(): void
    {
        $this->punish(SanctionKind::Ban);
        $stranger = User::factory()->create();
        $other = $this->member;
        $post = $this->somebodysPost();

        $this->assertFalse($other->can('create', [Post::class, $this->group]));
        $this->assertFalse($other->can('vote', $post));

        app(GroupRepositoryInterface::class)->unsubscribe($this->group->id, $other->id);
        $this->assertFalse($other->can('subscribe', $this->group));
        $this->assertTrue($stranger->can('subscribe', $this->group));
    }

    public function test_a_punishment_is_only_in_its_own_group_and_only_while_it_lasts(): void
    {
        $elsewhere = Group::factory()->create();
        app(GroupRepositoryInterface::class)->subscribe($elsewhere->id, $this->member->id);
        $this->punish(SanctionKind::Mute);

        $this->assertTrue($this->member->can('create', [Post::class, $elsewhere]));

        $this->travel(2)->days();
        $this->assertTrue($this->member->can('create', [Post::class, $this->group]));
    }

    public function test_a_punishment_taken_back_no_longer_binds_and_a_permanent_one_always_does(): void
    {
        $lifted = $this->punish(SanctionKind::Mute, extra: ['lifted_at' => now()]);
        $this->assertTrue($this->member->can('create', [Post::class, $this->group]));

        $lifted->delete();
        $this->punish(SanctionKind::Mute, endsAt: null);
        Punishment::query()->update(['ends_at' => null]);
        $this->travel(3650)->days();

        $this->assertFalse($this->member->can('create', [Post::class, $this->group]));
    }

    public function test_the_pages_tell_the_punished_member_and_nobody_else(): void
    {
        $this->punish(SanctionKind::Mute);
        $post = $this->somebodysPost();

        $this->actingAs($this->member)->get(route('groups.show', $this->group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('restriction.kind', 'mute')
                ->has('restriction.until'));

        $this->actingAs($this->member)->get(route('posts.show', [$this->group->slug, $post->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('restriction.kind', 'mute'));

        $this->actingAs(User::factory()->create())->get(route('groups.show', $this->group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('restriction', null));
        $this->get(route('groups.show', $this->group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('restriction', null));
    }

    public function test_a_ban_closes_the_groups_content_but_keeps_the_subscription(): void
    {
        $post = $this->somebodysPost();
        $this->punish(SanctionKind::Ban);

        // Still subscribed: a ban is not a removal.
        $this->assertTrue(app(GroupRepositoryInterface::class)->isMember($this->group->id, $this->member->id));

        $this->actingAs($this->member)->get(route('groups.show', $this->group->slug))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('restriction.kind', 'ban')
                ->where('posts', null)
                ->where('is_member', true));

        $this->actingAs($this->member)->get(route('posts.show', [$this->group->slug, $post->slug]))
            ->assertRedirect(route('groups.show', $this->group->slug));
        $this->actingAs($this->member)->get(route('groups.random_post', $this->group->slug))
            ->assertRedirect(route('groups.show', $this->group->slug));
        $this->assertFalse($this->member->can('view', $post));
    }

    public function test_a_ban_closes_only_its_own_group_and_a_mute_closes_nothing_to_read(): void
    {
        $post = $this->somebodysPost();
        $elsewhere = Post::factory()->create();
        $this->punish(SanctionKind::Ban);

        $this->actingAs($this->member)->get(route('posts.show', [$elsewhere->group->slug, $elsewhere->slug]))->assertOk();

        Punishment::query()->update(['kind' => SanctionKind::Mute]);
        $this->actingAs($this->member)->get(route('posts.show', [$this->group->slug, $post->slug]))->assertOk();
        $this->actingAs($this->member)->get(route('groups.show', $this->group->slug))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('posts'));
    }

    public function test_a_banned_members_lists_leave_out_the_posts_of_the_group_that_banned_them(): void
    {
        $author = User::factory()->create();
        $closed = Post::factory()->create(['group_id' => $this->group->id, 'user_id' => $author->id, 'created_at' => now(), 'title' => 'Findable closed']);
        $open = Post::factory()->create(['user_id' => $author->id, 'created_at' => now(), 'title' => 'Findable open']);
        \Illuminate\Support\Facades\DB::table('user_user_subscriptions')->insert(['user_follower_id' => $this->member->id, 'user_author_id' => $author->id, 'created_at' => now()]);
        $ids = fn (string $url) => collect($this->actingAs($this->member)->get($url)->viewData('page')['props']['posts']['data'])->pluck('id')->all();

        // Before the ban the post is on the list, so that its absence afterwards means something.
        $this->assertContains($closed->id, $ids('/?feed=following&source=groups'));
        $this->assertContains($closed->id, $ids('/search?q=Findable&tab=posts'));
        $this->punish(SanctionKind::Ban);

        foreach (['/?feed=following&source=groups', '/?feed=following&source=people', '/?feed=recommended', '/search?q=Findable&tab=posts'] as $url) {
            $this->assertNotContains($closed->id, $ids($url), $url);
        }
        $this->assertContains($open->id, $ids('/?feed=following&source=people'));

        // The author's page, too: the viewer sees none of that group's posts there.
        $profile = $this->actingAs($this->member)->get(route('users.show', $author->username))->viewData('page')['props'];
        $this->assertNotContains($closed->id, collect($profile['posts']['data'] ?? [])->pluck('id')->all());
    }

    public function test_a_group_the_member_is_banned_from_has_nothing_new_in_their_side_panel(): void
    {
        $this->travel(-1)->days();
        app(GroupRepositoryInterface::class)->subscribe($this->group->id, $this->member->id);
        $this->travelBack();
        Post::factory()->create(['group_id' => $this->group->id, 'created_at' => now()]);

        $count = fn () => $this->actingAs($this->member)->get('/')->viewData('page')['props']['side_panel']['groups'][0]['new_posts_count'];
        $this->assertSame(1, $count());

        $this->punish(SanctionKind::Ban);

        $this->assertSame(0, $count());
    }
}
