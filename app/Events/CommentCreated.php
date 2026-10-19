<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A comment (top-level or reply) was added to a post.
 * Says only what happened; whoever needs to react listens for it.
 */
class CommentCreated
{
    use Dispatchable;

    public function __construct(public Comment $comment) {}
}
