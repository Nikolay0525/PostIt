<script setup>
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';

// What the group's moderators did, for anyone to read. Entries name no moderator and no author.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
});

const membership = useGroupMembership(props);
</script>

<template>
    <Head :title="` | ${$t('moderation.log.title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.log.title')" tight />

        <p class="text-sm text-muted">{{ $t('moderation.log.none') }}</p>
    </section>
</template>
