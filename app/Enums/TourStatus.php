<?php

namespace App\Enums;

/**
 * The user's answer to a tour, kept so it is not offered again. "Later" is not stored:
 * the tour is simply offered again on the next visit.
 */
enum TourStatus: string
{
    case Completed = 'completed';
    case Declined = 'declined';
}
