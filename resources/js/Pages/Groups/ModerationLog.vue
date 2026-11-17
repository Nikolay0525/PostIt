<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';

// What the group's moderators decided, for its members, as short cards like the moderation queue's:
// what was decided under which rule, and the start of the content. The whole of an entry — the
// conversation, the marks, the reason, the appeal — is on its own page, which a link can point at.
// Entries name no moderator and no author; the author alone sees which are theirs. No dates: when
// something happened is one more clue to whose it was.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
    // Paginated by <InfiniteScroll>, the newest first; see LogListItemResource.
    entries: Object,
});

const membership = useGroupMembership(props);
</script>

<template>
    <Head :title="` | ${$t('moderation.log.title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.log.title')" tight/>

        <p v-if="!entries.data.length" class="text-sm text-muted">{{ $t('moderation.none_log') }}</p>

        <InfiniteScroll data="entries" class="feed-list">
            <article v-for="entry in entries.data" :key="entry.id" class="post-card" :class="{ 'case-reported': entry.is_own }">
                <p class="post-meta-text">
                    <span v-if="entry.is_own" class="log-entry-own">{{ $t(`moderation.log.yours_${entry.content}`) }}</span>
                    <span v-else class="font-medium text-ink">{{ $t(`moderation.${entry.content}`) }}</span>
                    <span aria-hidden="true"> · </span>
                    <template v-if="entry.rule">{{ $t(`moderation.log.${entry.type}`, { number: entry.rule.index + 1, rule: entry.rule.text }) }}</template>
                    <template v-else>{{ $t(`moderation.verdict.types.${entry.type}`) }}</template>
                </p>

                <h2 v-if="entry.title" class="post-title" dir="auto">{{ entry.title }}</h2>
                <p class="post-body" :class="{ 'mt-4': !entry.title }" dir="auto">{{ entry.excerpt }}</p>

                <footer class="post-footer">
                    <Link :href="route('groups.moderation_log.entry', [group.slug, entry.id])" class="post-more">{{ $t('moderation.case.view') }}</Link>
                </footer>
            </article>

            <template #loading>
                <p class="text-sm text-muted">{{ $t('moderation.loading') }}</p>
            </template>
        </InfiniteScroll>
    </section>
</template>
