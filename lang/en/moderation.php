<?php

return [

    'title' => 'Moderation',
    'queue' => 'Reports to review',
    'none' => 'Nothing is waiting for review.',
    'post' => 'Post',
    'comment' => 'Comment',
    'comment_on' => 'On the post “:title”',
    'untitled_post' => 'a post without a title',
    'gone' => 'This content is no longer available.',
    'reports' => ':num report|:num reports',
    'reported_for' => 'Reported for',
    'kind' => [
        'group_rule' => 'Breaks the group\'s rule',
        'platform' => 'Breaks the platform\'s rules',
        'other' => 'Something else',
    ],
    'case' => [
        'title' => 'Case',
    ],
    'author' => [
        'pending' => [
            'post' => 'Your post got many reports, so we hid it for now. Wait for the moderators\' decision.',
            'comment' => 'Your comment got many reports, so we hid it for now. Wait for the moderators\' decision.',
        ],
        'removed' => [
            'post' => 'The moderators removed your post.',
            'comment' => 'The moderators removed your comment.',
        ],
        'warned' => [
            'post' => 'The moderators removed your post and gave you a warning.',
            'comment' => 'The moderators removed your comment and gave you a warning.',
        ],
        'rule' => 'Rule :number: “:text”.',
        'rule_kind' => [
            'mute' => 'Breaking it counts towards a mute.',
            'ban' => 'Breaking it counts towards a ban.',
        ],
        'reason' => 'The moderator\'s reason',
        'marked' => 'The places where the rule was broken are marked in your text below.',
    ],
    'log' => [
        'title' => 'Moderation log',
        'none' => 'No actions yet.',
    ],
    'loading' => 'Loading…',

];
