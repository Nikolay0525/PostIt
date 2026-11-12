<?php

namespace App\Listeners;

use App\Enums\ReportTargetType;
use App\Events\ModerationCaseOpened;
use App\Notifications\ContentHiddenNotification;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\SanctionRepositoryInterface;

// A case on a post or comment hides it; its author hears of that, so that it does not simply vanish.
// Not someone banned from the group: its content is closed to them.
class NotifyAuthorOfHiddenContent
{
    public function __construct(
        private PostRepositoryInterface $posts,
        private CommentRepositoryInterface $comments,
        private SanctionRepositoryInterface $sanctions,
    ) {}

    public function handle(ModerationCaseOpened $event): void
    {
        $case = $event->case;

        $content = match ($case->target_type) {
            ReportTargetType::Post => $this->posts->find($case->target_id),
            ReportTargetType::Comment => $this->comments->find($case->target_id),
            ReportTargetType::User => null,
        };

        if ($content === null || $content->author === null) {
            return;
        }

        if ($this->sanctions->bannedAmong([$content->user_id], $case->group_id) !== []) {
            return;
        }

        $content->author->notify(new ContentHiddenNotification($content));
    }
}
