<?php

namespace App\Listeners;

use App\Events\PostCreated;
use App\Services\PostService;

// The author follows their post from the start, and can stop like anyone else.
class FollowOwnPost
{
    public function __construct(private PostService $posts) {}

    public function handle(PostCreated $event): void
    {
        $this->posts->follow($event->post->id, $event->post->user_id);
    }
}
