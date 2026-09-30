<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserSettings;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function findForSettings(string $id): ?User
    {
        return User::with(['settings', 'speakingLanguages'])->find($id);
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function updatePassword(User $user, string $password): User
    {
        $user->forceFill([
            'password' => $password,
            'remember_token' => Str::random(60),
        ])->save();

        return $user;
    }

    public function updateSettings(string $userId, array $settings): void
    {
        UserSettings::query()->whereKey($userId)->update($settings);
    }

    // `user_speaking_languages` has a composite primary key and no model; a delete + insert
    // replaces the set in one go (the caller wraps it in a transaction).
    public function syncSpeakingLanguages(string $userId, array $languageCodes): void
    {
        DB::table('user_speaking_languages')->where('user_id', $userId)->delete();

        DB::table('user_speaking_languages')->insert(array_map(
            fn (string $code) => ['user_id' => $userId, 'language_code' => $code],
            array_values(array_unique($languageCodes))
        ));
    }
}
