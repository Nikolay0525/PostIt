<?php

namespace App\Services;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Enums\SanctionKind;
use App\Events\PunishmentEnded;
use App\Events\PunishmentGiven;
use App\Events\StrikeGiven;
use App\Models\Group;
use App\Models\ModerationVerdict;
use App\Models\Punishment;
use App\Models\Strike;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Repositories\Contracts\GroupRepositoryInterface;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\SanctionRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Strikes and what they lead to, without anyone deciding: how long a strike lasts follows from how
 * many strikes of its kind the member already has, and when they reach the limit the punishment
 * begins at once, for as long as the member's earlier punishments make it. All the numbers are the
 * platform's (config/moderation.php).
 */
class StrikeService
{
    public function __construct(
        protected SanctionRepositoryInterface $sanctionRepository,
        protected GroupRepositoryInterface $groupRepository,
        protected PostRepositoryInterface $postRepository,
        protected CommentRepositoryInterface $commentRepository
    ) {}

    /**
     * The strike a "Warning" verdict gives its content's author, in the group the case was in. Its
     * kind is that of the rule the verdict named. Null for any other verdict, or when the author or
     * the rule is gone.
     */
    public function strikeFor(ModerationVerdict $verdict): ?Strike
    {
        if ($verdict->type !== ModerationVerdictType::Warning) {
            return null;
        }

        $case = $verdict->moderationCase;
        $kind = $verdict->ruleVersion?->kindOfRule((int) $verdict->rule_index);
        $authorId = match ($case->target_type) {
            ReportTargetType::Post => $this->postRepository->find($case->target_id)?->user_id,
            ReportTargetType::Comment => $this->commentRepository->find($case->target_id)?->user_id,
            ReportTargetType::User => null,
        };

        if ($kind === null || $authorId === null) {
            return null;
        }

        $punishment = null;
        $count = 0;
        $limit = 0;

        $strike = DB::transaction(function () use ($authorId, $case, $kind, $verdict, &$punishment, &$count, &$limit) {
            $this->sanctionRepository->lockRecordOf($authorId);

            return $this->give($authorId, $case->group_id, $kind, $verdict->id, $punishment, $count, $limit);
        });

        StrikeGiven::dispatch($strike, $count, $limit);

        if ($punishment !== null) {
            PunishmentGiven::dispatch($punishment);
        }

        return $strike;
    }

    /**
     * When a punishment has run its course, the member starts over: the strikes it was made of are
     * deleted (those given later stay), and the punishment is marked so that this is done once.
     * Of one member, or of everyone; returns how many punishments were settled.
     */
    public function settleFinished(?string $userId = null): int
    {
        $settled = 0;

        foreach ($this->sanctionRepository->finishedUnsettled($userId) as $punishment) {
            DB::transaction(function () use ($punishment) {
                $this->sanctionRepository->lockRecordOf($punishment->user_id);
                $this->sanctionRepository->deleteStrikesGivenUpTo($punishment->user_id, $punishment->group_id, $punishment->kind, $punishment->ends_at);
                $this->sanctionRepository->markSettled($punishment->id);
            });

            PunishmentEnded::dispatch($punishment);

            $settled++;
        }

        return $settled;
    }

    /**
     * How many active strikes of a kind punish a member in a group: the platform's highest, or the
     * group's own number if the owner lowered it. A group cannot raise it above the highest.
     */
    public function limitFor(Group $group, SanctionKind $kind): int
    {
        $highest = (int) config('moderation.strike_limit.'.$kind->key());
        $own = $kind === SanctionKind::Mute ? $group->mute_strike_limit : $group->ban_strike_limit;

        return $own === null ? $highest : max(1, min($own, $highest));
    }

    private function give(string $userId, ?string $groupId, SanctionKind $kind, string $verdictId, ?Punishment &$punishment, int &$count, int &$limit): Strike
    {
        // A punishment that ended a moment ago, before the scheduled settling got to it, must not leave
        // the old strikes to count towards the next one.
        $this->settleFinished($userId);

        $active = $this->sanctionRepository->countActiveStrikes($userId, $groupId, $kind);

        $strike = $this->sanctionRepository->createStrike(
            $userId,
            $groupId,
            $kind,
            $verdictId,
            now()->addDays($this->step(config('moderation.strike_lifetime_days'), $active))
        );

        // A member who is already being punished is not punished again for strikes that came in on
        // older content: they go away with the rest when the punishment ends.
        $group = $groupId === null ? null : $this->groupRepository->find($groupId);
        $limit = $group === null ? (int) config('moderation.strike_limit.'.$kind->key()) : $this->limitFor($group, $kind);

        $count = $active + 1;

        if ($count >= $limit && $this->sanctionRepository->findRunningPunishment($userId, $groupId, $kind) === null) {
            $punishment = $this->punish($userId, $groupId, $kind);
        }

        return $strike;
    }

    private function punish(string $userId, ?string $groupId, SanctionKind $kind): Punishment
    {
        $earlier = $this->sanctionRepository->countPunishmentsSince(
            $userId,
            $groupId,
            $kind,
            now()->subDays((int) config('moderation.punishment_memory_days'))
        );

        $days = $this->step(config('moderation.punishment_days'), $earlier);

        return $this->sanctionRepository->createPunishment(
            $userId,
            $groupId,
            $kind,
            $earlier + 1,
            now(),
            $days === null ? null : now()->addDays($days),
            $this->sanctionRepository->activeStrikeVerdictIds($userId, $groupId, $kind)
        );
    }

    /**
     * The $count-th rung of a ladder (the first is 0); past the end, the last one.
     *
     * @param  list<int|null>  $ladder
     */
    private function step(array $ladder, int $count): ?int
    {
        return $ladder[min($count, count($ladder) - 1)];
    }
}
