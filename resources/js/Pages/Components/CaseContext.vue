<script setup>
import { computed, provide, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import CaseThread from '@/Pages/Components/CaseThread.vue';
import HighlightedText from '@/Pages/Components/HighlightedText.vue';
import { excerpt, fullDate, timeAgo } from '@/utils/format';

// The conversation a case's content stands in, as a moderator judges it (and, later, the log and the
// jury): for a comment, the post it is under — folded, to unfold — the comments it answers and the
// replies to it; for a post, the post itself. People are labels, never names: the server sends none.
// It looks like the post and comments do elsewhere (the same styles), without what a judge has no use
// for: votes, replies, menus.
//
// The judged text — the reported comment, or the post — is plain text with the `marks` picked out, and
// `target` (exposed) is where a moderator selects what to mark.
const props = defineProps({
    // { post, ancestors, comment, replies }; see CaseContextResource.
    context: { type: Object, required: true },
    // [{ start, end, quote }]
    marks: { type: Array, default: () => [] },
    // { hidden_at, reports_count }: shown on the judged content's line, after its author and date.
    // None where the context is only read.
    judgedInfo: { type: Object, default: null },
});

const target = ref(null);
defineExpose({ target });

// The judged comment is drawn deep in CaseThread; it hands its text's element back here.
provide('caseTarget', (el) => (target.value = el));

const onPost = computed(() => props.context.comment === null);
const unfolded = ref(false);

const label = ({ kind, number }) => (kind === 'author'
    ? trans('moderation.context.author')
    : trans('moderation.context.participant', { number }));
</script>

<template>
    <div class="feed-list">
        <!-- The post: the judged content in a case on a post; otherwise what the conversation is under. -->
        <article v-if="context.post" class="post-card" :class="{ 'case-reported': onPost }">
            <p class="post-meta-text">
                <span class="case-label" :class="{ 'is-author': context.post.label.kind === 'author' }">{{ label(context.post.label) }}</span>
                <span aria-hidden="true"> · </span>
                <time :datetime="context.post.created_at" :title="fullDate(context.post.created_at)">{{ timeAgo(context.post.created_at) }}</time>
                <template v-if="onPost && judgedInfo">
                    <span aria-hidden="true"> · </span>
                    <time :datetime="judgedInfo.hidden_at" :title="fullDate(judgedInfo.hidden_at)">{{ $t('moderation.case.hidden', { time: timeAgo(judgedInfo.hidden_at) }) }}</time>
                    <span aria-hidden="true"> · </span>
                    {{ $tChoice('moderation.reports', judgedInfo.reports_count, { num: judgedInfo.reports_count }) }}
                </template>
            </p>

            <p v-if="context.post.is_deleted" class="comment-deleted mt-4">{{ $t('moderation.context.deleted') }}</p>

            <template v-else>
                <h2 v-if="context.post.title" class="post-title" dir="auto">{{ context.post.title }}</h2>

                <!-- Judged: plain text, so that what is selected lines up with the text the marks are counted in. -->
                <div v-if="onPost" ref="target" class="post-content" :class="{ 'mt-4': !context.post.title }">
                    <HighlightedText :text="context.post.article_text" :marks="marks" />
                </div>

                <template v-else>
                    <!-- article_html is server-rendered from Markdown with raw HTML stripped (RendersMarkdown). -->
                    <div v-if="unfolded" class="post-content" :class="{ 'mt-4': !context.post.title }" dir="auto" v-html="context.post.article_html"></div>
                    <p v-else class="post-body" :class="{ 'mt-4': !context.post.title }" dir="auto">{{ excerpt(context.post.article_text) }}</p>

                    <footer class="post-footer">
                        <button type="button" class="post-more" :aria-expanded="unfolded" @click="unfolded = !unfolded">
                            {{ unfolded ? $t('moderation.context.fold') : $t('moderation.context.unfold') }}
                        </button>
                    </footer>
                </template>
            </template>
        </article>

        <!-- A case on a comment: the thread down to it, the comment itself, and what was said to it,
             nested as on the post's page. -->
        <CaseThread
            v-if="context.comment"
            :ancestors="context.ancestors"
            :comment="context.comment"
            :replies="context.replies"
            :marks="marks"
            :judged-info="judgedInfo"
        />
    </div>
</template>
