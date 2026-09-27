<script setup>
import { computed, ref } from 'vue';
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';
import { formatCount } from '@/utils/format';
import { deleteJson, postJson } from '@/utils/http';

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
    { key: 'controversy', label: 'Controversy' },
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

// Requesting to join a private group is still a local toggle — GroupPolicy::subscribe()
// deliberately rejects private groups, since membership there only comes from an approved
// GroupJoinRequest, which doesn't exist yet. Subscribing to a public group is real.
const joined = ref(props.is_member);
const showLoginPrompt = ref(false);
const subscribing = ref(false);
const subscribeError = ref(null);

// Posting requires membership in every group, public or private (PostPolicy::create()). For a
// public group `joined` now reflects a real subscribe/unsubscribe call, so it's trustworthy
// immediately, without waiting on a reload; for a private group it's still a fake toggle, so
// only the server-truth `is_member` prop is trusted there.
const canPost = computed(() => {
    if (!page.props.auth.user) return false;

    return props.group.is_private ? props.is_member : joined.value;
});

const buttonLabel = computed(() => {
    if (props.group.is_private) return joined.value ? 'Request sent' : 'Request to join';
    return joined.value ? 'Subscribed' : 'Subscribe';
});

const toggleJoin = async () => {
    if (!page.props.auth.user) {
        showLoginPrompt.value = true;
        return;
    }

    if (props.group.is_private) {
        joined.value = !joined.value;
        return;
    }

    if (subscribing.value) return;

    subscribing.value = true;
    subscribeError.value = null;

    try {
        if (joined.value) {
            await deleteJson(`/groups/${props.group.id}/subscribe`);
        } else {
            await postJson(`/groups/${props.group.id}/subscribe`);
        }
        joined.value = !joined.value;
    } catch (error) {
        console.error('Subscribe failed:', error);
        subscribeError.value = error.message || 'Something went wrong.';
    } finally {
        subscribing.value = false;
    }
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
                :disabled="subscribing"
                @click="toggleJoin"
            >{{ buttonLabel }}</button>

            <p class="group-desc" dir="auto">{{ group.description }}</p>
            <!-- TODO: rules will become an array instead of one string; render as a list then. -->
            <p class="group-rules" dir="auto"><span class="font-medium text-ink">Rules:</span> {{ group.rules }}</p>

            <p v-if="subscribeError" class="vote-error w-full" :title="subscribeError">⚠ {{ subscribeError }}</p>

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
            <div class="feed-toolbar">
                <div class="flex flex-wrap items-center gap-3">
                    <Link v-if="canPost" :href="route('posts.create', group.id)" class="btn-primary">+ Create post</Link>
                    <p v-else-if="!page.props.auth.user" class="text-sm text-muted">
                        <Link :href="route('login')" class="auth-link">Log in</Link>
                        or
                        <Link :href="route('register')" class="auth-link">sign up</Link>
                        to post.
                    </p>
                    <p v-else class="text-sm text-muted">
                        <button type="button" class="auth-link cursor-pointer border-0 bg-transparent p-0" @click="toggleJoin">Join</button> to post.
                    </p>

                    <Link :href="route('groups.random_post', group.id)" class="btn-secondary">🎲 I'm feeling lucky</Link>
                </div>

                <select class="sort-select" :value="sort" aria-label="Sort posts by" @change="changeSort($event.target.value)">
                    <option v-for="s in sorts" :key="s.key" :value="s.key">{{ s.label }}</option>
                </select>
            </div>

            <p v-if="!posts.data.length" class="text-sm text-muted">No posts in this group yet.</p>

            <InfiniteScroll data="posts" class="feed-list">
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

                <template #loading>
                    <p class="text-sm text-muted">Loading more posts…</p>
                </template>
            </InfiniteScroll>
        </template>
    </section>
</template>
