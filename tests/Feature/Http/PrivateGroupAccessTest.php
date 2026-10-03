<?php

namespace Tests\Feature\Http;

use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Models\UserGroupSubscription;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-COM-006: a private group's posts — and their comments, votes and shares — exist only for its
 * members. Outsiders get 404 rather than 403, so not even the post's existence is revealed.
 */
class PrivateGroupAccessTest extends TestCase
{
    use RefreshDatabase;

    private Group $group;

    private Post $post;

    private User $member;

    private User $outsider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);

        $this->group = Group::factory()->private()->create();
        $this->post = Post::factory()->create(['group_id' => $this->group->id]);
        $this->member = User::factory()->create();
        $this->outsider = User::factory()->create();
        UserGroupSubscription::query()->insert(['user_id' => $this->member->id, 'group_id' => $this->group->id, 'created_at' => now()]);
    }

    public function test_the_private_group_page_itself_stays_open_with_its_posts_hidden(): void
    {
        // Everyone must be able to find the group and ask to join — only the posts are closed.
        $url = route('groups.show', $this->group->slug);

        foreach ([null, $this->outsider] as $viewer) {
            ($viewer ? $this->actingAs($viewer) : $this)->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('group.id', $this->group->id)
                    ->where('group.is_private', true)
                    ->where('posts', null));
        }

        $this->actingAs($this->member)->get($url)
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));
    }

    public function test_the_post_page_is_a_404_for_guests_and_outsiders(): void
    {
        $url = route('posts.show', [$this->group->slug, $this->post->slug]);

        $this->get($url)->assertNotFound();
        $this->actingAs($this->outsider)->get($url)->assertNotFound();
        $this->actingAs($this->member)->get($url)->assertOk();

        // The outsider's failed visit isn't recorded as a view.
        $this->assertDatabaseMissing('post_views', ['user_id' => $this->outsider->id]);
    }

    public function test_a_random_post_is_a_404_for_guests_and_outsiders(): void
    {
        $url = route('groups.random_post', $this->group->slug);

        $this->get($url)->assertNotFound();
        $this->actingAs($this->outsider)->get($url)->assertNotFound();
        $this->actingAs($this->member)->get($url)->assertRedirect(route('posts.show', [$this->group->slug, $this->post->slug]));
    }

    public function test_an_outsider_cannot_comment(): void
    {
        $this->actingAs($this->outsider)
            ->postJson('/comments', ['post_id' => $this->post->id, 'text' => 'Hi'])
            ->assertNotFound();

        $this->actingAs($this->member)
            ->postJson('/comments', ['post_id' => $this->post->id, 'text' => 'Hi'])
            ->assertOk();

        $this->assertDatabaseCount('comments', 1);
    }

    public function test_an_outsider_cannot_vote_on_the_post_or_its_comments(): void
    {
        $comment = Comment::factory()->create(['post_id' => $this->post->id]);

        $this->actingAs($this->outsider)
            ->postJson('/votes', ['target_type' => 'post', 'target_id' => $this->post->id, 'positive' => true])
            ->assertNotFound();
        $this->actingAs($this->outsider)
            ->postJson('/votes', ['target_type' => 'comment', 'target_id' => $comment->id, 'positive' => true])
            ->assertNotFound();

        $this->actingAs($this->member)
            ->postJson('/votes', ['target_type' => 'post', 'target_id' => $this->post->id, 'positive' => true])
            ->assertOk();

        $this->assertDatabaseCount('votes', 1);
    }

    public function test_an_outsider_cannot_share(): void
    {
        $this->actingAs($this->outsider)->postJson("/posts/{$this->post->id}/share")->assertNotFound();
        $this->actingAs($this->member)->postJson("/posts/{$this->post->id}/share")->assertExactJson(['shares_count' => 1]);
    }

    public function test_a_public_groups_post_stays_open_to_everyone(): void
    {
        $public = Post::factory()->create();
        $url = route('posts.show', [$public->group->slug, $public->slug]);

        $this->get($url)->assertOk();
        $this->actingAs($this->outsider)->get($url)->assertOk();
        $this->actingAs($this->outsider)
            ->postJson('/comments', ['post_id' => $public->id, 'text' => 'Hi'])
            ->assertOk();
    }
}
