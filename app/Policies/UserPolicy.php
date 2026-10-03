<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Any verified user may follow another user, never themselves (FR-ACC-010). Unfollowing is
     * not gated: removing your own follow can't be used to bypass anything.
     */
    public function follow(User $user, User $author): bool
    {
        return $user->hasVerifiedEmail() && $user->id !== $author->id;
    }
}
