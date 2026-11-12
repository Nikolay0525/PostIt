<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Strike;

/**
 * A strike the recipient had in a group ran out by itself.
 */
class WarningExpiredNotification extends BaseNotification
{
    public function __construct(private Strike $strike) {}

    public function type(): NotificationType
    {
        return NotificationType::WarningRemoved;
    }

    protected function sourceGroupId(): ?string
    {
        return $this->strike->group_id;
    }

    protected function payload(): array
    {
        return [
            'group_id' => $this->strike->group_id,
            'sanction' => $this->strike->kind->key(),
        ];
    }
}
