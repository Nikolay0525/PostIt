<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Models\UserSettings;
use App\Models\UserUserSubscription;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\Concerns\ExcludesSeenPosts;
use App\Repositories\Eloquent\Concerns\SearchesText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentUserRepository implements UserRepositoryInterface
{
    use ExcludesSeenPosts, SearchesText;

    public function find(string $id): ?User
    {
        return User::find($id);
    }

    public function findMany(array $ids): Collection
    {
        return User::query()->whereIn('id', $ids)->get();
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
        return User::withCount('followers')->with('counters:user_id,karma')->where('username', $username)->first();
    }

    public function search(string $term, int $perPage): LengthAwarePaginator
    {
        $query = $this->whereContains(User::withCount('followers'), ['users.username'], $term);

        return $this->orderByMatch($query, 'users.username', $term)
            ->orderByDesc('followers_count')
            ->orderBy('username')
            ->paginate($perPage);
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

    public function incrementReportsSent(string $userId): void
    {
        DB::table('user_counters')->where('user_id', $userId)->increment('reports_sent');
    }

    public function addKarma(string $userId, string $groupId, int $delta): void
    {
        DB::table('user_group_karma')->insertOrIgnore(['user_id' => $userId, 'group_id' => $groupId]);

        DB::table('user_group_karma')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->increment('karma', $delta);

        DB::table('user_counters')->where('user_id', $userId)->increment('karma', $delta);
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

    public function followedBy(string $followerId): Collection
    {
        return $this->followsOf($followerId)
            ->withCasts(['attached_on_feed' => 'boolean'])
            ->orderBy('users.username')
            ->get(['users.id', 'users.username', 'users.avatar_url', 'user_user_subscriptions.attached_on_feed']);
    }

    public function followsAttachedOnFeed(string $followerId): Collection
    {
        // select() before withCount(): with the join, get($columns) would be ignored in favour of `*`.
        return $this->followsOf($followerId)
            ->where('user_user_subscriptions.attached_on_feed', true)
            ->select(['users.id', 'users.username', 'users.avatar_url'])
            // New = posted since the follow began, not seen yet (ExcludesSeenPosts), and visible to
            // the follower like in the People feed: a public group, or a private one they're in —
            // otherwise the badge would give away posts they can't open.
            ->withCount(['posts as new_posts_count' => fn (Builder $posts) => $this->whereNotSeenBy($posts
                ->where('posts.is_deleted', false)
                ->whereColumn('posts.created_at', '>=', 'user_user_subscriptions.created_at')
                ->where(fn (Builder $visible) => $visible
                    ->whereIn('posts.group_id', fn ($groups) => $groups
                        ->select('id')
                        ->from('groups')
                        ->where('is_private', false))
                    ->orWhereIn('posts.group_id', fn ($groups) => $groups
                        ->select('group_id')
                        ->from('user_group_subscriptions')
                        ->where('user_id', $followerId))), $followerId)])
            ->orderByDesc('new_posts_count')
            ->orderBy('users.username')
            ->get();
    }

    public function syncFollowsAttachedOnFeed(string $followerId, array $authorIds): void
    {
        UserUserSubscription::query()
            ->where('user_follower_id', $followerId)
            ->update(['attached_on_feed' => false]);

        UserUserSubscription::query()
            ->where('user_follower_id', $followerId)
            ->whereIn('user_author_id', $authorIds)
            ->update(['attached_on_feed' => true]);
    }

    private function followsOf(string $followerId): Builder
    {
        return User::query()
            ->join('user_user_subscriptions', 'user_user_subscriptions.user_author_id', '=', 'users.id')
            ->where('user_user_subscriptions.user_follower_id', $followerId);
    }

    // Mute tables have composite primary keys and no models, like `user_speaking_languages`.
    public function muteGroupNotifications(string $userId, string $groupId): void
    {
        DB::table('notification_group_mutes')->insertOrIgnore(['user_id' => $userId, 'group_id' => $groupId]);
    }

    public function unmuteGroupNotifications(string $userId, string $groupId): void
    {
        DB::table('notification_group_mutes')->where('user_id', $userId)->where('group_id', $groupId)->delete();
    }

    public function mutePostNotifications(string $userId, string $postId): void
    {
        DB::table('notification_post_mutes')->insertOrIgnore(['user_id' => $userId, 'post_id' => $postId]);
    }

    public function unmutePostNotifications(string $userId, string $postId): void
    {
        DB::table('notification_post_mutes')->where('user_id', $userId)->where('post_id', $postId)->delete();
    }

    public function muteCommentNotifications(string $userId, string $commentId): void
    {
        DB::table('notification_comment_mutes')->insertOrIgnore(['user_id' => $userId, 'comment_id' => $commentId]);
    }

    public function unmuteCommentNotifications(string $userId, string $commentId): void
    {
        DB::table('notification_comment_mutes')->where('user_id', $userId)->where('comment_id', $commentId)->delete();
    }

    // Group → post → comment: a mute on any level of the source silences it.
    public function isNotificationSourceMuted(string $userId, ?string $groupId, ?string $postId, ?string $commentId = null): bool
    {
        $levels = [
            'notification_group_mutes' => ['group_id', $groupId],
            'notification_post_mutes' => ['post_id', $postId],
            'notification_comment_mutes' => ['comment_id', $commentId],
        ];

        foreach ($levels as $table => [$column, $id]) {
            if ($id !== null && DB::table($table)->where('user_id', $userId)->where($column, $id)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function latestNotifications(string $userId, int $limit): Collection
    {
        return $this->notificationsQuery($userId)->latest()->limit($limit)->get();
    }

    public function unreadNotificationsCount(string $userId): int
    {
        return $this->notificationsQuery($userId)->whereNull('read_at')->count();
    }

    public function findNotification(string $userId, string $notificationId): ?DatabaseNotification
    {
        return $this->notificationsQuery($userId)->find($notificationId);
    }

    public function markNotificationRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllNotificationsRead(string $userId): void
    {
        $this->notificationsQuery($userId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    // Laravel's `notifications` table is polymorphic; here the recipient is always a user.
    private function notificationsQuery(string $userId): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', (new User)->getMorphClass())
            ->where('notifiable_id', $userId);
    }

    private function followQuery(string $followerId, string $authorId): Builder
    {
        return UserUserSubscription::query()
            ->where('user_follower_id', $followerId)
            ->where('user_author_id', $authorId);
    }
}
