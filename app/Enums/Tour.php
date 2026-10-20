<?php

namespace App\Enums;

/**
 * Interface tours, one per kind of page. Each is answered separately: finishing one does not
 * count for another. Own and someone else's profile are separate, as their pages differ.
 */
enum Tour: string
{
    case Navigation = 'navigation';
    case Feed = 'feed';
    case Group = 'group';
    case Post = 'post';
    case OwnProfile = 'own_profile';
    case OtherProfile = 'other_profile';
}
