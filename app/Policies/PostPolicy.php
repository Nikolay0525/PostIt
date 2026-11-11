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
     * members. Nobody banned from the group sees its posts. Callers answer a refusal with 404, not
     * 403, so the post's existence isn't revealed.
     */
    public function view(?User $user, Post $post): bool
    {
        if ($user !== null && $this->isBannedFromGroup($user, $post->group_id)) {
            return false;
        }

        return $this->canSeePostsOf($user, $post->group);
    }

    /**
     * A user may publish a post once verified, a member of the group (public or private — being
     * able to read a public group's posts does not by itself grant posting rights in it), and
     * neither muted nor banned in it.
     */
    public function create(User $user, Group $group): bool
    {
        if (! $user->hasVerifiedEmail()) {
            return false;
        }

        if (! $group->members()->where('user_id', $user->id)->exists()) {
            return false;
        }

        return ! $this->cannotContributeIn($user, $group->id);
    }

    /**
     * Only the author edits a post, and only while it is not deleted or hidden (a moderation case
     * judges the text as it was reported), they are verified and not banned from its group.
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && ! $post->is_deleted
            && ! $post->isConcealed()
            && $user->hasVerifiedEmail()
            && ! $this->cannotContributeIn($user, $post->group_id);
    }

    /**
     * A verified user may report a post they can see, except their own and a deleted one. A post
     * they can't see is "not found", not "forbidden".
     */
    public function report(User $user, Post $post): Response|bool
    {
        if (! $this->view($user, $post)) {
            return Response::denyAsNotFound();
        }

        return $user->hasVerifiedEmail() && ! $post->is_deleted && ! $post->isConcealed() && $user->id !== $post->user_id;
    }

    /**
     * A user may vote on any post they can see except their own, and only while it has not been
     * deleted, and while not muted or banned in its group. A post they can't see is "not found",
     * not "forbidden". Reporting stays open to the muted: it is how they would ask for help.
     */
    public function vote(User $user, Post $post): Response|bool
    {
        if (! $this->view($user, $post)) {
            return Response::denyAsNotFound();
        }

        return ! $post->is_deleted && ! $post->isConcealed() && $user->id !== $post->user_id
            && ! $this->cannotContributeIn($user, $post->group_id);
    }
}
