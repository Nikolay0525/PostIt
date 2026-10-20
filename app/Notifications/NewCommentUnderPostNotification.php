<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;
use Illuminate\Notifications\Notification;

/**
 * Someone commented under the recipient's post.
 * Stores only ids: the frontend builds the text in the viewer's language,
 * and a deleted post or comment is shown as deleted.
 */
class NewCommentUnderPostNotification extends Notification
{
    public function __construct(private Comment $comment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Stored in the `type` column instead of the class name, so it matches the muted-kinds list.
    public function databaseType(object $notifiable): string
    {
        return NotificationType::NewCommentUnderPost->value;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->comment->post_id,
            'comment_id' => $this->comment->id,
            'author_id' => $this->comment->user_id,
        ];
    }
}
