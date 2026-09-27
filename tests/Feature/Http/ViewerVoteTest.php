<?php

namespace Tests\Feature\Http;

use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewerVoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_the_post_page_reports_the_current_viewers_own_vote(): void
    {
        $voter = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        Vote::factory()->onPost($post)->create(['user_id' => $voter->id, 'positive' => true]);

        $this->actingAs($voter)->get("/posts/{$post->id}")
            ->assertInertia(fn ($page) => $page->where('post.viewer_vote', true));

        $this->actingAs($otherUser)->get("/posts/{$post->id}")
            ->assertInertia(fn ($page) => $page->where('post.viewer_vote', null));

        $this->get("/posts/{$post->id}")
            ->assertInertia(fn ($page) => $page->where('post.viewer_vote', null));
    }

    public function test_the_group_page_post_list_reports_the_viewers_own_votes(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create();
        Vote::factory()->onPost($post)->create(['user_id' => $voter->id, 'positive' => false]);

        $this->actingAs($voter)->get("/groups/{$post->group_id}")
            ->assertInertia(fn ($page) => $page->where('posts.data.0.viewer_vote', false));
    }

    public function test_the_home_feed_reports_the_viewers_own_votes(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create(['created_at' => now()]);
        Vote::factory()->onPost($post)->create(['user_id' => $voter->id, 'positive' => true]);

        $this->actingAs($voter)->get('/')
            ->assertInertia(fn ($page) => $page->where('posts.data.0.viewer_vote', true));
    }
}
