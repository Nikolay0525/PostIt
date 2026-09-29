<?php

namespace Tests\Feature\Http;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_a_group_page_is_addressed_by_its_slug(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);

        $this->assertSame(url('/groups/home-cooking'), route('groups.show', $group->slug));
        $this->get('/groups/home-cooking')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('group.slug', 'home-cooking'));
    }

    public function test_an_unknown_slug_and_the_old_uuid_url_are_not_found(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);

        $this->get('/groups/no-such-group')->assertNotFound();
        $this->get("/groups/{$group->id}")->assertNotFound();
    }

    public function test_service_pages_under_the_dash_segment_never_collide_with_a_group(): void
    {
        // "create" is a perfectly valid slug; the create page is /groups/-/create, not this.
        Group::factory()->create(['slug' => 'create']);
        $user = User::factory()->create();

        $this->get('/groups/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Groups/Show')->where('group.slug', 'create'));

        $this->actingAs($user)->get('/groups/-/create')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Groups/Create'));
    }

    public function test_a_post_is_addressed_by_group_slug_and_post_slug_in_any_script(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);
        $post = Post::factory()->for($group)->create(['slug' => 'борщ-з-пампушками-a1b2c3']);

        // The browser sends the Cyrillic segment percent-encoded; the router decodes it.
        $this->get('/groups/home-cooking/posts/'.rawurlencode('борщ-з-пампушками-a1b2c3'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Posts/Show')
                ->where('post.id', $post->id)
                ->where('post.slug', 'борщ-з-пампушками-a1b2c3')
                ->where('post.group.slug', 'home-cooking'));
    }

    public function test_a_post_slug_only_resolves_inside_its_own_group(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);
        Group::factory()->create(['slug' => 'retro-gaming']);
        Post::factory()->for($group)->create(['slug' => 'my-post-a1b2c3']);

        $this->get('/groups/retro-gaming/posts/my-post-a1b2c3')->assertNotFound();
        $this->get('/groups/home-cooking/posts/no-such-post')->assertNotFound();
    }

    public function test_a_deleted_post_is_not_found_by_its_slug(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);
        Post::factory()->for($group)->create(['slug' => 'gone-a1b2c3', 'is_deleted' => true]);

        $this->get('/groups/home-cooking/posts/gone-a1b2c3')->assertNotFound();
    }

    public function test_the_random_post_link_redirects_to_the_posts_slug_url(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);
        Post::factory()->for($group)->create(['slug' => 'only-post-a1b2c3']);

        $this->get('/groups/home-cooking/random-post')
            ->assertRedirect('/groups/home-cooking/posts/only-post-a1b2c3');
    }

    public function test_creating_a_post_redirects_to_its_slug_url(): void
    {
        $group = Group::factory()->create(['slug' => 'home-cooking']);
        $user = User::factory()->create();
        $group->members()->attach($user->id, ['created_at' => now()]);

        $this->actingAs($user)->get('/groups/home-cooking/-/create-post')->assertOk();

        $response = $this->actingAs($user)->post('/posts', [
            'group_id' => $group->id,
            'title' => 'Борщ з пампушками',
            'article' => 'Рецепт.',
        ]);

        $post = Post::where('group_id', $group->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^борщ-з-пампушками-[a-z0-9]{6}$/u', $post->slug);
        $response->assertRedirect(route('posts.show', ['home-cooking', $post->slug]));
    }

    public function test_the_same_post_slug_can_exist_in_two_groups_but_not_twice_in_one(): void
    {
        $first = Group::factory()->create();
        $second = Group::factory()->create();

        Post::factory()->for($first)->create(['slug' => 'same-a1b2c3']);
        Post::factory()->for($second)->create(['slug' => 'same-a1b2c3']);

        $this->expectException(UniqueConstraintViolationException::class);
        Post::factory()->for($first)->create(['slug' => 'same-a1b2c3']);
    }
}
