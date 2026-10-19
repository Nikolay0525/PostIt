<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import Dropdown from '@/Pages/Components/Dropdown.vue';
import PostFeed from '@/Pages/Components/PostFeed.vue';

const props = defineProps({
    // 'recommended' (the default) or 'following'.
    feed: String,
    // Inside Following: 'groups' or 'people'.
    source: String,
    // The feed filters, shared by every tab: { new, langs, period }. new/langs are always false for guests.
    filters: Object,
    // Paginated by <InfiniteScroll>. null for a guest on the Following tab.
    posts: Object,
});

const page = usePage();

const tabs = ['recommended', 'following'];
const sources = ['groups', 'people'];
const periods = ['all', 'month', 'week'];

// Only what differs from the defaults goes into the URL, so plain Recommended stays "/". The
// filters are shared by every tab, so they ride along when switching.
const query = (feed, source, filters) => ({
    ...(feed === 'following' ? { feed, source } : {}),
    ...(filters.new ? { new: 1 } : {}),
    ...(filters.langs ? { langs: 1 } : {}),
    ...(filters.period !== 'all' ? { period: filters.period } : {}),
});

// Switching happens on the server, like a group's sort: reload only the posts and start their
// list over from page 1. Tabs, source and filters all live in the URL, so any view can be
// bookmarked or shared.
const load = (feed, source, filters) => {
    router.get(route('home'), query(feed, source, filters), {
        only: ['posts', 'feed', 'source', 'filters'],
        reset: ['posts'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const show = (feed, source = props.source) => {
    if (feed === props.feed && source === props.source) return;

    load(feed, source, props.filters);
};

const setFilter = (key, value) => load(props.feed, props.source, { ...props.filters, [key]: value });

// Shown on the button, so a narrowed list is never mistaken for "that's all there is".
const activeFilters = computed(() =>
    [props.filters.new, props.filters.langs, props.filters.period !== 'all'].filter(Boolean).length);
</script>

<template>
    <Head title="" />

    <!-- Guests get a welcome hero above the feed. -->
    <section v-if="!$page.props.auth.user" class="hero">
        <p class="hero-brand">Post<span class="brand-mark-accent">It.</span></p>
        <h1 class="hero-title">{{ $t('home.hero_title') }}</h1>
        <p class="hero-subtitle">{{ $t('home.hero_subtitle') }}</p>

        <div class="hero-actions">
            <Link :href="route('register')" class="btn-primary">{{ $t('nav.sign_up') }}</Link>
            <Link :href="route('login')" class="btn-secondary">{{ $t('nav.log_in') }}</Link>
        </div>
    </section>

    <section class="feed">
        <div class="feed-tabs-row">
            <div class="feed-tabs" role="tablist" :aria-label="$t('home.tabs_label')">
                <button
                    v-for="tab in tabs"
                    :key="tab"
                    type="button"
                    role="tab"
                    class="feed-tab"
                    :class="{ 'is-active': feed === tab }"
                    :aria-selected="feed === tab"
                    @click="show(tab)"
                >{{ $t(`home.tabs.${tab}`) }}</button>
            </div>

            <!-- On every tab; not for a guest on Following, who sees a log-in prompt there instead. -->
            <Dropdown v-if="posts" :label="$t('home.filters.label')" trigger-class="feed-filter-btn">
                <template #trigger>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5z" />
                    </svg>
                    {{ $t('home.filters.label') }}
                    <span v-if="activeFilters" class="feed-filter-count">{{ activeFilters }}</span>
                </template>

                <div class="feed-filter-panel">
                    <!-- These two need to know who's reading: views, votes, languages. -->
                    <template v-if="page.props.auth.user">
                        <label class="settings-toggle">
                            <input type="checkbox" class="checkbox-input mt-0.5" :checked="filters.new" @change="setFilter('new', $event.target.checked)" />
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $t('home.filters.new.label') }}</span>
                                <span class="block text-xs text-muted">{{ $t('home.filters.new.hint') }}</span>
                            </span>
                        </label>
                        <label class="settings-toggle">
                            <input type="checkbox" class="checkbox-input mt-0.5" :checked="filters.langs" @change="setFilter('langs', $event.target.checked)" />
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $t('home.filters.langs.label') }}</span>
                                <span class="block text-xs text-muted">{{ $t('home.filters.langs.hint') }}</span>
                            </span>
                        </label>
                    </template>
                    <p v-else class="text-xs text-muted">
                        <AuthPrompt action="filters" />
                    </p>

                    <fieldset class="flex flex-col gap-2">
                        <legend class="settings-heading">{{ $t('home.filters.period.label') }}</legend>
                        <label v-for="period in periods" :key="period" class="settings-toggle">
                            <input
                                type="radio"
                                name="feed-period"
                                class="checkbox-input mt-0.5 rounded-full"
                                :value="period"
                                :checked="filters.period === period"
                                @change="setFilter('period', period)"
                            />
                            <span class="text-sm text-ink">{{ $t(`home.filters.period.${period}`) }}</span>
                        </label>
                    </fieldset>
                </div>
            </Dropdown>
        </div>

        <div v-if="feed === 'following'" class="feed-switch" role="group" :aria-label="$t('home.sources_label')">
            <button
                v-for="key in sources"
                :key="key"
                type="button"
                class="feed-switch-option"
                :class="{ 'is-active': source === key }"
                :aria-pressed="source === key"
                @click="show('following', key)"
            >{{ $t(`home.sources.${key}`) }}</button>
        </div>

        <p v-if="!posts" class="post-login-prompt">
            <AuthPrompt action="following" />
        </p>

        <PostFeed
            v-else-if="feed === 'recommended'"
            :hint="$t($page.props.auth.user ? 'home.recommended_hint.member' : 'home.recommended_hint.guest')"
            :empty="$t(activeFilters ? 'home.filters.empty' : 'home.recommended_empty')"
            :posts="posts"
        />
        <PostFeed
            v-else
            :empty="$t(activeFilters ? 'home.filters.empty' : `home.empty.${source}`)"
            :posts="posts"
        />
    </section>
</template>
