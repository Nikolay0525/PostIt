<?php

namespace App\Services;

use App\Enums\NotificationCategory;
use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Models\User;

/**
 * Decides, for one recipient and one kind, whether a notification is sent and whether it is silent.
 * Group and post mutes will be added here, so notification classes never need to change.
 */
class NotificationPreferenceService
{
    public function statusFor(User $user, NotificationType $type): NotificationStatus
    {
        $settings = $user->settings;

        $status = NotificationStatus::tryFrom($settings->notification_preferences[$type->value] ?? '')
            ?? NotificationStatus::Enabled;

        // Important kinds cannot be turned off, only silenced: a warning or a ban always arrives.
        if ($status === NotificationStatus::Disabled && $type->category() === NotificationCategory::Important) {
            $status = NotificationStatus::Muted;
        }

        // Muting everything only silences: a kind the user turned off stays off.
        if ($status === NotificationStatus::Enabled && $this->isEverythingMuted($user)) {
            $status = NotificationStatus::Muted;
        }

        return $status;
    }

    private function isEverythingMuted(User $user): bool
    {
        $settings = $user->settings;

        return $settings->muted_forever || $settings->muted_until?->isFuture();
    }
}
