<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Notifications\NewCommentNotification;

class SendNewCommentNotification
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $postAuthor = $comment->post->author;

        // Nobody needs to hear about their own comment.
        if ($postAuthor->id === $comment->user_id) {
            return;
        }

        $postAuthor->notify(new NewCommentNotification($comment));
    }
}
