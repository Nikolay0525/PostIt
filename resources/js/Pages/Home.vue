<script setup>
import PostFeed from '@/Pages/Components/PostFeed.vue';

// feed: 'subscriptions' (posts from the member's groups) or 'trending'.
defineProps({
    feed: String,
    posts: Object,
});
</script>

<template>
    <Head title="" />

    <!-- Guests get a welcome hero; members go straight to the feed. -->
    <section v-if="!$page.props.auth.user" class="hero">
        <p class="hero-brand">Post<span class="brand-mark-accent">It.</span></p>
        <h1 class="hero-title">{{ $t('home.hero_title') }}</h1>
        <p class="hero-subtitle">{{ $t('home.hero_subtitle') }}</p>

        <div class="hero-actions">
            <Link :href="route('register')" class="btn-primary">{{ $t('nav.sign_up') }}</Link>
            <Link :href="route('login')" class="btn-secondary">{{ $t('nav.log_in') }}</Link>
        </div>
    </section>

    <PostFeed
        :title="feed === 'subscriptions' ? $t('home.your_feed') : $t('home.trending')"
        :hint="$page.props.auth.user && feed === 'trending' ? $t('home.trending_hint') : ''"
        :posts="posts"
    />
</template>
