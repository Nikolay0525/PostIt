<script setup>
import { computed, ref } from 'vue';
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';
import { formatCount } from '@/utils/format';

const props = defineProps({
    group: Object,
    is_member: Boolean,
    sort: String,
    // Paginated by <InfiniteScroll>. null when the group is private and the viewer is not a member.
    posts: Object,
});

const page = usePage();

const sorts = [
    { key: 'newest', label: 'Newest' },
    { key: 'top', label: 'Top' },
];

// Sorting happens on the server: reload only the posts and start their list over from page 1.
const changeSort = (key) => {
    if (key === props.sort) return;

    router.get(`/groups/${props.group.id}`, { sort: key }, {
        only: ['posts', 'sort'],
        reset: ['posts'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Subscribing (or requesting to join a private group) is a local toggle for now;
// guests are asked to log in. The real subscribe action comes with the write endpoints.
const joined = ref(props.is_member);
const showLoginPrompt = ref(false);

const buttonLabel = computed(() => {
    if (props.group.is_private) return joined.value ? 'Request sent' : 'Request to join';
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
    <Head :title="` | ${group.name}`" />

    <section class="feed">
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
        <p v-if="!posts" class="post-login-prompt">
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
                    @click="changeSort(s.key)"
                >{{ s.label }}</button>
            </div>

            <p v-if="!posts.data.length" class="text-sm text-muted">No posts in this group yet.</p>

            <InfiniteScroll data="posts">
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

                <template #loading>
                    <p class="text-sm text-muted">Loading more posts…</p>
                </template>
            </InfiniteScroll>
        </template>
    </section>
</template>
