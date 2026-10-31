<?php

namespace Tests\Feature\Http;

use App\Models\Comment;
use App\Models\GroupBan;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * An author changing their own post or comment, and the "edited" time that follows.
 */
class EditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // UserObserver assigns a default UI/speaking language on user creation.
        $this->seed(LanguageSeeder::class);
    }

    private function postBy(User $author, array $attributes = []): Post
    {
        return Post::factory()->create(array_merge([
            'user_id' => $author->id,
            'title' => 'Old title',
            'article' => 'Old text',
        ], $attributes));
    }

    private function editUrl(Post $post): string
    {
        return "/groups/{$post->group->slug}/-/edit-post/{$post->slug}";
    }

    private function updateUrl(Post $post): string
    {
        return "/groups/{$post->group->slug}/posts/{$post->slug}";
    }

    public function test_the_author_gets_the_edit_page_with_the_current_text(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($author)->get($this->editUrl($post))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Posts/Edit')
                ->where('post.title', 'Old title')
                ->where('post.article', 'Old text')
                ->where('post.slug', $post->slug));
    }

    public function test_the_author_can_change_a_post_and_its_address_stays(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);
        $this->assertNull($post->edited_at);

        $this->actingAs($author)
            ->put($this->updateUrl($post), ['title' => 'New title', 'article' => 'New text'])
            ->assertRedirect(route('posts.show', [$post->group->slug, $post->slug]))
            ->assertSessionHasNoErrors();

        $post->refresh();
        $this->assertSame('New title', $post->title);
        $this->assertSame('New text', $post->article);
        $this->assertNotNull($post->edited_at);
    }

    public function test_the_title_can_be_removed(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($author)->put($this->updateUrl($post), ['title' => '', 'article' => 'Old text']);

        $this->assertNull($post->fresh()->title);
    }

    public function test_saving_a_post_unchanged_is_not_an_edit(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($author)->put($this->updateUrl($post), ['title' => 'Old title', 'article' => 'Old text']);

        $this->assertNull($post->fresh()->edited_at);
    }

    public function test_an_edit_is_validated(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);

        $this->actingAs($author)
            ->put($this->updateUrl($post), ['title' => str_repeat('t', 101), 'article' => ''])
            ->assertSessionHasErrors(['title', 'article']);

        $this->assertSame('Old text', $post->fresh()->article);
    }

    public function test_someone_else_cannot_edit_a_post(): void
    {
        $post = $this->postBy(User::factory()->create());
        $other = User::factory()->create();

        $this->actingAs($other)->get($this->editUrl($post))->assertForbidden();
        $this->actingAs($other)->put($this->updateUrl($post), ['title' => 'Hijacked', 'article' => 'Hijacked'])->assertForbidden();

        $this->assertSame('Old text', $post->fresh()->article);
    }

    public function test_a_guest_is_sent_to_login(): void
    {
        $post = $this->postBy(User::factory()->create());

        $this->get($this->editUrl($post))->assertRedirect('/login');
        $this->put($this->updateUrl($post), ['article' => 'x'])->assertRedirect('/login');
    }

    public function test_a_banned_author_cannot_edit_and_a_deleted_post_is_gone(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author);
        GroupBan::create([
            'group_id' => $post->group_id,
            'blamed_user_id' => $author->id,
            'moderator_id' => User::factory()->create()->id,
            'reason' => 'Spam',
            'expires_at' => null,
        ]);

        $this->actingAs($author)->get($this->editUrl($post))->assertForbidden();

        $deleted = $this->postBy($author, ['is_deleted' => true]);
        $this->actingAs($author)->get($this->editUrl($deleted))->assertNotFound();
    }

    public function test_only_the_author_is_offered_the_edit_link_and_the_edit_time_is_shown(): void
    {
        $author = User::factory()->create();
        $post = $this->postBy($author, ['edited_at' => now()->subHour()]);

        $this->actingAs($author)->get("/groups/{$post->group->slug}/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('post.can_edit', true)
                ->whereNot('post.edited_at', null));

        $this->actingAs(User::factory()->create())->get("/groups/{$post->group->slug}/posts/{$post->slug}")
            ->assertInertia(fn (AssertableInertia $page) => $page->where('post.can_edit', false));
    }

    public function test_the_author_can_change_a_comment(): void
    {
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id, 'text' => 'Old']);

        $this->actingAs($author)->putJson("/comments/{$comment->id}", ['text' => 'New'])
            ->assertOk()
            ->assertJson(['text' => 'New'])
            ->assertJsonStructure(['text', 'edited_at']);

        $comment->refresh();
        $this->assertSame('New', $comment->text);
        $this->assertNotNull($comment->edited_at);
    }

    public function test_saving_a_comment_unchanged_is_not_an_edit(): void
    {
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id, 'text' => 'Same']);

        $this->actingAs($author)->putJson("/comments/{$comment->id}", ['text' => 'Same'])->assertOk();

        $this->assertNull($comment->fresh()->edited_at);
    }

    public function test_a_comment_edit_is_validated_and_limited_to_its_author(): void
    {
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id, 'text' => 'Old']);

        $this->actingAs($author)->putJson("/comments/{$comment->id}", ['text' => str_repeat('x', 501)])
            ->assertUnprocessable();
        $this->actingAs(User::factory()->create())->putJson("/comments/{$comment->id}", ['text' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Old', $comment->fresh()->text);
    }

    public function test_a_deleted_comment_cannot_be_edited(): void
    {
        $author = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id, 'is_deleted' => true]);

        $this->actingAs($author)->putJson("/comments/{$comment->id}", ['text' => 'Back'])->assertNotFound();
    }
}
