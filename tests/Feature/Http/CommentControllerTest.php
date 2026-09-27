<?php

namespace Tests\Feature\Http;

use App\Models\Comment;
use App\Models\GroupBan;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    public function test_a_verified_user_can_post_a_top_level_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertOk()->assertJson([
            'post_id' => $post->id,
            'parent_id' => null,
            'is_deleted' => false,
            'text' => 'Hello world',
            'author' => ['id' => $user->id, 'name' => $user->name],
            'upvotes' => 0,
            'downvotes' => 0,
            'controversy' => null,
            'viewer_vote' => null,
            'replies' => [],
        ]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'parent_id' => null,
            'user_id' => $user->id,
            'text' => 'Hello world',
            'is_deleted' => false,
        ]);
    }

    public function test_a_verified_user_can_reply_to_a_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $parent = Comment::factory()->create(['post_id' => $post->id]);

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'text' => 'A reply',
        ]);

        $response->assertOk()->assertJson([
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'text' => 'A reply',
        ]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'user_id' => $user->id,
            'text' => 'A reply',
        ]);
    }

    public function test_a_guest_cannot_comment(): void
    {
        $post = Post::factory()->create();

        $response = $this->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
    }

    public function test_an_unverified_user_cannot_comment(): void
    {
        $user = User::factory()->unverified()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
    }

    public function test_a_user_banned_from_the_group_cannot_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $moderator = User::factory()->create();

        GroupBan::create([
            'group_id' => $post->group_id,
            'blamed_user_id' => $user->id,
            'moderator_id' => $moderator->id,
            'reason' => 'Spam',
            'expires_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id]);
    }

    public function test_an_expired_group_ban_does_not_block_commenting(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $moderator = User::factory()->create();

        GroupBan::create([
            'group_id' => $post->group_id,
            'blamed_user_id' => $user->id,
            'moderator_id' => $moderator->id,
            'reason' => 'Spam',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertOk();
    }

    public function test_commenting_on_a_deleted_post_is_forbidden(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['is_deleted' => true]);

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => 'Hello world',
        ]);

        $response->assertForbidden();
    }

    public function test_commenting_on_a_missing_post_is_a_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => '00000000-0000-4000-8000-000000000000',
            'text' => 'Hello world',
        ]);

        $response->assertNotFound();
    }

    public function test_text_is_required(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => '',
        ]);

        $response->assertInvalid(['text']);
    }

    public function test_text_cannot_exceed_500_characters(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'text' => str_repeat('a', 501),
        ]);

        $response->assertInvalid(['text']);
    }

    // CommentService::createComment() enforces this invariant with a plain InvalidArgumentException,
    // which the controller does not catch yet, so it currently surfaces as a 500 rather than a 422 —
    // documenting today's real behaviour, not the ideal one (see tech notes).
    public function test_a_reply_whose_parent_belongs_to_a_different_post_is_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $otherPost = Post::factory()->create();
        $parent = Comment::factory()->create(['post_id' => $otherPost->id]);

        $response = $this->actingAs($user)->postJson('/comments', [
            'post_id' => $post->id,
            'parent_id' => $parent->id,
            'text' => 'A reply',
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseMissing('comments', ['post_id' => $post->id, 'text' => 'A reply']);
    }
}
