<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;

/**
 * Someone commented under the recipient's post.
 * Stores only ids: the frontend builds the text in the viewer's language,
 * and a deleted post or comment is shown as deleted.
 */
class NewCommentUnderPostNotification extends BaseNotification
{
    public function __construct(private Comment $comment) {}

    public function type(): NotificationType
    {
        return NotificationType::NewCommentUnderPost;
    }

    protected function payload(): array
    {
        return [
            'post_id' => $this->comment->post_id,
            'comment_id' => $this->comment->id,
            'author_id' => $this->comment->user_id,
        ];
    }
}
