<?php

namespace App\Repositories\Eloquent;

use App\Enums\SanctionKind;
use App\Models\Punishment;
use App\Models\Strike;
use App\Models\User;
use App\Repositories\Contracts\SanctionRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EloquentSanctionRepository implements SanctionRepositoryInterface
{
    public function lockRecordOf(string $userId): void
    {
        User::whereKey($userId)->lockForUpdate()->first();
    }

    public function countActiveStrikes(string $userId, ?string $groupId, SanctionKind $kind): int
    {
        return $this->activeStrikes($userId, $groupId, $kind)->count();
    }

    public function activeStrikeVerdictIds(string $userId, ?string $groupId, SanctionKind $kind): array
    {
        return $this->activeStrikes($userId, $groupId, $kind)->pluck('moderation_verdict_id')->all();
    }

    public function createStrike(string $userId, ?string $groupId, SanctionKind $kind, string $verdictId, CarbonInterface $expiresAt): Strike
    {
        return Strike::create([
            'user_id' => $userId,
            'group_id' => $groupId,
            'kind' => $kind,
            'moderation_verdict_id' => $verdictId,
            'expires_at' => $expiresAt,
        ]);
    }

    public function findRunningPunishment(string $userId, ?string $groupId, SanctionKind $kind): ?Punishment
    {
        return $this->punishments($userId, $groupId, $kind)
            ->whereNull('lifted_at')
            ->where(fn (Builder $running) => $running->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->first();
    }

    public function countPunishmentsSince(string $userId, ?string $groupId, SanctionKind $kind, CarbonInterface $since): int
    {
        return $this->punishments($userId, $groupId, $kind)
            ->whereNull('lifted_at')
            ->where('starts_at', '>=', $since)
            ->count();
    }

    public function finishedUnsettled(?string $userId = null): Collection
    {
        return Punishment::query()
            ->whereNull('lifted_at')
            ->whereNull('settled_at')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->when($userId !== null, fn (Builder $q) => $q->where('user_id', $userId))
            ->get();
    }

    public function deleteStrikesGivenUpTo(string $userId, ?string $groupId, SanctionKind $kind, CarbonInterface $until): int
    {
        return $this->scoped(Strike::query(), $userId, $groupId, $kind)->where('created_at', '<=', $until)->delete();
    }

    public function markSettled(string $punishmentId): void
    {
        Punishment::whereKey($punishmentId)->update(['settled_at' => now()]);
    }

    public function createPunishment(
        string $userId,
        ?string $groupId,
        SanctionKind $kind,
        int $step,
        CarbonInterface $startsAt,
        ?CarbonInterface $endsAt,
        array $verdictIds,
    ): Punishment {
        $punishment = Punishment::create([
            'user_id' => $userId,
            'group_id' => $groupId,
            'kind' => $kind,
            'step' => $step,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $punishment->verdicts()->attach($verdictIds);

        return $punishment;
    }

    private function activeStrikes(string $userId, ?string $groupId, SanctionKind $kind): Builder
    {
        return $this->scoped(Strike::query(), $userId, $groupId, $kind)->where('expires_at', '>', now());
    }

    private function punishments(string $userId, ?string $groupId, SanctionKind $kind): Builder
    {
        return $this->scoped(Punishment::query(), $userId, $groupId, $kind);
    }

    private function scoped(Builder $query, string $userId, ?string $groupId, SanctionKind $kind): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where('kind', $kind->value)
            ->when($groupId === null, fn (Builder $q) => $q->whereNull('group_id'), fn (Builder $q) => $q->where('group_id', $groupId));
    }
}
