<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A comment (top-level or reply) was added to a post.
 * Says only what happened; whoever needs to react listens for it.
 */
class CommentCreated
{
    // SerializesModels: queued listeners get the comment by id and reload it, not a stale copy.
    use Dispatchable, SerializesModels;

    public function __construct(public Comment $comment) {}
}
