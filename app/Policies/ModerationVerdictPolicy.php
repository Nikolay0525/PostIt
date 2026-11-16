<?php

namespace App\Policies;

use App\Models\ModerationVerdict;
use App\Models\User;
use App\Repositories\Contracts\AppealRepositoryInterface;
use App\Repositories\Contracts\ModerationCaseRepositoryInterface;
use Illuminate\Auth\Access\Response;

class ModerationVerdictPolicy
{
    /**
     * Only the author of the content may appeal a verdict that punished it, once, and within the
     * window after it. Whoever it is not about gets "not found": that a verdict exists is nobody
     * else's to know. Being muted or banned does not matter: an appeal is not a contribution.
     */
    public function appeal(User $user, ModerationVerdict $verdict): Response|bool
    {
        $authorId = app(ModerationCaseRepositoryInterface::class)->authorIdOf($verdict->moderationCase);

        if ($authorId !== $user->id) {
            return Response::denyAsNotFound();
        }

        if (! $user->hasVerifiedEmail() || ! $verdict->type->punishes()) {
            return false;
        }

        if ($verdict->created_at->lt(now()->subDays((int) config('moderation.appeal_window_days')))) {
            return false;
        }

        return ! app(AppealRepositoryInterface::class)->existsForVerdict($verdict->id);
    }
}
