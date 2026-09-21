<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';

// `posts` is a paginated page prop: <InfiniteScroll> loads the next page as the user scrolls down.
defineProps({
    title: { type: String, default: 'Your feed' },
    hint: { type: String, default: '' },
    posts: Object,
});
</script>

<template>
    <section class="feed">
        <h2 class="feed-title">{{ title }}</h2>
        <p v-if="hint" class="text-sm text-muted">{{ hint }}</p>

        <p v-if="!posts.data.length" class="text-sm text-muted">No posts yet.</p>

        <InfiniteScroll data="posts">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

            <template #loading>
                <p class="text-sm text-muted">Loading more posts…</p>
            </template>
        </InfiniteScroll>
    </section>
</template>
