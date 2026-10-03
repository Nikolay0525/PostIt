<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\ChecksGroupBans;
use App\Policies\Concerns\ChecksGroupVisibility;
use Illuminate\Auth\Access\Response;

class PostPolicy
{
    use ChecksGroupBans;
    use ChecksGroupVisibility;

    /**
     * Anyone may read a post in a public group, guests included (hence ?User: Laravel then calls
     * this for guests too instead of refusing them outright); a post in a private group only its
     * members. Callers answer a refusal with 404, not 403, so the post's existence isn't revealed.
     */
    public function view(?User $user, Post $post): bool
    {
        return $this->canSeePostsOf($user, $post->group);
    }

    /**
     * A user may publish a post once verified, a member of the group (public or private — being
     * able to read a public group's posts does not by itself grant posting rights in it), and
     * not banned from it.
     */
    public function create(User $user, Group $group): bool
    {
        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        if (! $group->members()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return ! $this->isBannedFromGroup($user, $group->id);
    }

    /**
     * A user may vote on any post they can see except their own, and only while it has not been
     * deleted. A post they can't see is "not found", not "forbidden".
     */
    public function vote(User $user, Post $post): Response|bool
    {
        if (! $this->view($user, $post)) {
            return Response::denyAsNotFound();
        }

        return ! $post->is_deleted && $user->id !== $post->user_id;
    }
}
