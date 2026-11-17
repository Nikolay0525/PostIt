<?php

namespace Tests\Feature\Http;

use App\Models\Comment;
use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\ModerationCase;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Content under a moderation case: hidden by it, and shown only as hidden.
 */
class HiddenContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    private function hiddenPost(): Post
    {
        return Post::factory()->create(['title' => 'Secret title', 'article' => 'Secret text', 'hidden_at' => now()]);
    }

    public function test_enough_reports_hide_the_post_and_it_is_shown_as_hidden(): void
    {
        $group = Group::factory()->create();
        GroupActivity::create(['group_id' => $group->id, 'voters_count' => 200, 'votes_count' => 600, 'computed_at' => now()]);
        $post = Post::factory()->create(['group_id' => $group->id, 'article' => 'Secret text']);

        foreach (range(1, 10) as $_) {
            $reporter = User::factory()->create();
            DB::table('user_group_karma')->insert(['user_id' => $reporter->id, 'group_id' => $group->id, 'karma' => 1]);
            $this->actingAs($reporter)->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'platform_violation' => true, 'text' => 'Spam']);
        }

        $this->assertNotNull($post->fresh()->hidden_at);
        $this->assertNotNull(ModerationCase::firstOrFail());
    }

    public function test_enough_reports_hide_a_comment_too(): void
    {
        $group = Group::factory()->create();
        GroupActivity::create(['group_id' => $group->id, 'voters_count' => 200, 'votes_count' => 600, 'computed_at' => now()]);
        $comment = Comment::factory()->create(['post_id' => Post::factory()->create(['group_id' => $group->id])->id]);

        foreach (range(1, 10) as $_) {
            $reporter = User::factory()->create();
            DB::table('user_group_karma')->insert(['user_id' => $reporter->id, 'group_id' => $group->id, 'karma' => 1]);
            $this->actingAs($reporter)->postJson('/reports', ['target_type' => 'comment', 'target_id' => $comment->id, 'platform_violation' => true, 'text' => 'Rude']);
        }

        $this->assertNotNull($comment->fresh()->hidden_at);
    }

    public function test_a_hidden_posts_text_and_author_never_reach_the_page(): void
    {
        $post = $this->hiddenPost();

        $this->actingAs(User::factory()->create())->get("/groups/{$post->group->slug}/posts/{$post->slug}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('post.is_hidden', true)
                ->where('post.title', null)
                ->where('post.article', '')
                ->where('post.article_html', '')
                ->where('post.article_text', '')
                ->where('post.author.id', null)
                ->where('post.author.username', 'Hidden')
                ->where('post.can_edit', false));
    }

    public function test_a_hidden_or_removed_post_is_left_out_of_feeds_and_search_but_not_for_its_author(): void
    {
        $post = $this->hiddenPost();
        $removed = Post::factory()->create(['group_id' => $post->group_id, 'title' => 'Removed title', 'removed_at' => now()]);
        $shown = Post::factory()->create(['group_id' => $post->group_id, 'title' => 'Secret, but fine']);

        $this->get("/groups/{$post->group->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.id', $shown->id));

        $this->get('/search?q=Secret')
            ->assertInertia(fn (AssertableInertia $page) => $page->where('posts.data.0.id', $shown->id)->has('posts.data', 1));

        // Its author still sees it, as hidden, with where it stands.
        $posts = $this->actingAs($post->author)->get("/groups/{$post->group->slug}")
            ->viewData('page')['props']['posts']['data'];
        $this->assertEqualsCanonicalizing([$post->id, $shown->id], array_column($posts, 'id'));
        $this->assertTrue(collect($posts)->firstWhere('id', $post->id)['is_hidden']);

        // Its page still opens by its link.
        $this->get(route('posts.show', [$removed->group->slug, $removed->slug]))->assertOk();
    }

    public function test_a_hidden_comment_stays_in_the_thread_as_a_placeholder(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'text' => 'Secret comment', 'hidden_at' => now()]);

        $this->get("/groups/{$post->group->slug}/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('comments.data.0.is_hidden', true)
                ->where('comments.data.0.is_deleted', false)
                ->where('comments.data.0.text', '')
                ->where('comments.data.0.author.id', null)
                ->where('comments.data.0.author.username', 'Hidden'));
    }

    public function test_hidden_content_cannot_be_reported_voted_on_or_edited(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id, 'hidden_at' => now()]);
        $comment = Comment::factory()->create(['user_id' => $author->id, 'hidden_at' => now()]);
        $other = User::factory()->create();

        $this->actingAs($other)->postJson('/reports', ['target_type' => 'post', 'target_id' => $post->id, 'platform_violation' => true, 'text' => 'x'])->assertForbidden();
        $this->actingAs($other)->postJson('/reports', ['target_type' => 'comment', 'target_id' => $comment->id, 'platform_violation' => true, 'text' => 'x'])->assertForbidden();
        $this->actingAs($other)->postJson('/votes', ['target_type' => 'post', 'target_id' => $post->id, 'positive' => true])->assertForbidden();
        $this->actingAs($other)->postJson('/votes', ['target_type' => 'comment', 'target_id' => $comment->id, 'positive' => true])->assertForbidden();
        $this->actingAs($author)->get("/groups/{$post->group->slug}/-/edit-post/{$post->slug}")->assertForbidden();
        $this->actingAs($author)->putJson("/comments/{$comment->id}", ['text' => 'Rewritten'])->assertForbidden();

        $this->assertNotSame('Rewritten', $comment->fresh()->text);
    }
}
