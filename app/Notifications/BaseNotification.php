<?php

namespace App\Notifications;

use App\Enums\NotificationStatus;
use App\Enums\NotificationType;
use App\Services\NotificationPreferenceService;
use Illuminate\Notifications\Notification;

/**
 * Everything kinds share. A kind only says what it is (type()) and what to store (payload());
 * whether it is sent at all, and whether it makes a sound, is decided here for every kind.
 */
abstract class BaseNotification extends Notification
{
    abstract public function type(): NotificationType;

    // Ids only: the frontend builds the text in the viewer's language.
    abstract protected function payload(): array;

    public function via(object $notifiable): array
    {
        // An empty list makes Laravel skip the notification: nothing is stored or sent.
        return $this->status($notifiable) === NotificationStatus::Disabled ? [] : ['database'];
    }

    // Stored in the `type` column instead of the class name, so it matches the settings' keys.
    public function databaseType(object $notifiable): string
    {
        return $this->type()->value;
    }

    public function toArray(object $notifiable): array
    {
        return [
            ...$this->payload(),
            // Decided once, on arrival: un-muting later does not make old ones loud.
            'silent' => $this->status($notifiable) === NotificationStatus::Muted,
        ];
    }

    private function status(object $notifiable): NotificationStatus
    {
        return app(NotificationPreferenceService::class)->statusFor($notifiable, $this->type());
    }
}
