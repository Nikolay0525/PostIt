<?php

namespace App\Repositories\Contracts;

use App\Enums\ReportTargetType;
use App\Models\ModerationCase;
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
     *
     * @throws UniqueConstraintViolationException when one already is
     */
    public function open(string $groupId, ReportTargetType $targetType, string $targetId): ModerationCase;

    /**
     * The group's open cases, the longest waiting first. Each carries `reports_count`, its
     * `reports` and its `target` (the post or comment, as a relation set by hand: the type is a column).
     *
     * The moderator who reads this must not learn who wrote the content or who reported it, so
     * neither the author's id nor the reporters' ids are loaded.
     */
    public function openInGroup(string $groupId, int $perPage): LengthAwarePaginator;

    /**
     * One case with the same data as in the list, or null.
     */
    public function findForModeration(string $id): ?ModerationCase;
}
