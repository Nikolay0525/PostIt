<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A post was published in a group.
 * Says only what happened; whoever needs to react listens for it.
 */
class PostCreated
{
    use Dispatchable;

    public function __construct(public Post $post) {}
}
