<?php

namespace Tests\Feature\Http;

use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PostViewsAndSharesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_opening_a_post_records_one_view_per_member(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $url = route('posts.show', [$post->group->slug, $post->slug]);

        $this->actingAs($user)->get($url)->assertOk();
        $this->actingAs($user)->get($url)->assertOk();

        $this->assertDatabaseCount('post_views', 1);
        $this->assertDatabaseHas('post_views', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    public function test_a_guest_view_is_not_recorded(): void
    {
        $post = Post::factory()->create();

        $this->get(route('posts.show', [$post->group->slug, $post->slug]))->assertOk();

        $this->assertDatabaseCount('post_views', 0);
    }

    public function test_sharing_counts_each_member_once(): void
    {
        [$alice, $bob] = User::factory()->count(2)->create();
        $post = Post::factory()->create();

        $this->actingAs($alice)->postJson("/posts/{$post->id}/share")->assertExactJson(['shares_count' => 1]);
        $this->actingAs($alice)->postJson("/posts/{$post->id}/share")->assertExactJson(['shares_count' => 1]);
        $this->actingAs($bob)->postJson("/posts/{$post->id}/share")->assertExactJson(['shares_count' => 2]);
    }

    public function test_the_share_count_reaches_the_feed_and_the_post_page(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['created_at' => now()]);
        $this->actingAs($user)->postJson("/posts/{$post->id}/share");

        $this->get('/')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('posts.data.0.shares_count', 1));
        $this->get(route('posts.show', [$post->group->slug, $post->slug]))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('post.shares_count', 1));
    }

    public function test_a_guest_share_is_not_counted(): void
    {
        $post = Post::factory()->create();

        $this->postJson("/posts/{$post->id}/share")->assertUnauthorized();

        $this->assertDatabaseCount('post_shares', 0);
    }

    public function test_sharing_a_missing_or_deleted_post_is_a_404(): void
    {
        $user = User::factory()->create();
        $deleted = Post::factory()->create(['is_deleted' => true]);

        $this->actingAs($user)->postJson('/posts/'.Str::uuid().'/share')->assertNotFound();
        $this->actingAs($user)->postJson("/posts/{$deleted->id}/share")->assertNotFound();
    }
}
