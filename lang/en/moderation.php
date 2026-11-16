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
        'hidden' => 'hidden :time',
        'no_reports' => 'No reports are attached to this case.',
        'view' => 'View the case',
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
        'collapsed' => [
            'pending' => [
                'comment' => 'Your comment is hidden: the moderators have not decided yet',
            ],
            'removed' => [
                'comment' => 'Your comment was removed',
            ],
            'warned' => [
                'comment' => 'Your comment was removed, and you got a warning',
            ],
        ],
        'rule' => 'Rule :number: “:text”.',
        'rule_kind' => [
            'mute' => 'Breaking it counts towards a mute.',
            'ban' => 'Breaking it counts towards a ban.',
        ],
        'reason' => 'The moderator\'s reason',
        'marked' => 'The places where the rule was broken are marked in your text below.',
    ],
    'appeal' => [
        'open' => 'Appeal this decision',
        'until' => 'You can appeal it until :date.',
        'placeholder' => 'Why do you think the decision is wrong?',
        'send' => 'Send the appeal',
        'sending' => 'Sending…',
        'failed' => 'Could not send the appeal',
        'your_statement' => 'You wrote',
        'closed' => 'The time to appeal this decision ended on :date.',
        'status' => [
            'pending' => 'Your appeal was sent and is waiting to be reviewed. The content stays removed meanwhile.',
            'overturned' => 'Your appeal was granted: the decision was taken back.',
            'upheld' => 'Your appeal was rejected: the decision stands.',
        ],
    ],
    'context' => [
        'author' => 'Author',
        'participant' => 'Participant :number',
        'unfold' => 'Show the whole post',
        'fold' => 'Fold the post',
        'reported' => 'reported',
        'deleted' => '[deleted by its author]',
        'hidden' => 'hidden',
        'removed' => 'removed',
    ],
    'verdict' => [
        'title' => 'Verdict',
        'grounds' => 'Grounds',
        'types' => [
            'no_violation' => 'No violation',
            'remove' => 'Remove',
            'warning' => 'Warning',
        ],
        'type_hints' => [
            'no_violation' => 'The content is shown again, and nobody is punished.',
            'remove' => 'The content is removed, and nothing more happens to its author.',
            'warning' => 'The content is removed, and its author gets a strike.',
        ],
        'no_rules' => 'The group has no rules, so nothing here can be punished: the case can only be closed as "no violation".',
        'rule' => 'The rule that was broken',
        'rule_kind' => [
            'mute' => 'A warning under it is a strike towards a mute.',
            'ban' => 'A warning under it is a strike towards a ban.',
        ],
        'marks' => 'Where it is broken',
        'marks_hint' => 'Select the words that break the rule in the text being judged: a "Mark" button appears over them. A mark is taken off with its ✕ below, or by clicking it in the text.',
        'mark' => 'Mark',
        'no_marks' => 'Nothing is marked yet.',
        'unmark' => 'Remove the mark',
        'reason' => 'Your reason',
        'reason_placeholder' => 'How do you read it against the rule? Everyone will see this.',
        'submit' => 'Give the verdict',
        'submitting' => 'Giving…',
        'unknown_rule' => 'The group has no such rule.',
        'invalid' => 'The verdict is not complete: check that the marked words are in the text.',
        'given' => 'The verdict was given.',
        'already' => 'Another moderator decided that case first.',
    ],
    'log' => [
        'title' => 'Moderation log',
        'none' => 'No actions yet.',
    ],
    'loading' => 'Loading…',

];
