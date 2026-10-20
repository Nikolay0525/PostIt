<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Notifications\NewCommentUnderPostNotification;

class SendNewCommentUnderPostNotification
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $postAuthor = $comment->post->author;

        // Nobody needs to hear about their own comment.
        if ($postAuthor->id === $comment->user_id) {
            return;
        }

        $postAuthor->notify(new NewCommentUnderPostNotification($comment));
    }
}
