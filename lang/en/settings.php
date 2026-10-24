<?php

return [

    'title' => 'Settings',
    'saved' => 'Settings saved.',
    'languages' => 'Languages',
    'ui_language' => 'Interface language',
    'speaking' => 'Languages you speak',
    'speaking_hint' => 'Used to suggest groups in these languages. Up to :max.',
    'selected' => 'Selected languages',
    'remove_language' => 'Remove :name',
    'none_selected' => 'No languages selected.',
    'search_placeholder' => 'Search: English, Українська, de…',
    'limit_reached' => 'Limit reached — remove one to add another',
    'no_match' => 'No matching language.',

    'sections' => [
        'content' => 'Content',
        'privacy' => 'Privacy',
        'appearance' => 'Appearance',
        'subscriptions' => 'My subscriptions',
    ],

    'subscriptions' => [
        'hint' => 'Ticked groups and people appear on the side panel of the home page. A new subscription is ticked automatically. Ticks are saved with Save.',
        'amount' => 'How many to show on the panel',
        'amount_hint' => 'Separately for groups and for people, from 1 to :max. The rest are under “All”.',
        'search_placeholder' => 'Find a group or a person…',
        'groups' => 'Groups',
        'people' => 'People',
        'no_groups' => 'You are not in any group yet.',
        'no_people' => 'You don’t follow anyone yet.',
        'no_match' => 'Nothing found.',
        'attached_count' => ':attached of :total on the panel',
    ],

    'toggles' => [
        'show_swear_words' => [
            'label' => 'Show swear words',
            'hint' => 'Otherwise they are masked in posts and comments.',
        ],
        'show_adult_content' => [
            'label' => 'Show adult (18+) content',
            'hint' => 'Only available if you are 18 or older.',
        ],
        'allow_messages' => [
            'label' => 'Allow direct messages',
            'hint' => 'Other users can write to you privately.',
        ],
        'enable_cookies' => [
            'label' => 'Allow optional cookies',
            'hint' => 'Cookies needed to keep you signed in are always used.',
        ],
    ],

    'theme' => [
        'clock' => [
            'label' => 'By time of day',
            'hint' => 'Dark from 20:00 to 07:00 by your local time. The button in the top bar switches it until the next change.',
        ],
        'browser' => [
            'label' => 'Like my browser',
            'hint' => 'Follows your system or browser setting. The button in the top bar switches it for 12 hours.',
        ],
        'manual' => [
            'label' => 'Manual',
            'hint' => 'Switch it with the button in the top bar; your choice is saved.',
        ],
    ],

    'unsaved' => 'Unsaved changes',
    'save' => 'Save',
    'saving' => 'Saving…',

];
