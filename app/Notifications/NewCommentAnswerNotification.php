<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;
use Illuminate\Notifications\Notification;

/**
 * Someone replied to the recipient's comment. The post author may also get
 * NewCommentNotification for the same reply: these are different kinds, muted separately.
 */
class NewCommentAnswerNotification extends Notification
{
    public function __construct(private Comment $reply) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function databaseType(object $notifiable): string
    {
        return NotificationType::NewCommentAnswer->value;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->reply->post_id,
            'comment_id' => $this->reply->id,
            'author_id' => $this->reply->user_id,
        ];
    }
}
