<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;

/**
 * Someone replied to the recipient's comment. The post author may also get
 * NewCommentNotification for the same reply: these are different kinds, muted separately.
 */
class NewCommentAnswerNotification extends BaseNotification
{
    public function __construct(private Comment $reply) {}

    public function type(): NotificationType
    {
        return NotificationType::NewCommentAnswer;
    }

    protected function sourceGroupId(): ?string
    {
        return $this->reply->post->group_id;
    }

    protected function sourcePostId(): ?string
    {
        return $this->reply->post_id;
    }

    protected function payload(): array
    {
        return [
            'post_id' => $this->reply->post_id,
            'comment_id' => $this->reply->id,
            'author_id' => $this->reply->user_id,
        ];
    }
}
