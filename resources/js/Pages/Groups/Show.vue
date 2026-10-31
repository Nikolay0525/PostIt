<script setup>
import { computed, ref } from 'vue';
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import Dropdown from '@/Pages/Components/Dropdown.vue';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import TransSlots from '@/Pages/Components/TransSlots.vue';
import PostCard from '@/Pages/Components/PostCard.vue';
import { useGroupMembership } from '@/utils/groupMembership';
import { postJson } from '@/utils/http';
import { panel, takePanel } from '@/utils/sidePanel';

const props = defineProps({
    group: Object,
    is_member: Boolean,
    // Whether the viewer is the group's Owner, who may edit it.
    can_edit: Boolean,
    sort: String,
    // Paginated by <InfiniteScroll>. null when the group is private and the viewer is not a member.
    posts: Object,
});

const page = usePage();

// Labels are lang keys groups.show.sort.<key>.
const sorts = ['newest', 'top', 'controversy'];

// Sorting happens on the server: reload only the posts and start their list over from page 1.
const changeSort = (key) => {
    if (key === props.sort) return;

    router.get(route('groups.show', props.group.slug), { sort: key }, {
        only: ['posts', 'sort'],
        reset: ['posts'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Subscribing, shared with the group card (GroupAside); `joined` also decides who may post.
const membership = useGroupMembership(props);
const { joined, toggleJoin } = membership;

// Posting requires membership in every group, public or private (PostPolicy::create()). For a
// public group `joined` now reflects a real subscribe/unsubscribe call, so it's trustworthy
// immediately, without waiting on a reload; for a private group it's still a fake toggle, so
// only the server-truth `is_member` prop is trusted there.
const canPost = computed(() => {
    if (!page.props.auth.user) return false;

    return props.group.is_private ? props.is_member : joined.value;
});

// "Read all" here, like the ✓ on the side panel. The count is the panel's, so it's known only
// while the group is attached there; unattached, the button shows without a number.
const newPostsCount = computed(() => panel.value?.groups.find((g) => g.id === props.group.id)?.new_posts_count ?? null);
const canReadAll = computed(() => page.props.auth.user && props.is_member && newPostsCount.value !== 0);
const readingAll = ref(false);

const readAll = async () => {
    readingAll.value = true;
    try {
        const data = await postJson(`/groups/${props.group.id}/seen`);
        if (data?.side_panel) takePanel(data.side_panel);
    } catch (error) {
        console.error('Read all failed:', error);
    } finally {
        readingAll.value = false;
    }
};
</script>

<template>
    <Head :title="` | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :membership="membership" />

    <section class="feed">
        <!-- The same strip as on the home page: the group's actions where the home page has its
             tabs, Filters (here only the sort) on the right, and the line under it all. -->
        <div class="feed-tabs-row">
            <nav v-if="posts" class="feed-tabs me-auto" :aria-label="$t('groups.show.actions_label')">
                <Link v-if="canPost" :href="route('posts.create', group.slug)" class="feed-tab feed-tab-action">
                    {{ $t('groups.show.create_post') }}
                </Link>
                <Link
                    :href="route('groups.random_post', group.slug)"
                    class="feed-tab inline-flex items-center gap-1.5"
                    :aria-label="$t('groups.show.lucky')"
                    :title="$t('groups.show.lucky')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3zm2.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0M8 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
                    </svg>
                    <!-- Icon only on a phone, where the row is tight. -->
                    <span class="hidden sm:inline">{{ $t('groups.show.lucky') }}</span>
                </Link>
            </nav>

            <!-- "Read all" for this group, with the side panel's count while it's attached there. -->
            <button
                v-if="posts && canReadAll"
                type="button"
                class="feed-filter-btn"
                :aria-label="$t('groups.show.read_all')"
                :title="$t('groups.show.read_all')"
                :disabled="readingAll"
                @click="readAll"
            >
                ✓
                <span v-if="newPostsCount" class="feed-filter-count">{{ newPostsCount }}</span>
            </button>

            <Dropdown v-if="posts" :label="$t('groups.show.sort_label')" trigger-class="feed-filter-btn" anchored>
                <template #trigger>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z" />
                    </svg>
                    <span class="hidden sm:inline">{{ $t('groups.show.filters') }}</span>
                    <!-- Like the home page's count of active filters: shown once the sort isn't the default. -->
                    <span v-if="sort !== sorts[0]" class="feed-filter-count">1</span>
                </template>

                <div class="feed-filter-panel">
                    <fieldset class="feed-filter-group">
                        <legend class="muted-heading">{{ $t('groups.show.sort_label') }}</legend>
                        <label v-for="key in sorts" :key="key" class="settings-toggle">
                            <input
                                type="radio"
                                name="group-sort"
                                class="checkbox-input mt-0.5 rounded-full"
                                :value="key"
                                :checked="sort === key"
                                @change="changeSort(key)"
                            />
                            <span class="text-sm text-ink">{{ $t(`groups.show.sort.${key}`) }}</span>
                        </label>
                    </fieldset>
                </div>
            </Dropdown>
        </div>

        <!-- Private groups keep their posts hidden from non-members. -->
        <p v-if="!posts" class="post-login-prompt">{{ $t('groups.show.private_notice') }}</p>

        <template v-else>
            <!-- Who can't post yet is told why, under the strip. -->
            <p v-if="!page.props.auth.user" class="text-sm text-muted">
                <AuthPrompt action="post" />
            </p>
            <p v-else-if="!canPost" class="text-sm text-muted">
                <TransSlots :text="$t('groups.show.join_to_post')">
                    <template #join><button type="button" class="auth-link cursor-pointer border-0 bg-transparent p-0" @click="toggleJoin">{{ $t('groups.show.join') }}</button></template>
                </TransSlots>
            </p>

            <p v-if="!posts.data.length" class="text-sm text-muted">{{ $t('groups.show.none') }}</p>

            <InfiniteScroll data="posts" class="feed-list">
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

                <template #loading>
                    <p class="text-sm text-muted">{{ $t('posts.loading') }}</p>
                </template>
            </InfiniteScroll>
        </template>
    </section>
</template>
