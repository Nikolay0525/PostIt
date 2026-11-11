<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Repositories\Contracts\SanctionRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Reading the notification list. A notification stores ids only; here they are turned into
 * what the list shows (a username, a post title), and the frontend builds the sentence from
 * them in the viewer's language.
 */
class NotificationService
{
    private const LIST_LIMIT = 20;

    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected PostRepositoryInterface $postRepository,
        protected SanctionRepositoryInterface $sanctionRepository,
    ) {}

    public function unreadCount(string $userId): int
    {
        return $this->userRepository->unreadNotificationsCount($userId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(string $userId): array
    {
        $notifications = $this->userRepository->latestNotifications($userId, self::LIST_LIMIT);

        // One query per kind of id, not per notification.
        $posts = $this->postRepository
            ->findManyWithGroup($notifications->pluck('data.post_id')->filter()->unique()->values()->all())
            ->keyBy('id');
        $authors = $this->userRepository
            ->findMany($notifications->pluck('data.author_id')->filter()->unique()->values()->all())
            ->keyBy('id');
        $banned = $this->sanctionRepository->bannedGroupIds($userId);

        return $notifications->map(fn (DatabaseNotification $n) => $this->present(
            $n,
            $posts->get($n->data['post_id'] ?? null),
            $authors->get($n->data['author_id'] ?? null),
            $banned,
        ))->values()->all();
    }

    /**
     * Marks the notification read, as the user opened it.
     *
     * @return string|null where it leads, or null when that is gone (e.g. the post was deleted)
     */
    public function open(string $userId, string $notificationId): ?string
    {
        $notification = $this->userRepository->findNotification($userId, $notificationId);

        if ($notification === null) {
            return null;
        }

        $this->userRepository->markNotificationRead($notification);

        $postId = $notification->data['post_id'] ?? null;
        $post = $postId ? $this->postRepository->findManyWithGroup([$postId])->first() : null;

        return $this->postUrl($post, $this->sanctionRepository->bannedGroupIds($userId));
    }

    public function markAllRead(string $userId): void
    {
        $this->userRepository->markAllNotificationsRead($userId);
    }

    /**
     * @param  list<string>  $bannedGroupIds  the groups the user is banned from
     */
    private function present(DatabaseNotification $notification, ?Post $post, ?User $author, array $bannedGroupIds): array
    {
        $type = NotificationType::tryFrom($notification->type);
        $postUrl = $this->postUrl($post, $bannedGroupIds);

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'important' => $type?->category() === NotificationCategory::Important,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toIso8601String(),
            'author' => $author?->username,
            // Null when the post can't be shown: deleted, or closed to the user. The list says so instead
            // of linking to nothing, and the title and the text never leave the server.
            'post_title' => $postUrl ? ($post->title ?: Str::words($post->article, 6)) : null,
            'post_unavailable' => $post !== null && ! $post->is_deleted && $postUrl === null,
            'has_link' => $postUrl !== null,
        ];
    }

    /**
     * Where the post is, or null when it is gone or not for this user to see: hidden under a
     * moderation case, removed by its verdict, or in a group they are banned from.
     *
     * @param  list<string>  $bannedGroupIds
     */
    private function postUrl(?Post $post, array $bannedGroupIds): ?string
    {
        if ($post === null || $post->is_deleted || $post->isConcealed() || in_array($post->group_id, $bannedGroupIds, true)) {
            return null;
        }

        return route('posts.show', [$post->group->slug, $post->slug]);
    }
}
