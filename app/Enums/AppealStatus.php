<?php

namespace App\Enums;

enum AppealStatus: int
{
    // Waiting for the jury.
    case Pending = 0;
    // The jury agreed that the action was wrong: it is taken back.
    case Overturned = 1;
    // The action stands.
    case Upheld = 2;

    public function key(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Overturned => 'overturned',
            self::Upheld => 'upheld',
        };
    }
}
