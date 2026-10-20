<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Decides, for one recipient and one kind, whether a notification is sent and whether it is silent,
 * and keeps the groups and posts the user muted.
 */
class NotificationPreferenceService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    /**
     * $groupId and $postId are where the notification comes from; null when it has no such source.
     */
    public function statusFor(User $user, NotificationType $type, ?string $groupId = null, ?string $postId = null): NotificationStatus
    {
        $settings = $user->settings;

        $status = NotificationStatus::tryFrom($settings->notification_preferences[$type->value] ?? '')
            ?? NotificationStatus::Enabled;

        // Important kinds cannot be turned off, only silenced: a warning or a ban always arrives.
        if ($status === NotificationStatus::Disabled && $type->category() === NotificationCategory::Important) {
            $status = NotificationStatus::Muted;
        }

        // Every mute below only silences: a kind the user turned off stays off.
        if ($status === NotificationStatus::Enabled
            && ($this->isEverythingMuted($user)
                || $this->userRepository->isNotificationSourceMuted($user->id, $groupId, $postId))) {
            $status = NotificationStatus::Muted;
        }

        return $status;
    }

    public function muteGroup(string $userId, string $groupId): void
    {
        $this->userRepository->muteGroupNotifications($userId, $groupId);
    }

    public function unmuteGroup(string $userId, string $groupId): void
    {
        $this->userRepository->unmuteGroupNotifications($userId, $groupId);
    }

    public function mutePost(string $userId, string $postId): void
    {
        $this->userRepository->mutePostNotifications($userId, $postId);
    }

    public function unmutePost(string $userId, string $postId): void
    {
        $this->userRepository->unmutePostNotifications($userId, $postId);
    }

    private function isEverythingMuted(User $user): bool
    {
        $settings = $user->settings;

        return $settings->muted_forever || $settings->muted_until?->isFuture();
    }
}
