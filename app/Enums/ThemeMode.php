<?php

namespace App\Enums;

/**
 * How a user's theme is chosen. The choosing itself happens in the browser
 * (resources/views/partials/theme-script.blade.php), which knows the local time and system setting.
 */
enum ThemeMode: string
{
    // Dark at night by the browser's local time.
    case Clock = 'clock';
    // Follows the OS/browser light/dark setting.
    case Browser = 'browser';
    // The user's own choice, user_settings.dark_theme, switched with the navbar button.
    case Manual = 'manual';
}
