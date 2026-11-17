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
    // In place of the "author" label: only the author's own entry in the log has it, their own name.
    authorName: { type: String, default: null },
});

// Where a moderator selects what to mark: the judged body (`target`) and, in a case on a post, its title.
const target = ref(null);
const titleTarget = ref(null);
defineExpose({ target, titleTarget });

// A mark says which part it is in; one without is in the body, as marks were before titles could be marked.
const titleMarks = computed(() => props.marks.filter((mark) => mark.field === 'title'));
const bodyMarks = computed(() => props.marks.filter((mark) => (mark.field ?? 'body') === 'body'));

// The judged comment is drawn deep in CaseThread; it hands its text's element back here.
provide('caseTarget', (el) => (target.value = el));

const onPost = computed(() => props.context.comment === null);
const unfolded = ref(false);

// Who said something: "author", or "participant N" — or, to the author on their own log entry, their name.
const label = ({ kind, number }) => (kind === 'author'
    ? props.authorName ?? trans('moderation.context.author')
    : trans('moderation.context.participant', { number }));
provide('caseLabel', label);
</script>

<template>
    <div class="feed-list">
        <!-- The post: the judged content in a case on a post; otherwise what the conversation is under. -->
        <article v-if="context.post" class="post-card" :class="{ 'case-reported': onPost }">
            <p class="post-meta-text">
                <!-- Coloured only when the post is what is judged, not when its author is the one reported below. -->
                <span class="case-label" :class="{ 'is-judged': onPost }">{{ label(context.post.label) }}</span>
                <!-- No date it was written: one more clue to whose it is. Only since when it is hidden. -->
                <template v-if="onPost && judgedInfo">
                    <span aria-hidden="true"> · </span>
                    <time :datetime="judgedInfo.hidden_at" :title="fullDate(judgedInfo.hidden_at)">{{ $t('moderation.case.hidden', { time: timeAgo(judgedInfo.hidden_at) }) }}</time>
                    <span aria-hidden="true"> · </span>
                    {{ $tChoice('moderation.reports', judgedInfo.reports_count, { num: judgedInfo.reports_count }) }}
                </template>
            </p>

            <p v-if="context.post.is_deleted" class="comment-deleted mt-4">{{ $t('moderation.context.deleted') }}</p>

            <template v-else>
                <h2 v-if="context.post.title" class="post-title" dir="auto">
                    <span v-if="onPost" ref="titleTarget" data-mark-field="title">
                        <HighlightedText tag="span" :text="context.post.title" :marks="titleMarks" />
                    </span>
                    <template v-else>{{ context.post.title }}</template>
                </h2>

                <!-- Judged: plain text, so that what is selected lines up with the text the marks are counted in. -->
                <div v-if="onPost" ref="target" data-mark-field="body" class="post-content" :class="{ 'mt-4': !context.post.title }">
                    <HighlightedText :text="context.post.article_text" :marks="bodyMarks" />
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
             nested as on the post's page, under the same heading. -->
        <h2 v-if="context.comment" class="feed-title">{{ $t('comments.heading') }}</h2>
        <CaseThread
            v-if="context.comment"
            :ancestors="context.ancestors"
            :comment="context.comment"
            :replies="context.replies"
            :marks="bodyMarks"
            :judged-info="judgedInfo"
        />
    </div>
</template>
