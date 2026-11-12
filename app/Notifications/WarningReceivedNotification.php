<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Strike;

/**
 * The recipient got a strike in a group. Says which kind it is, how many they have now out of how
 * many it takes to be punished, and until when this one lasts: so nothing about it is a surprise.
 * Ids and plain values only; the frontend builds the text.
 */
class WarningReceivedNotification extends BaseNotification
{
    public function __construct(private Strike $strike, private int $count, private int $limit) {}

    public function type(): NotificationType
    {
        return NotificationType::ReceivedWarning;
    }

    protected function sourceGroupId(): ?string
    {
        return $this->strike->group_id;
    }

    protected function payload(): array
    {
        return [
            'group_id' => $this->strike->group_id,
            'moderation_verdict_id' => $this->strike->moderation_verdict_id,
            'sanction' => $this->strike->kind->key(),
            'count' => $this->count,
            'limit' => $this->limit,
            'until' => $this->strike->expires_at->toIso8601String(),
        ];
    }
}
