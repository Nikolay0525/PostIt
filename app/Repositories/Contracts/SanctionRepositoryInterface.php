<?php

namespace App\Repositories\Contracts;

use App\Enums\SanctionKind;
use App\Models\Punishment;
use App\Models\Strike;
use Carbon\CarbonInterface;

/**
 * A member's strikes and the punishments they led to: one record kept together, so one repository.
 * `$groupId` null is the member's account (a platform rule), not a group.
 */
interface SanctionRepositoryInterface
{
    /**
     * Makes everything done to this member's record wait for the one doing it now, so two strikes
     * given at the same moment cannot both see the limit as not yet reached. Call inside a transaction.
     */
    public function lockRecordOf(string $userId): void;

    /**
     * Strikes of this kind still in force.
     */
    public function countActiveStrikes(string $userId, ?string $groupId, SanctionKind $kind): int;

    /**
     * The verdicts behind the strikes of this kind still in force.
     *
     * @return list<string>
     */
    public function activeStrikeVerdictIds(string $userId, ?string $groupId, SanctionKind $kind): array;

    public function createStrike(string $userId, ?string $groupId, SanctionKind $kind, string $verdictId, CarbonInterface $expiresAt): Strike;

    /**
     * The punishment of this kind that is in force now, if any.
     */
    public function findRunningPunishment(string $userId, ?string $groupId, SanctionKind $kind): ?Punishment;

    /**
     * Punishments of this kind that started since then and were not taken back by an appeal.
     */
    public function countPunishmentsSince(string $userId, ?string $groupId, SanctionKind $kind, CarbonInterface $since): int;

    /**
     * @param  list<string>  $verdictIds  the verdicts it was made of
     */
    public function createPunishment(
        string $userId,
        ?string $groupId,
        SanctionKind $kind,
        int $step,
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt,
        array $verdictIds,
    ): Punishment;
}
