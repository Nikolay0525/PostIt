<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';
import { fullDate, timeAgo } from '@/utils/format';

// The group's moderation queue, for its Owner and moderators: the reported content in full, and
// what it was reported for. Neither who wrote it nor who reported it is ever sent.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
    // Paginated by <InfiniteScroll>, the longest waiting first.
    cases: Object,
});

const membership = useGroupMembership(props);
</script>

<template>
    <Head :title="` | ${$t('moderation.title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.title')" tight />

        <p v-if="!cases.data.length" class="text-sm text-muted">{{ $t('moderation.none') }}</p>

        <InfiniteScroll data="cases" class="feed-list">
            <article v-for="item in cases.data" :key="item.id" class="post-card">
                <p class="post-meta-text">
                    <span class="font-medium text-ink">{{ item.target?.type === 'Post' ? $t('moderation.post') : $t('moderation.comment') }}</span>
                    <span aria-hidden="true"> · </span>
                    <time :datetime="item.created_at" :title="fullDate(item.created_at)">{{ timeAgo(item.created_at) }}</time>
                    <span aria-hidden="true"> · </span>
                    {{ $tChoice('moderation.reports', item.reports_count, { num: item.reports_count }) }}
                </p>

                <p v-if="!item.target" class="comment-deleted mt-4">{{ $t('moderation.gone') }}</p>

                <template v-else-if="item.target.type === 'Post'">
                    <h2 v-if="item.target.title" class="post-title" dir="auto">{{ item.target.title }}</h2>
                    <!-- article_html is server-rendered from Markdown with raw HTML stripped (RendersMarkdown). -->
                    <div class="post-content" :class="{ 'mt-4': !item.target.title }" dir="auto" v-html="item.target.article_html"></div>
                </template>

                <template v-else>
                    <p class="mt-2 text-sm text-muted" dir="auto">
                        {{ $t('moderation.comment_on', { title: item.target.post.title || $t('moderation.untitled_post') }) }}
                    </p>
                    <p class="post-content whitespace-pre-line" dir="auto">{{ item.target.text }}</p>
                </template>

                <div class="case-reports">
                    <p class="muted-heading">{{ $t('moderation.reported_for') }}</p>
                    <p v-for="report in item.reports" :key="report.id" class="case-report" dir="auto">
                        <span class="case-report-kind">{{ $t(`moderation.kind.${report.kind}`) }}</span>
                        <template v-if="report.rule"> — {{ report.rule.index + 1 }}. {{ report.rule.text }}</template>
                        <template v-if="report.text"> — {{ report.text }}</template>
                    </p>
                </div>
            </article>

            <template #loading>
                <p class="text-sm text-muted">{{ $t('moderation.loading') }}</p>
            </template>
        </InfiniteScroll>
    </section>
</template>
