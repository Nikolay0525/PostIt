<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Services\CommentService;

// The author follows replies to their comment from the start, and can stop.
class FollowOwnComment
{
    public function __construct(private CommentService $comments) {}

    public function handle(CommentCreated $event): void
    {
        $this->comments->follow($event->comment->id, $event->comment->user_id);
    }
}
