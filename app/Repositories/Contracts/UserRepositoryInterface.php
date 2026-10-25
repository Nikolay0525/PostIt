<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

interface UserRepositoryInterface
{
    public function find(string $id): ?User;

    /**
     * @param  list<string>  $ids
     * @return Collection<int, User>
     */
    public function findMany(array $ids): Collection;

    public function findWithSettingsAndCounters(string $id): ?User;

    /**
     * The returned user carries `settings` and `speakingLanguages`.
     */
    public function findForSettings(string $id): ?User;

    /**
     * For the public profile page: the returned user carries the `followers_count` aggregate.
     */
    public function findForProfile(string $username): ?User;

    /**
     * Users whose username contains the term: the exact name first, then "starts with", then
     * the rest, the most followed first within each. Each carries `followers_count`.
     */
    public function search(string $term, int $perPage): LengthAwarePaginator;

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

    /**
     * The people the user follows, by username — for the user's own subscriptions menu. Each
     * carries `attached_on_feed`: whether they are on the user's side panel.
     *
     * @return Collection<int, User>
     */
    public function followedBy(string $followerId): Collection;

    /**
     * Every person the follower attached to their side panel, by username.
     *
     * @return Collection<int, User>
     */
    public function followsAttachedOnFeed(string $followerId): Collection;

    /**
     * Shows or hides the author on the follower's side panel. A no-op when not following them.
     */
    public function setFollowAttachedOnFeed(string $followerId, string $authorId, bool $attached): void;

    /**
     * Exactly these of the people the follower follows are on their side panel; the rest are
     * taken off. An id of someone not followed is ignored. The caller wraps it in a transaction.
     *
     * @param  list<string>  $authorIds
     */
    public function syncFollowsAttachedOnFeed(string $followerId, array $authorIds): void;

    /**
     * Idempotent: muting a group already muted changes nothing.
     */
    public function muteGroupNotifications(string $userId, string $groupId): void;

    public function unmuteGroupNotifications(string $userId, string $groupId): void;

    /**
     * Idempotent: muting a post already muted changes nothing.
     */
    public function mutePostNotifications(string $userId, string $postId): void;

    public function unmutePostNotifications(string $userId, string $postId): void;

    /**
     * Idempotent: muting a comment already muted changes nothing.
     */
    public function muteCommentNotifications(string $userId, string $commentId): void;

    public function unmuteCommentNotifications(string $userId, string $commentId): void;

    /**
     * True when the user muted the group, the post or the comment a notification comes from.
     */
    public function isNotificationSourceMuted(string $userId, ?string $groupId, ?string $postId, ?string $commentId = null): bool;

    /**
     * The user's newest notifications, read and unread.
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function latestNotifications(string $userId, int $limit): Collection;

    public function unreadNotificationsCount(string $userId): int;

    public function findNotification(string $userId, string $notificationId): ?DatabaseNotification;

    public function markNotificationRead(DatabaseNotification $notification): void;

    public function markAllNotificationsRead(string $userId): void;
}
