<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\PostRepositoryInterface;
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

        return $notifications->map(fn (DatabaseNotification $n) => $this->present(
            $n,
            $posts->get($n->data['post_id'] ?? null),
            $authors->get($n->data['author_id'] ?? null),
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

        return $this->postUrl($post);
    }

    public function markAllRead(string $userId): void
    {
        $this->userRepository->markAllNotificationsRead($userId);
    }

    private function present(DatabaseNotification $notification, ?Post $post, ?User $author): array
    {
        $type = NotificationType::tryFrom($notification->type);
        $postUrl = $this->postUrl($post);

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'important' => $type?->category() === NotificationCategory::Important,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toIso8601String(),
            'author' => $author?->username,
            // Null when the post is deleted: the list says so instead of linking to nothing.
            'post_title' => $postUrl ? ($post->title ?: Str::words($post->article, 6)) : null,
            'has_link' => $postUrl !== null,
        ];
    }

    private function postUrl(?Post $post): ?string
    {
        if ($post === null || $post->is_deleted) {
            return null;
        }

        return route('posts.show', [$post->group->slug, $post->slug]);
    }
}
