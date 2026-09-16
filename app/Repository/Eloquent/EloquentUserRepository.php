<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function findWithSettingsAndCounters(string $id): ?User
    {
        return User::with(['settings', 'counters'])->find($id);
    }

    public function create(array $data): User
    {
        return User::create($data); // тригерить UserObserver::created()
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }
}