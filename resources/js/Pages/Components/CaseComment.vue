<script setup>
import { inject } from 'vue';

// One comment of a case's conversation that is not the one judged: who said it (a label, never a
// name), when, and what. Its card is the caller's, as in CommentNode.
defineProps({
    // { label: { kind, number }, text, is_deleted, is_hidden, is_removed }; see CaseContextResource. No
    // date it was written: one more clue to whose it is.
    item: { type: Object, required: true },
});

// CaseContext's: "author", "participant N", or the author's own name on their own log entry.
const label = inject('caseLabel');
</script>

<template>
    <p class="post-meta-text">
        <!-- Never coloured: only the piece being judged is, not its author's other words around it. -->
        <span class="case-label">{{ label(item.label) }}</span>
        <span v-if="item.is_hidden || item.is_removed" class="case-flag">{{ $t(item.is_removed ? 'moderation.context.removed' : 'moderation.context.hidden') }}</span>
    </p>
    <p v-if="item.is_deleted" class="comment-deleted mt-2">{{ $t('moderation.context.deleted') }}</p>
    <p v-else class="post-body mt-2 whitespace-pre-line text-ink" dir="auto">{{ item.text }}</p>
</template>
