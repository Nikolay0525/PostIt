<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Notifications\NewCommentAnswerNotification;
use App\Notifications\NewCommentUnderPostNotification;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\CommentService;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Notification;

/**
 * One comment brings each person at most one notification. Whoever follows the answered comment
 * gets NewCommentAnswer, as the more specific one; if they turned that kind off, they are treated
 * as a plain post follower instead. Everyone else following the post gets NewCommentUnderPost.
 */
// Queued: a post may have thousands of followers, and the commenter should not wait for them.
// After commit, so the worker never looks for a comment that is not saved yet.
class SendCommentNotifications implements ShouldQueueAfterCommit
{
    public function __construct(
        private CommentService $comments,
        private PostRepositoryInterface $posts,
    ) {}

    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;

        // Nobody needs to hear about their own comment.
        $handled = [$comment->user_id];

        if ($comment->parent_id !== null) {
            $parentAuthor = $comment->parent->author;

            if ($parentAuthor->id !== $comment->user_id
                && $this->comments->isFollowing($comment->parent_id, $parentAuthor->id)) {
                $answer = new NewCommentAnswerNotification($comment);

                if (! $answer->isDisabledFor($parentAuthor)) {
                    $parentAuthor->notify($answer);
                    $handled[] = $parentAuthor->id;
                }
            }
        }

        Notification::send(
            $this->posts->followers($comment->post_id, $handled),
            new NewCommentUnderPostNotification($comment),
        );
    }
}
