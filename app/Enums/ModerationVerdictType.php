<?php

namespace App\Enums;

enum ModerationVerdictType: int
{
    // The case is closed and the content is shown again.
    case NoViolation = 0;
    // The content is removed; nothing more happens to its author.
    case Remove = 1;
    // The content is removed, and the author also gets a warning.
    case Warning = 2;

    /** Whether the content stays removed, so the verdict must say which rule it broke and where. */
    public function punishes(): bool
    {
        return $this !== self::NoViolation;
    }
}
