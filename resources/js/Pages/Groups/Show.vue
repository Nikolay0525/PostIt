<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';
import { postsForGroup, score } from '@/data/dummyPosts';
import { findGroup } from '@/data/dummyGroups';
import { formatCount } from '@/utils/format';

const props = defineProps({
    id: String,
});

const page = usePage();
const group = computed(() => findGroup(props.id));

const sorts = [
    { key: 'newest', label: 'Newest' },
    { key: 'top', label: 'Top' },
];
const sort = ref('newest');

const posts = computed(() => {
    const list = [...postsForGroup(props.id)];
    return sort.value === 'top'
        ? list.sort((a, b) => score(b) - score(a))
        : list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});

// Subscribing (or requesting to join a private group) is a local toggle for now;
// guests are asked to log in.
const joined = ref(false);
const showLoginPrompt = ref(false);

const buttonLabel = computed(() => {
    if (group.value.is_private) return joined.value ? 'Request sent' : 'Request to join';
    return joined.value ? 'Subscribed' : 'Subscribe';
});

const toggleJoin = () => {
    if (!page.props.auth.user) {
        showLoginPrompt.value = true;
        return;
    }
    joined.value = !joined.value;
};
</script>

<template>
    <Head :title="group ? ` | ${group.name}` : ' | Group not found'" />

    <section v-if="group" class="feed">
        <header class="group-header">
            <span class="group-avatar" aria-hidden="true">{{ group.name.charAt(0).toUpperCase() }}</span>

            <div class="min-w-0 flex-1">
                <h1 class="group-name" dir="auto">{{ group.name }}</h1>
                <p class="group-stats">
                    {{ formatCount(group.members_count) }} members<span v-if="group.is_private"> · Private</span>
                </p>
            </div>

            <button
                type="button"
                :class="joined ? 'btn-secondary' : 'btn-primary'"
                @click="toggleJoin"
            >{{ buttonLabel }}</button>

            <p class="group-desc" dir="auto">{{ group.description }}</p>
            <p class="group-rules" dir="auto"><span class="font-medium text-ink">Rules:</span> {{ group.rules }}</p>

            <p v-if="showLoginPrompt" class="post-login-prompt w-full">
                <Link :href="route('login')" class="auth-link">Log in</Link>
                or
                <Link :href="route('register')" class="auth-link">sign up</Link>
                to {{ group.is_private ? 'request to join' : 'subscribe to' }} this group.
            </p>
        </header>

        <!-- Private groups keep their posts hidden from non-members. -->
        <p v-if="group.is_private" class="post-login-prompt">
            This group is private. Posts are visible to members only.
        </p>

        <template v-else>
            <div class="sort-tabs" role="tablist">
                <button
                    v-for="s in sorts"
                    :key="s.key"
                    type="button"
                    role="tab"
                    class="sort-tab"
                    :class="{ 'sort-tab-active': sort === s.key }"
                    @click="sort = s.key"
                >{{ s.label }}</button>
            </div>

            <PostCard v-for="post in posts" :key="post.id" :post="post" />

            <p v-if="!posts.length" class="text-sm text-muted">No posts in this group yet.</p>
        </template>
    </section>

    <section v-else class="feed">
        <h1 class="feed-title">Group not found</h1>
        <p class="text-sm text-muted">This group doesn't exist or was removed.</p>
        <Link :href="route('home')" class="auth-link">Back to home</Link>
    </section>
</template>
