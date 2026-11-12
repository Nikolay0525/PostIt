<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationType;
use App\Models\Group;
use App\Models\Post;
use App\Models\User;
use App\Repositories\Contracts\GroupRepositoryInterface;
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
        protected GroupRepositoryInterface $groupRepository,
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
        $groups = $this->groupRepository
            ->findMany($notifications->pluck('data.group_id')->filter()->unique()->values()->all())
            ->keyBy('id');
        $banned = $this->sanctionRepository->bannedGroupIds($userId);

        return $notifications->map(fn (DatabaseNotification $n) => $this->present(
            $n,
            $posts->get($n->data['post_id'] ?? null),
            $authors->get($n->data['author_id'] ?? null),
            $groups->get($n->data['group_id'] ?? null),
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

        // A notification about what happened to the user in a group leads to the group, whose page
        // says what applies to them; one about a post leads to the post.
        if (isset($notification->data['group_id']) && ! isset($notification->data['post_id'])) {
            $slug = $this->groupRepository->findMany([$notification->data['group_id']])->first()?->slug;

            return $slug === null ? null : route('groups.show', $slug);
        }

        $postId = $notification->data['post_id'] ?? null;
        $post = $postId ? $this->postRepository->findManyWithGroup([$postId])->first() : null;

        return $this->postUrl(
            $post,
            $this->sanctionRepository->bannedGroupIds($userId),
            NotificationType::tryFrom($notification->type) === NotificationType::ContentHidden
        );
    }

    public function markAllRead(string $userId): void
    {
        $this->userRepository->markAllNotificationsRead($userId);
    }

    /**
     * @param  list<string>  $bannedGroupIds  the groups the user is banned from
     */
    private function present(DatabaseNotification $notification, ?Post $post, ?User $author, ?Group $group, array $bannedGroupIds): array
    {
        $type = NotificationType::tryFrom($notification->type);

        // What happened to the user in a group (a strike, a mute, a ban): about the group, not a post.
        if (isset($notification->data['group_id']) && ! isset($notification->data['post_id'])) {
            return $this->presentGroupMatter($notification, $type, $group);
        }

        // A notice to the author about their own hidden post is the one that may still name and open it.
        $postUrl = $this->postUrl($post, $bannedGroupIds, $type === NotificationType::ContentHidden);

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
            // Plain values the text is built from: whether it was a post or a comment.
            'details' => array_intersect_key($notification->data, array_flip(['target'])),
            'has_link' => $postUrl !== null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentGroupMatter(DatabaseNotification $notification, ?NotificationType $type, ?Group $group): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'important' => $type?->category() === NotificationCategory::Important,
            'read' => $notification->read_at !== null,
            'created_at' => $notification->created_at->toIso8601String(),
            'author' => null,
            'post_title' => null,
            'post_unavailable' => false,
            'group_name' => $group?->name,
            // Plain values the text is built from; nothing here names a moderator.
            'details' => array_intersect_key($notification->data, array_flip(['sanction', 'until', 'count', 'limit'])),
            'has_link' => $group !== null,
        ];
    }

    /**
     * Where the post is, or null when it is gone or not for this user to see: hidden under a
     * moderation case, removed by its verdict, or in a group they are banned from.
     *
     * @param  list<string>  $bannedGroupIds
     * @param  bool  $own  the notice is to the post's own author, who may still open a hidden one
     */
    private function postUrl(?Post $post, array $bannedGroupIds, bool $own = false): ?string
    {
        if ($post === null || $post->is_deleted || (! $own && $post->isConcealed()) || in_array($post->group_id, $bannedGroupIds, true)) {
            return null;
        }

        return route('posts.show', [$post->group->slug, $post->slug]);
    }
}
