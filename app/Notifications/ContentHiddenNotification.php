<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Comment;
use App\Models\Post;

/**
 * The recipient's post or comment got so many reports that a moderation case was opened on it, and it
 * is hidden until the moderators decide. Says only that, not how many reports there were or who sent
 * them. Ids only: the frontend builds the text.
 */
class ContentHiddenNotification extends BaseNotification
{
    public function __construct(private Post|Comment $content) {}

    public function type(): NotificationType
    {
        return NotificationType::ContentHidden;
    }

    private function post(): Post
    {
        return $this->content instanceof Post ? $this->content : $this->content->post;
    }

    protected function sourceGroupId(): ?string
    {
        return $this->post()->group_id;
    }

    protected function sourcePostId(): ?string
    {
        return $this->post()->id;
    }

    protected function payload(): array
    {
        return [
            'post_id' => $this->post()->id,
            'comment_id' => $this->content instanceof Comment ? $this->content->id : null,
            'target' => $this->content instanceof Post ? 'post' : 'comment',
        ];
    }
}
