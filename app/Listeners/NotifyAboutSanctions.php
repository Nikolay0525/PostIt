<?php

namespace App\Listeners;

use App\Events\PunishmentEnded;
use App\Events\PunishmentGiven;
use App\Events\StrikeGiven;
use App\Notifications\PunishmentEndedNotification;
use App\Notifications\PunishmentStartedNotification;
use App\Notifications\WarningReceivedNotification;

/**
 * Tells a member of what is done to their record in a group: a strike, the punishment it led to, and
 * the punishment's end. All of these are Important, so they cannot be turned off, only silenced.
 * Account-wide ones (no group) are not covered yet: there are none to give.
 */
class NotifyAboutSanctions
{
    public function handleStrike(StrikeGiven $event): void
    {
        $strike = $event->strike;

        if ($strike->group_id !== null) {
            $strike->user->notify(new WarningReceivedNotification($strike, $event->count, $event->limit));
        }
    }

    public function handlePunishment(PunishmentGiven $event): void
    {
        $punishment = $event->punishment;

        if ($punishment->group_id !== null) {
            $punishment->user->notify(new PunishmentStartedNotification($punishment));
        }
    }

    public function handleEnd(PunishmentEnded $event): void
    {
        $punishment = $event->punishment;

        if ($punishment->group_id !== null) {
            $punishment->user->notify(new PunishmentEndedNotification($punishment));
        }
    }
}
