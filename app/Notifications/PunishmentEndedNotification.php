<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Enums\SanctionKind;
use App\Models\Punishment;

/**
 * The recipient's mute or ban in a group is over.
 */
class PunishmentEndedNotification extends BaseNotification
{
    public function __construct(private Punishment $punishment) {}

    public function type(): NotificationType
    {
        return $this->punishment->kind === SanctionKind::Ban ? NotificationType::GroupUnban : NotificationType::GroupUnmute;
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
        ];
    }
}
