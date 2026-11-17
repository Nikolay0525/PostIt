<script setup>
import AppealForm from '@/Pages/Components/AppealForm.vue';
import BackButton from '@/Pages/Components/BackButton.vue';
import CaseContext from '@/Pages/Components/CaseContext.vue';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';

// One entry of the moderation log in full: what was decided under which rule and what it gave, the
// moderator's reason, and the conversation as the moderator saw it, its marked places picked out. Its
// author also finds here the way to appeal: a notification about the verdict leads here, and it is
// where they come back to. Names no moderator and no author, and carries no dates.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
    // See LogEntryResource.
    entry: Object,
});

const membership = useGroupMembership(props);
</script>

<template>
    <Head :title="` | ${$t('moderation.log.entry_title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.log.entry_title')" />

        <div class="feed-list">
            <!-- What was decided and why, above the content it was about. -->
            <div class="post-card flex flex-col gap-1.5 text-sm text-ink">
                <p v-if="entry.is_own" class="log-entry-own">
                    {{ entry.context?.comment ? $t('moderation.log.yours_comment') : $t('moderation.log.yours_post') }}
                </p>
                <p class="font-medium" dir="auto">
                    <template v-if="entry.rule">
                        {{ $t(`moderation.log.${entry.type}`, { number: entry.rule.index + 1, rule: entry.rule.text }) }}
                    </template>
                    <template v-else>{{ $t(`moderation.verdict.types.${entry.type}`) }}</template>
                </p>
                <!-- What a warning gave: fixed by the rule it named. -->
                <p v-if="entry.type === 'warning' && entry.rule">{{ $t(`moderation.log.strike.${entry.rule.kind}`) }}</p>
                <p v-if="entry.reason" dir="auto">
                    <span class="font-medium">{{ $t('moderation.author.reason') }}:</span> {{ entry.reason }}
                </p>

                <!-- Its author alone: the way to appeal it. -->
                <AppealForm v-if="entry.author_view" :verdict="entry.author_view" />
            </div>

            <p v-if="entry.context && entry.author_username" class="text-sm text-muted">{{ $t('moderation.log.own_name') }}</p>
            <CaseContext v-if="entry.context" :context="entry.context" :marks="entry.marks" :author-name="entry.author_username" />
            <p v-else class="comment-deleted">{{ $t('moderation.log.no_context') }}</p>

            <div class="wizard-actions">
                <BackButton :fallback="route('groups.moderation_log', group.slug)" />
            </div>
        </div>
    </section>
</template>
