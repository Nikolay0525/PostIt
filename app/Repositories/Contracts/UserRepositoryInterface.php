<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;

    public function findWithSettingsAndCounters(string $id): ?User;

    /**
     * The returned user carries `settings` and `speakingLanguages`.
     */
    public function findForSettings(string $id): ?User;

    /**
     * For the public profile page: the returned user carries the `followers_count` aggregate.
     */
    public function findForProfile(string $username): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function updatePassword(User $user, string $password): User;

    /**
     * @param  array<string, mixed>  $settings  columns of `user_settings`
     */
    public function updateSettings(string $userId, array $settings): void;

    /**
     * Replaces the user's speaking languages with exactly these codes.
     *
     * @param  list<string>  $languageCodes
     */
    public function syncSpeakingLanguages(string $userId, array $languageCodes): void;

    /**
     * Idempotent: following an author already followed changes nothing.
     */
    public function follow(string $followerId, string $authorId): void;

    public function unfollow(string $followerId, string $authorId): void;

    public function isFollowing(string $followerId, string $authorId): bool;

    public function followersCount(string $authorId): int;
}
