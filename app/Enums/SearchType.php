<?php

namespace App\Enums;

/**
 * The search page tabs. All shows a few groups and people above the posts.
 */
enum SearchType: string
{
    case All = 'all';
    case Posts = 'posts';
    case Groups = 'groups';
    case People = 'people';
}
