<?php

return [

    'read_all' => 'Mark all as read',
    'empty' => 'No notifications yet',
    'loading' => 'Loading…',
    'load_failed' => 'Could not load notifications',
    'important' => 'Important',
    'post_deleted' => 'The post was deleted',
    'post_unavailable' => 'The post is not available',
    'someone' => 'Someone',
    'a_group' => 'a group',

    // The word the kinds below put into "towards a ...".
    'sanctions' => [
        'mute' => 'mute',
        'ban' => 'ban',
    ],

    // Built by the frontend from the ids a notification stores; :user and :post are filled in.
    'kinds' => [
        'new_comment_under_post' => ':user commented on “:post”',
        'new_comment_answer' => ':user replied to your comment on “:post”',
        'received_warning' => 'Warning in “:group”: strike :count of :limit towards a :sanction. This one lasts until :date',
        'group_mute' => 'You are muted in “:group” until :date: you can read, but not post, comment or vote',
        'group_mute_forever' => 'You are muted in “:group” for good: you can read, but not post, comment or vote',
        'warning_removed' => 'Your warning in “:group” has expired',
        'group_unmute' => 'Your mute in “:group” is over',
        'group_ban' => 'You are banned from “:group” until :date: its content is closed to you',
        'group_ban_forever' => 'You are banned from “:group” for good: its content is closed to you',
        'group_unban' => 'Your ban from “:group” is over',
        'unknown' => 'You have a new notification',
    ],

];
