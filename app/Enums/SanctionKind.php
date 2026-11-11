<?php

namespace App\Enums;

/**
 * What a rule leads to when it is broken, and so what a strike and a punishment are: a mute strike
 * adds up to a mute, a ban strike to a ban. They are counted apart.
 */
enum SanctionKind: int
{
    // The member can read but not contribute.
    case Mute = 0;
    // The member cannot take part in the group at all.
    case Ban = 1;

    public function key(): string
    {
        return match ($this) {
            self::Mute => 'mute',
            self::Ban => 'ban',
        };
    }

    public static function fromKey(?string $key): self
    {
        return match ($key) {
            'ban' => self::Ban,
            default => self::Mute,
        };
    }
}
