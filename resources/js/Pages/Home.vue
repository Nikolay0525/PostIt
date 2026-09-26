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
        <h1 class="hero-title">Find the conversations that matter to you</h1>
        <p class="hero-subtitle">
            Browse open posts from groups and people around the world. Joining is free.
        </p>

        <div class="hero-actions">
            <Link :href="route('register')" class="btn-primary">Sign up</Link>
            <Link :href="route('login')" class="btn-secondary">Log in</Link>
        </div>
    </section>

    <PostFeed
        :title="feed === 'subscriptions' ? 'Your feed' : 'Trending posts'"
        :hint="$page.props.auth.user && feed === 'trending' ? 'You have not joined any groups yet, so here is what is trending.' : ''"
        :posts="posts"
    />
</template>
