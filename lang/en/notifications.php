<?php

return [

    'read_all' => 'Mark all as read',
    'empty' => 'No notifications yet',
    'loading' => 'Loading…',
    'load_failed' => 'Could not load notifications',
    'important' => 'Important',
    'post_deleted' => 'The post was deleted',
    'someone' => 'Someone',

    // Built by the frontend from the ids a notification stores; :user and :post are filled in.
    'kinds' => [
        'new_comment_under_post' => ':user commented on “:post”',
        'new_comment_answer' => ':user replied to your comment on “:post”',
        'unknown' => 'You have a new notification',
    ],

];
