<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Enums\SanctionKind;
use App\Models\Punishment;

/**
 * The recipient was muted or banned in a group, and until when (nothing for good).
 */
class PunishmentStartedNotification extends BaseNotification
{
    public function __construct(private Punishment $punishment) {}

    public function type(): NotificationType
    {
        return $this->punishment->kind === SanctionKind::Ban ? NotificationType::GroupBan : NotificationType::GroupMute;
    }

    protected function sourceGroupId(): ?string
    {
        return $this->punishment->group_id;
    }

    protected function payload(): array
    {
        return [
            'group_id' => $this->punishment->group_id,
            'sanction' => $this->punishment->kind->key(),
            'until' => $this->punishment->ends_at?->toIso8601String(),
        ];
    }
}
