<?php

namespace Tests\Feature\Http;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Models\Vote;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    public function test_casts_a_new_vote(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'positive' => true,
        ]);

        $response->assertOk()->assertJson(['upvotes' => 1, 'downvotes' => 0, 'controversy' => null]);
        $this->assertDatabaseHas('votes', [
            'parent_id' => $post->id,
            'user_id' => $voter->id,
            'positive' => true,
        ]);
    }

    public function test_repeating_the_same_direction_removes_the_vote(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post', 'target_id' => $post->id, 'positive' => true,
        ]);
        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post', 'target_id' => $post->id, 'positive' => true,
        ]);

        $response->assertOk()->assertJson(['upvotes' => 0, 'downvotes' => 0]);
        $this->assertDatabaseMissing('votes', ['parent_id' => $post->id, 'user_id' => $voter->id]);
    }

    public function test_the_opposite_direction_changes_the_vote(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post', 'target_id' => $post->id, 'positive' => true,
        ]);
        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post', 'target_id' => $post->id, 'positive' => false,
        ]);

        $response->assertOk()->assertJson(['upvotes' => 0, 'downvotes' => 1]);
        $this->assertDatabaseHas('votes', [
            'parent_id' => $post->id,
            'user_id' => $voter->id,
            'positive' => false,
        ]);
    }

    public function test_a_comment_can_be_voted_on_too(): void
    {
        $voter = User::factory()->create();
        $comment = Comment::factory()->create();

        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'positive' => true,
        ]);

        $response->assertOk()->assertJson(['upvotes' => 1, 'downvotes' => 0]);
    }

    public function test_a_user_cannot_vote_on_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $response = $this->actingAs($author)->postJson('/votes', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'positive' => true,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('votes', ['parent_id' => $post->id]);
    }

    public function test_a_guest_cannot_vote(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/votes', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'positive' => true,
        ]);

        $response->assertUnauthorized();
    }

    public function test_an_unknown_target_type_is_rejected(): void
    {
        $voter = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'group',
            'target_id' => $post->id,
            'positive' => true,
        ]);

        $response->assertInvalid(['target_type']);
    }

    public function test_voting_on_a_missing_post_is_a_404(): void
    {
        $voter = User::factory()->create();

        $response = $this->actingAs($voter)->postJson('/votes', [
            'target_type' => 'post',
            'target_id' => '00000000-0000-4000-8000-000000000000',
            'positive' => true,
        ]);

        $response->assertNotFound();
    }

    public function test_controversy_is_returned_once_both_sides_reach_the_threshold(): void
    {
        $post = Post::factory()->create();
        $voters = User::factory()->count(6)->create();

        foreach ($voters->take(3) as $voter) {
            Vote::factory()->onPost($post)->create(['user_id' => $voter->id, 'positive' => true]);
        }
        foreach ($voters->skip(3)->take(3) as $voter) {
            Vote::factory()->onPost($post)->create(['user_id' => $voter->id, 'positive' => false]);
        }

        $response = $this->actingAs(User::factory()->create())->postJson('/votes', [
            'target_type' => 'post',
            'target_id' => $post->id,
            'positive' => true,
        ]);

        // 4 up / 3 down after this vote: magnitude=7, balance=3/4 -> 7^0.75 ≈ 4.3 -> rounds to 4.
        $response->assertOk()->assertJson(['upvotes' => 4, 'downvotes' => 3, 'controversy' => 4]);
    }
}
