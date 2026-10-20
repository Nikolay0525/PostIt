<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewCommentAnswerNotificationTest extends TestCase
{
    use RefreshDatabase;

    private CommentService $comments;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->comments = $this->app->make(CommentService::class);
    }

    public function test_the_parent_comment_author_is_notified_about_a_reply(): void
    {
        $post = Post::factory()->create();
        [$commenter, $replier] = User::factory()->count(2)->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'First');

        $reply = $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $notifications = $commenter->notifications;
        $this->assertCount(1, $notifications);
        $this->assertSame(NotificationType::NewCommentAnswer->value, $notifications[0]->type);
        $this->assertSame([
            'post_id' => $post->id,
            'comment_id' => $reply->id,
            'author_id' => $replier->id,
        ], $notifications[0]->data);
    }

    public function test_a_top_level_comment_sends_no_answer_notification(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();

        $this->comments->createComment($post->id, $commenter->id, 'First');

        $this->assertCount(0, $commenter->notifications);
    }

    public function test_replying_to_your_own_comment_sends_nothing(): void
    {
        $post = Post::factory()->create();
        $commenter = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'First');

        $this->comments->createComment($post->id, $commenter->id, 'Me again', $parent->id);

        $this->assertCount(0, $commenter->notifications);
    }

    public function test_only_the_direct_parent_author_is_notified(): void
    {
        $post = Post::factory()->create();
        [$first, $second, $third] = User::factory()->count(3)->create();
        $top = $this->comments->createComment($post->id, $first->id, 'Top');
        $middle = $this->comments->createComment($post->id, $second->id, 'Middle', $top->id);

        $this->comments->createComment($post->id, $third->id, 'Bottom', $middle->id);

        // $first got one answer (from $second), not a second one for the deeper reply.
        $this->assertCount(1, $first->notifications);
        $this->assertCount(1, $second->notifications);
    }

    public function test_the_post_author_gets_both_kinds_when_their_comment_is_answered(): void
    {
        $post = Post::factory()->create();
        $replier = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $post->user_id, 'Author here');

        $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $this->assertEqualsCanonicalizing(
            [NotificationType::NewCommentUnderPost->value, NotificationType::NewCommentAnswer->value],
            $post->author->notifications->pluck('type')->all(),
        );
    }
}
