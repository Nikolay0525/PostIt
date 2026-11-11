<?php

// What the platform decides about strikes and punishments. The same for every group and every user,
// so none of it can be tuned to protect or hit particular people. The values below are starting
// ones, to be set by the site's management.
return [

    // How many days a new strike lasts, by how many strikes of its kind the member already has
    // active: none (first), one (second), two (third)… The last number is used for all further ones.
    'strike_lifetime_days' => [1, 7, 30],

    // How many days a punishment lasts, by how many punishments of its kind the member has already
    // had within the memory period below: none (first), one (second)… `null` is permanent. The last
    // step is used for all further ones.
    'punishment_days' => [1, 7, 30, null],

    // Only punishments this recent count towards the step of a new one, so someone who has reformed
    // starts from the first step again.
    'punishment_memory_days' => 180,

    // The most active strikes of a kind that a group may allow before the punishment. A group's owner
    // can lower it for their group (stricter), never raise it.
    'strike_limit' => [
        'mute' => 3,
        'ban' => 3,
    ],

];
