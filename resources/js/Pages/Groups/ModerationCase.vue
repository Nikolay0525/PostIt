<script setup>
import BackButton from '@/Pages/Components/BackButton.vue';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';
import { fullDate, timeAgo } from '@/utils/format';

// One case of the moderation queue, read in full. Neither who wrote the content nor who reported
// it is ever sent.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
    // { id, created_at, reports_count, target, reports }; see ModerationCaseResource.
    moderation_case: Object,
});

const membership = useGroupMembership(props);
</script>

<template>
    <Head :title="` | ${$t('moderation.case.title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.case.title')" />

        <article class="post-card">
            <p class="post-meta-text">
                <span class="font-medium text-ink">{{ moderation_case.target?.type === 'Post' ? $t('moderation.post') : $t('moderation.comment') }}</span>
                <span aria-hidden="true"> · </span>
                <time :datetime="moderation_case.created_at" :title="fullDate(moderation_case.created_at)">{{ timeAgo(moderation_case.created_at) }}</time>
                <span aria-hidden="true"> · </span>
                {{ $tChoice('moderation.reports', moderation_case.reports_count, { num: moderation_case.reports_count }) }}
            </p>

            <p v-if="!moderation_case.target" class="comment-deleted mt-4">{{ $t('moderation.gone') }}</p>

            <template v-else-if="moderation_case.target.type === 'Post'">
                <h2 v-if="moderation_case.target.title" class="post-title" dir="auto">{{ moderation_case.target.title }}</h2>
                <!-- article_html is server-rendered from Markdown with raw HTML stripped (RendersMarkdown). -->
                <div class="post-content" :class="{ 'mt-4': !moderation_case.target.title }" dir="auto" v-html="moderation_case.target.article_html"></div>
            </template>

            <template v-else>
                <p class="mt-2 text-sm text-muted" dir="auto">
                    {{ $t('moderation.comment_on', { title: moderation_case.target.post.title || $t('moderation.untitled_post') }) }}
                </p>
                <p class="post-content whitespace-pre-line" dir="auto">{{ moderation_case.target.text }}</p>
            </template>

            <div class="case-reports">
                <p class="muted-heading">{{ $t('moderation.reported_for') }}</p>
                <p v-for="report in moderation_case.reports" :key="report.id" class="case-report" dir="auto">
                    <span class="case-report-kind">{{ $t(`moderation.kind.${report.kind}`) }}</span>
                    <template v-if="report.rule"> — {{ report.rule.index + 1 }}. {{ report.rule.text }}</template>
                    <template v-if="report.text"> — {{ report.text }}</template>
                </p>
            </div>
        </article>

        <div class="wizard-actions">
            <BackButton :fallback="route('groups.moderation', group.slug)" />
        </div>
    </section>
</template>
