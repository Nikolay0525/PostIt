<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Notifications\NewCommentAnswerNotification;

class SendNewCommentAnswerNotification
{
    public function handle(CommentCreated $event): void
    {
        $reply = $event->comment;

        // Only the direct parent's author is told, not everyone up the thread.
        if ($reply->parent_id === null) {
            return;
        }

        $parentAuthor = $reply->parent->author;

        if ($parentAuthor->id === $reply->user_id) {
            return;
        }

        $parentAuthor->notify(new NewCommentAnswerNotification($reply));
    }
}
