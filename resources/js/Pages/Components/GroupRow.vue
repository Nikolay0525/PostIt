<script setup>
import { excerpt, formatCount } from '@/utils/format';

// A group as one line of a list (search results). Private groups show a lock but are listed:
// they must stay findable so people can ask to join.
defineProps({
    group: { type: Object, required: true },
});
</script>

<template>
    <Link :href="route('groups.show', group.slug)" class="search-row">
        <span class="avatar rounded-xl" aria-hidden="true">{{ group.name.charAt(0).toUpperCase() }}</span>
        <span class="min-w-0 flex-1">
            <span class="block font-medium text-ink profile-text" dir="auto">
                {{ group.name }} <span v-if="group.is_private" :title="$t('groups.show.private')">🔒</span>
            </span>
            <span class="block text-xs text-muted profile-text" dir="auto">
                {{ $tChoice('groups.show.members', group.members_count, { num: formatCount(group.members_count) }) }}
                · {{ excerpt(group.description, 90) }}
            </span>
        </span>
    </Link>
</template>
