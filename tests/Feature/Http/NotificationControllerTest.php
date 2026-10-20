<?php

namespace Tests\Feature\Http;

use App\Enums\NotificationType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use App\Services\PostService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
    }

    public function test_the_list_shows_names_instead_of_ids(): void
    {
        [$post, $commenter] = $this->postWithComment();

        $this->actingAs($post->author)->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('notifications.0.type', NotificationType::NewCommentUnderPost->value)
            ->assertJsonPath('notifications.0.author', $commenter->username)
            ->assertJsonPath('notifications.0.post_title', 'A title')
            ->assertJsonPath('notifications.0.has_link', true)
            ->assertJsonPath('notifications.0.read', false)
            ->assertJsonPath('notifications.0.important', false);
    }

    public function test_seeing_the_list_does_not_mark_anything_read(): void
    {
        [$post] = $this->postWithComment();

        $this->actingAs($post->author)->getJson('/notifications');

        $this->assertSame(1, $post->author->unreadNotifications()->count());
    }

    public function test_opening_a_notification_marks_it_read_and_leads_to_the_post(): void
    {
        [$post] = $this->postWithComment();
        $notification = $post->author->notifications()->first();

        $this->actingAs($post->author)->post("/notifications/{$notification->id}/open")
            ->assertRedirect(route('posts.show', [$post->group->slug, $post->slug]));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_a_notification_about_a_deleted_post_says_so_and_still_opens(): void
    {
        [$post] = $this->postWithComment();
        $post->update(['is_deleted' => true, 'deleted_at' => now()]);
        $notification = $post->author->notifications()->first();

        $this->actingAs($post->author)->getJson('/notifications')
            ->assertJsonPath('notifications.0.post_title', null)
            ->assertJsonPath('notifications.0.has_link', false);

        $this->actingAs($post->author)->from('/')->post("/notifications/{$notification->id}/open")
            ->assertRedirect('/');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_someone_elses_notification_cannot_be_opened(): void
    {
        [$post] = $this->postWithComment();
        $notification = $post->author->notifications()->first();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->from('/')->post("/notifications/{$notification->id}/open")
            ->assertRedirect('/');

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_read_all_marks_every_notification_read(): void
    {
        [$post, $commenter] = $this->postWithComment();
        $this->app->make(CommentService::class)->createComment($post->id, $commenter->id, 'Again');

        $this->actingAs($post->author)->postJson('/notifications/read-all')
            ->assertOk()
            ->assertExactJson(['unread' => 0]);

        $this->assertSame(0, $post->author->unreadNotifications()->count());
    }

    public function test_the_unread_count_is_shared_with_every_page(): void
    {
        [$post] = $this->postWithComment();

        $this->actingAs($post->author)->get('/')
            ->assertInertia(fn ($page) => $page->where('unread_notifications', 1));
    }

    public function test_guests_cannot_read_notifications(): void
    {
        $this->getJson('/notifications')->assertUnauthorized();
    }

    /** @return array{Post, User} */
    private function postWithComment(): array
    {
        $post = $this->app->make(PostService::class)->createPost(
            Group::factory()->create()->id,
            User::factory()->create()->id,
            'A title',
            'Some text',
        );
        $commenter = User::factory()->create();
        $this->app->make(CommentService::class)->createComment($post->id, $commenter->id, 'Hello');

        return [$post, $commenter];
    }
}
