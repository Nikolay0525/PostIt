<?php

namespace App\Services;

use App\Events\AppealFiled;
use App\Models\Appeal;
use App\Models\ModerationVerdict;
use App\Repositories\Contracts\AppealRepositoryInterface;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;

class AppealService
{
    public function __construct(protected AppealRepositoryInterface $appealRepository) {}

    /**
     * Files the author's appeal against a verdict. Whether they may (their content, within the window,
     * not appealed yet) is the policy's to say, before this; here a second appeal that slipped in at the
     * same moment is turned away by the table's unique key.
     *
     * The removed content stays removed while it waits: nothing is changed by filing.
     *
     * @throws DomainException when the verdict was appealed already
     */
    public function file(string $userId, ModerationVerdict $verdict, string $statement): Appeal
    {
        try {
            $appeal = $this->appealRepository->create($verdict->id, $userId, trim($statement));
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('The verdict was appealed already.');
        }

        AppealFiled::dispatch($appeal);

        return $appeal;
    }
}
