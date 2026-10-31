<?php

return [

    'show' => [
        'members' => ':num member|:num members',
        'private' => 'Private',
        'subscribe' => 'Subscribe',
        'subscribed' => 'Subscribed',
        'request' => 'Request to join',
        'request_sent' => 'Request sent',
        'rules' => 'Rules',
        'rule_example' => 'Example: :example',
        'read_all' => 'Mark all as read',
        'about' => 'About the group',
        'edit' => 'Edit',
        'language' => 'Group language',
        'actions_label' => 'Group actions',
        'filters' => 'Filters',
        'private_notice' => 'This group is private. Posts are visible to members only.',
        'create_post' => '+ New post',
        'join' => 'Join',
        'join_to_post' => ':join to post.',
        'lucky' => 'I\'m feeling lucky',
        'sort_label' => 'Sort posts by',
        'sort' => [
            'newest' => 'Newest',
            'top' => 'Top',
            'controversy' => 'Controversy',
        ],
        'none' => 'No posts in this group yet.',
    ],

    'create' => [
        'title' => 'New group',
        'step' => 'Step :step of 2 · :name',
        'basics' => 'Basics',
        'rules' => 'Rules',
        'slug' => 'Slug',
        'slug_hint' => 'The group\'s permanent short name, in Latin letters: lowercase letters, digits and hyphens, up to :max characters.',
        'name' => 'Name',
        'description' => 'Description',
        'description_placeholder' => 'What is this group about?',
        'language' => 'Language',
        'private' => 'Private group — only members can see its posts',
        'next' => 'Next: rules',
        'submit' => 'Create group',
        'submitting' => 'Creating…',
    ],

    'edit' => [
        'title' => 'Edit group',
        'basics' => 'Basics',
        'rules_versioned' => 'A change to the rules is saved as a new version; the old one is kept, so earlier moderation decisions are still judged by the rules in force at the time.',
        'save' => 'Save',
        'saving' => 'Saving…',
    ],

    // The rules list, on both the create and the edit page.
    'rules_editor' => [
        'hint' => 'Optional. One rule per item; add an example where the rule alone could be read more than one way.',
        'remove' => 'Remove rule',
        'remove_n' => 'Remove rule :n',
        'rule_n' => 'Rule :n',
        'rule_placeholder' => 'Rule, e.g. No spam.',
        'example_n' => 'Example for rule :n',
        'example_placeholder' => 'Example (optional), e.g. Posting the same link in several threads.',
        'add' => '+ Add rule',
        'limit' => 'A group can have up to :max rules.',
    ],

];
