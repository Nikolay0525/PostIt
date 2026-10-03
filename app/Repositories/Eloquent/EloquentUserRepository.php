<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserSettings;
use App\Models\UserUserSubscription;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
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

    public function findForProfile(string $username): ?User
    {
        return User::withCount('followers')->where('username', $username)->first();
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

    // `UserUserSubscription` has a composite primary key, which Eloquent does not support
    // natively: same insertOrIgnore()/WHERE-scoped delete() as EloquentGroupRepository::subscribe().
    public function follow(string $followerId, string $authorId): void
    {
        UserUserSubscription::query()->insertOrIgnore([
            'user_follower_id' => $followerId,
            'user_author_id' => $authorId,
            'created_at' => now(),
        ]);
    }

    public function unfollow(string $followerId, string $authorId): void
    {
        $this->followQuery($followerId, $authorId)->delete();
    }

    public function isFollowing(string $followerId, string $authorId): bool
    {
        return $this->followQuery($followerId, $authorId)->exists();
    }

    public function followersCount(string $authorId): int
    {
        return UserUserSubscription::query()->where('user_author_id', $authorId)->count();
    }

    private function followQuery(string $followerId, string $authorId): Builder
    {
        return UserUserSubscription::query()
            ->where('user_follower_id', $followerId)
            ->where('user_author_id', $authorId);
    }
}
