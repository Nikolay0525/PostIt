<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;
    public function findWithSettingsAndCounters(string $id): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function updatePassword(User $user, string $password): User;
}