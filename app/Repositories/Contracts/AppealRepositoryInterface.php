<?php

namespace App\Repositories\Contracts;

use App\Models\Appeal;
use Illuminate\Database\UniqueConstraintViolationException;

interface AppealRepositoryInterface
{
    public function existsForVerdict(string $verdictId): bool;

    /**
     * Stores a new appeal, waiting for the jury.
     *
     * @throws UniqueConstraintViolationException when the verdict was appealed already
     */
    public function create(string $verdictId, string $appellantId, string $statement): Appeal;
}
