<?php

namespace App\Repositories\Contracts;

use App\Enums\ModerationVerdictType;
use App\Enums\ReportTargetType;
use App\Models\ModerationCase;
use App\Models\ModerationVerdict;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;

interface ModerationCaseRepositoryInterface
{
    /**
     * The case still open on this post or comment, if any.
     */
    public function findOpen(ReportTargetType $targetType, string $targetId): ?ModerationCase;

    /**
     * Opens a case on a post or comment. At most one case is open on a piece of content at a time.
     * `$ruleVersionId` is the group's rules as they are now: the verdict names the broken rule from it.
     *
     * @throws UniqueConstraintViolationException when one already is
     */
    public function open(string $groupId, ReportTargetType $targetType, string $targetId, ?string $ruleVersionId = null): ModerationCase;

    /**
     * Stores how the case was decided.
     *
     * @param  list<array{start: int, end: int, quote: string}>|null  $marks
     *
     * @throws UniqueConstraintViolationException when the case already has a verdict
     */
    public function createVerdict(
        string $caseId,
        ModerationVerdictType $type,
        string $decidedBy,
        ?string $ruleVersionId,
        ?int $ruleIndex,
        ?array $marks,
        ?string $reason,
    ): ModerationVerdict;

    /**
     * Closes the case: it leaves the queue, and its content may get a new case later.
     */
    public function close(string $caseId): void;

    /**
     * The group's open cases, the longest waiting first. Each carries `reports_count`, its
     * `reports` and its `target` (the post or comment, as a relation set by hand: the type is a column).
     *
     * The moderator who reads this must not learn who wrote the content or who reported it, so
     * neither the author's id nor the reporters' ids are loaded.
     */
    public function openInGroup(string $groupId, int $perPage): LengthAwarePaginator;

    /**
     * The latest case on this post or comment, open or decided, with its verdict and the rules the
     * verdict names: what the content's own author is shown of how it was dealt with. Null if it never
     * had one.
     */
    public function findLatestFor(ReportTargetType $targetType, string $targetId): ?ModerationCase;

    /**
     * One case with the same data as in the list, or null.
     */
    public function findForModeration(string $id): ?ModerationCase;
}
