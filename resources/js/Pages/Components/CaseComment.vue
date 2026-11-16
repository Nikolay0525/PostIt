<script setup>
import { trans } from 'laravel-vue-i18n';
import { timeAgo } from '@/utils/format';

// One comment of a case's conversation that is not the one judged: who said it (a label, never a
// name), when, and what. Its card is the caller's, as in CommentNode.
defineProps({
    // { label: { kind, number }, created_at, text, is_deleted, is_hidden, is_removed }; see CaseContextResource.
    item: { type: Object, required: true },
});

const label = ({ kind, number }) => (kind === 'author'
    ? trans('moderation.context.author')
    : trans('moderation.context.participant', { number }));
</script>

<template>
    <p class="post-meta-text">
        <span class="case-label" :class="{ 'is-author': item.label.kind === 'author' }">{{ label(item.label) }}</span>
        <span aria-hidden="true"> · </span>
        <time :datetime="item.created_at">{{ timeAgo(item.created_at) }}</time>
        <span v-if="item.is_hidden || item.is_removed" class="case-flag">{{ $t(item.is_removed ? 'moderation.context.removed' : 'moderation.context.hidden') }}</span>
    </p>
    <p v-if="item.is_deleted" class="comment-deleted mt-2">{{ $t('moderation.context.deleted') }}</p>
    <p v-else class="post-body mt-2 whitespace-pre-line text-ink" dir="auto">{{ item.text }}</p>
</template>
