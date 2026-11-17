<script setup>
import { inject } from 'vue';
import CaseComment from '@/Pages/Components/CaseComment.vue';
import HighlightedText from '@/Pages/Components/HighlightedText.vue';
import { fullDate, timeAgo } from '@/utils/format';

// A case's thread, drawn the way a thread is on a post's page — the same .comment-node, .comment-card
// and .comment-replies, so the same line on the left — but only one branch of it: the first of the
// `ancestors`, with the rest nested under it, down to the comment being judged, and the replies to it
// under that. It renders itself one level deeper for each ancestor.
defineOptions({ name: 'CaseThread' });

defineProps({
    // The comments the judged one answers, the top one first.
    ancestors: { type: Array, required: true },
    // The judged comment; its text is where the moderator selects what to mark.
    comment: { type: Object, required: true },
    replies: { type: Array, required: true },
    // [{ start, end, quote }]
    marks: { type: Array, default: () => [] },
    // { hidden_at, reports_count }: on the judged comment's line, after its author and date.
    judgedInfo: { type: Object, default: null },
});

// CaseContext's: where the judged text is, for marking; and how a person is labelled.
const setTarget = inject('caseTarget');
const label = inject('caseLabel');

</script>

<template>
    <article class="comment-node">
        <template v-if="ancestors.length">
            <div class="comment-card">
                <CaseComment :item="ancestors[0]" />
            </div>
            <div class="comment-replies">
                <CaseThread :ancestors="ancestors.slice(1)" :comment="comment" :replies="replies" :marks="marks" :judged-info="judgedInfo" />
            </div>
        </template>

        <template v-else>
            <div class="comment-card case-reported">
                <p class="post-meta-text">
                    <span class="case-label is-judged">{{ label({ kind: 'author' }) }}</span>
                    <!-- No date it was written: one more clue to whose it is. Only since when it is hidden. -->
                    <template v-if="judgedInfo">
                        <span aria-hidden="true"> · </span>
                        <time :datetime="judgedInfo.hidden_at" :title="fullDate(judgedInfo.hidden_at)">{{ $t('moderation.case.hidden', { time: timeAgo(judgedInfo.hidden_at) }) }}</time>
                        <span aria-hidden="true"> · </span>
                        {{ $tChoice('moderation.reports', judgedInfo.reports_count, { num: judgedInfo.reports_count }) }}
                    </template>
                </p>
                <div :ref="(el) => el && setTarget(el)" data-mark-field="body" class="post-body mt-2 text-ink">
                    <HighlightedText :text="comment.text" :marks="marks" />
                </div>
            </div>

            <div v-if="replies.length" class="comment-replies">
                <article v-for="item in replies" :key="item.id" class="comment-node">
                    <div class="comment-card">
                        <CaseComment :item="item" />
                    </div>
                </article>
            </div>
        </template>
    </article>
</template>
