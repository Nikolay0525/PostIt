<?php

namespace App\Repositories\Contracts;

use App\Enums\ReportTargetType;
use App\Models\ModerationCase;
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
}
