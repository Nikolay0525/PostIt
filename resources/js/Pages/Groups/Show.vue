<script setup>
import { computed, ref } from 'vue';
import { InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import TransSlots from '@/Pages/Components/TransSlots.vue';
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
    if (props.group.is_private) return trans(joined.value ? 'groups.show.request_sent' : 'groups.show.request');
    return trans(joined.value ? 'groups.show.subscribed' : 'groups.show.subscribe');
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
        subscribeError.value = error.message || trans('common.something_went_wrong');
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
                    {{ $tChoice('groups.show.members', group.members_count, { num: formatCount(group.members_count) }) }}<span v-if="group.is_private"> · {{ $t('groups.show.private') }}</span>
                </p>
            </div>

            <button
                type="button"
                :class="joined ? 'btn-secondary' : 'btn-primary'"
                :disabled="subscribing"
                @click="toggleJoin"
            >{{ buttonLabel }}</button>

            <p class="group-desc" dir="auto">{{ group.description }}</p>
            <div v-if="group.rules?.length" class="group-rules">
                <p class="font-medium text-ink">{{ $t('groups.show.rules') }}</p>
                <ol class="group-rules-list">
                    <li v-for="(rule, index) in group.rules" :key="index" dir="auto">
                        {{ rule.text }}
                        <span v-if="rule.example" class="group-rule-example">{{ $t('groups.show.rule_example', { example: rule.example }) }}</span>
                    </li>
                </ol>
            </div>

            <p v-if="subscribeError" class="vote-error w-full" :title="subscribeError">⚠ {{ subscribeError }}</p>

            <p v-if="showLoginPrompt" class="post-login-prompt w-full">
                <AuthPrompt :action="group.is_private ? 'request_join' : 'subscribe'" />
            </p>
        </header>

        <!-- Private groups keep their posts hidden from non-members. -->
        <p v-if="!posts" class="post-login-prompt">{{ $t('groups.show.private_notice') }}</p>

        <template v-else>
            <div class="feed-toolbar">
                <div class="flex flex-wrap items-center gap-3">
                    <Link v-if="canPost" :href="route('posts.create', group.slug)" class="btn-primary">{{ $t('groups.show.create_post') }}</Link>
                    <p v-else-if="!page.props.auth.user" class="text-sm text-muted">
                        <AuthPrompt action="post" />
                    </p>
                    <p v-else class="text-sm text-muted">
                        <TransSlots :text="$t('groups.show.join_to_post')">
                            <template #join><button type="button" class="auth-link cursor-pointer border-0 bg-transparent p-0" @click="toggleJoin">{{ $t('groups.show.join') }}</button></template>
                        </TransSlots>
                    </p>

                    <Link
                        :href="route('groups.random_post', group.slug)"
                        class="btn-secondary"
                    ><svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="16"
                        height="16"
                        fill="currentColor"
                        class="bi bi-dice-3-fill"
                        viewBox="0 0 16 16"
                    >
                        <path
                            d="M3 0a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V3a3 3 0 0 0-3-3zm2.5 4a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m8 8a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0M8 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3"
                        />
                    </svg> {{ $t('groups.show.lucky') }}</Link>
                </div>

                <select class="sort-select" :value="sort" :aria-label="$t('groups.show.sort_label')" @change="changeSort($event.target.value)">
                    <option v-for="key in sorts" :key="key" :value="key">{{ $t(`groups.show.sort.${key}`) }}</option>
                </select>
            </div>

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
