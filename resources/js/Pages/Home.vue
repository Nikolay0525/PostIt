<script setup>
import { router } from '@inertiajs/vue3';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import PostFeed from '@/Pages/Components/PostFeed.vue';

const props = defineProps({
    // 'recommended' (the default) or 'following'.
    feed: String,
    // Inside Following: 'groups' or 'people'.
    source: String,
    // Paginated by <InfiniteScroll>. null for a guest on the Following tab.
    posts: Object,
});

const tabs = ['recommended', 'following'];
const sources = ['groups', 'people'];

// Switching happens on the server, like a group's sort: reload only the posts and start their
// list over from page 1. The default tab keeps a clean "/" URL; Following shows up in it, so
// the tab can be bookmarked or shared.
const show = (feed, source = props.source) => {
    if (feed === props.feed && source === props.source) return;

    router.get(route('home'), feed === 'following' ? { feed, source } : {}, {
        only: ['posts', 'feed', 'source'],
        reset: ['posts'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
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
            :hint="$t('home.recommended_hint')"
            :posts="posts"
        />
        <PostFeed
            v-else
            :empty="$t(`home.empty.${source}`)"
            :posts="posts"
        />
    </section>
</template>
