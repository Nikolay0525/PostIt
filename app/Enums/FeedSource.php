<?php

namespace App\Enums;

/**
 * Inside the Following tab: posts from the groups you're in, or from the people you follow.
 * A followed person's post in one of your groups shows up in both lists — each is complete on
 * its own, and neither has duplicates within itself.
 */
enum FeedSource: string
{
    case Groups = 'groups';
    case People = 'people';
}
