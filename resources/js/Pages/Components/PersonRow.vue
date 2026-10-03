<script setup>
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import { formatCount } from '@/utils/format';

// A person as one line of a list (search results): picture, name, followers and status.
defineProps({
    person: { type: Object, required: true },
});
</script>

<template>
    <Link :href="route('users.show', person.username)" class="search-row">
        <UserAvatar :url="person.avatar_url" :username="person.username" class="avatar" />
        <span class="min-w-0 flex-1">
            <span class="block font-medium text-ink profile-text" dir="auto">{{ person.username }}</span>
            <span class="block text-xs text-muted profile-text" dir="auto">
                {{ $tChoice('users.show.followers', person.followers_count, { num: formatCount(person.followers_count) }) }}
                <template v-if="person.status_emoji || person.status_text">
                    · {{ person.status_emoji }} {{ person.status_text }}
                </template>
            </span>
        </span>
    </Link>
</template>
