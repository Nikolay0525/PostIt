<?php

namespace Tests\Feature\Services;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Listeners\SendCommentNotifications;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use App\Services\NotificationPreferenceService;
use App\Services\PostService;
use Database\Seeders\LanguageSeeder;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CommentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private CommentService $comments;

    private PostService $posts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LanguageSeeder::class);
        $this->comments = $this->app->make(CommentService::class);
        $this->posts = $this->app->make(PostService::class);
    }

    public function test_the_author_follows_their_new_post_and_comment(): void
    {
        $post = $this->publish();
        $comment = $this->comments->createComment($post->id, $post->user_id, 'Hi');

        $this->assertTrue($this->posts->isFollowing($post->id, $post->user_id));
        $this->assertTrue($this->comments->isFollowing($comment->id, $post->user_id));
    }

    public function test_a_post_follower_is_notified_about_a_new_comment(): void
    {
        $post = $this->publish();
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

    public function test_an_interested_user_is_notified_too(): void
    {
        $post = $this->publish();
        [$interested, $commenter] = User::factory()->count(2)->create();
        $this->posts->follow($post->id, $interested->id);

        $this->comments->createComment($post->id, $commenter->id, 'Hello');

        $this->assertCount(1, $interested->notifications);
        $this->assertCount(1, $post->author->notifications);
    }

    public function test_an_author_who_unfollowed_hears_nothing(): void
    {
        $post = $this->publish();
        $this->posts->unfollow($post->id, $post->user_id);

        $this->comments->createComment($post->id, User::factory()->create()->id, 'Hello?');

        $this->assertCount(0, $post->author->notifications);
    }

    public function test_your_own_comment_sends_you_nothing(): void
    {
        $post = $this->publish();

        $this->comments->createComment($post->id, $post->user_id, 'My own comment');

        $this->assertCount(0, $post->author->notifications);
    }

    public function test_the_answered_author_gets_an_answer_notification(): void
    {
        $post = $this->publish();
        [$commenter, $replier] = User::factory()->count(2)->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'First');

        $reply = $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $notifications = $commenter->notifications;
        $this->assertCount(1, $notifications);
        $this->assertSame(NotificationType::NewCommentAnswer->value, $notifications[0]->type);
        $this->assertSame($reply->id, $notifications[0]->data['comment_id']);
    }

    public function test_only_the_direct_parent_author_gets_an_answer(): void
    {
        $post = $this->publish();
        [$first, $second, $third] = User::factory()->count(3)->create();
        $top = $this->comments->createComment($post->id, $first->id, 'Top');
        $middle = $this->comments->createComment($post->id, $second->id, 'Middle', $top->id);

        $this->comments->createComment($post->id, $third->id, 'Bottom', $middle->id);

        $this->assertCount(1, $first->notifications);
        $this->assertCount(1, $second->notifications);
    }

    public function test_an_answer_to_a_comment_its_author_unfollowed_sends_nothing(): void
    {
        $post = $this->publish();
        [$commenter, $replier] = User::factory()->count(2)->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'Just a joke');
        $this->comments->unfollow($parent->id, $commenter->id);

        $this->comments->createComment($post->id, $replier->id, 'Haha', $parent->id);

        $this->assertCount(0, $commenter->notifications);
    }

    public function test_a_follower_whose_comment_is_answered_gets_only_the_answer(): void
    {
        $post = $this->publish();
        $replier = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $post->user_id, 'Author here');

        $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $this->assertSame(
            [NotificationType::NewCommentAnswer->value],
            $post->author->notifications->pluck('type')->all(),
        );
    }

    public function test_with_answers_turned_off_a_post_follower_gets_the_comment_instead(): void
    {
        $post = $this->publish();
        $this->setKind($post->author, NotificationType::NewCommentAnswer, NotificationStatus::Disabled);
        $replier = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $post->user_id, 'Author here');

        $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $this->assertSame(
            [NotificationType::NewCommentUnderPost->value],
            $post->author->notifications->pluck('type')->all(),
        );
    }

    public function test_with_comments_turned_off_the_answer_still_comes(): void
    {
        $post = $this->publish();
        $this->setKind($post->author, NotificationType::NewCommentUnderPost, NotificationStatus::Disabled);
        $replier = User::factory()->create();
        $parent = $this->comments->createComment($post->id, $post->user_id, 'Author here');

        $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $this->assertSame(
            [NotificationType::NewCommentAnswer->value],
            $post->author->notifications->pluck('type')->all(),
        );
    }

    public function test_a_following_and_muted_post_brings_comments_silently(): void
    {
        $post = $this->publish();
        $this->app->make(NotificationPreferenceService::class)->mutePost($post->user_id, $post->id);

        $this->comments->createComment($post->id, User::factory()->create()->id, 'Shh');

        $this->assertTrue($post->author->notifications[0]->data['silent']);
    }

    public function test_a_muted_comment_brings_its_answers_silently(): void
    {
        $post = $this->publish();
        [$commenter, $replier] = User::factory()->count(2)->create();
        $parent = $this->comments->createComment($post->id, $commenter->id, 'First');
        $this->app->make(NotificationPreferenceService::class)->muteComment($commenter->id, $parent->id);

        $this->comments->createComment($post->id, $replier->id, 'Answer', $parent->id);

        $this->assertTrue($commenter->notifications[0]->data['silent']);
    }

    public function test_comment_notifications_are_sent_through_the_queue(): void
    {
        Queue::fake();
        $post = $this->publish();

        $this->comments->createComment($post->id, User::factory()->create()->id, 'Later');

        Queue::assertPushed(CallQueuedListener::class, fn (CallQueuedListener $job) => $job->class === SendCommentNotifications::class);
        $this->assertCount(0, $post->author->notifications);
    }

    // Through PostService, as in the app, so the author follows the post.
    private function publish(): Post
    {
        return $this->posts->createPost(
            Group::factory()->create()->id,
            User::factory()->create()->id,
            'A title',
            'Some text',
        );
    }

    private function setKind(User $user, NotificationType $type, NotificationStatus $status): void
    {
        $user->settings->update(['notification_preferences' => [$type->value => $status->value]]);
    }
}
