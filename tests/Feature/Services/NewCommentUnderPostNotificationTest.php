<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewCommentUnderPostNotificationTest extends TestCase
{
    use RefreshDatabase;

    private CommentService $comments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->comments = $this->app->make(CommentService::class);
    }

    public function test_the_post_author_is_notified_about_a_new_comment(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();

        $comment = $this->comments->createComment($post->id, $commenter->id, 'Nice post');

        $notifications = $post->author->notifications;
        $this->assertCount(1, $notifications);
        $this->assertSame(NotificationType::NewCommentUnderPost->value, $notifications[0]->type);
        $this->assertSame([
            'post_id' => $post->id,
            'comment_id' => $comment->id,
            'author_id' => $commenter->id,
            'silent' => false,
        ], $notifications[0]->data);
        $this->assertNull($notifications[0]->read_at);
    }

    public function test_a_reply_also_notifies_the_post_author(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'First');

        $this->comments->createComment($post->id, $commenter->id, 'Second', $parent->id);

        $this->assertCount(2, $post->author->notifications);
    }

    public function test_commenting_on_your_own_post_sends_nothing(): void
    {
        $post = Post::factory()->create();

        $this->comments->createComment($post->id, $post->user_id, 'My own comment');

        $this->assertCount(0, $post->author->notifications);
    }
}
