<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';

// `posts` is a paginated page prop: <InfiniteScroll> loads the next page as the user scrolls down.
defineProps({
    title: { type: String, required: true },
    hint: { type: String, default: '' },
    posts: Object,
});
</script>

<template>
    <!-- w-full: inside another .feed (the profile page) the auto side margins would otherwise
         shrink this to its content, and an empty feed's title would end up centred. -->
    <section class="feed w-full">
        <h2 class="feed-title" dir="auto">{{ title }}</h2>
        <p v-if="hint" class="text-sm text-muted">{{ hint }}</p>

        <p v-if="!posts.data.length" class="text-sm text-muted">{{ $t('posts.none') }}</p>

        <InfiniteScroll data="posts" class="feed-list">
            <PostCard v-for="post in posts.data" :key="post.id" :post="post" />

            <template #loading>
                <p class="text-sm text-muted">{{ $t('posts.loading') }}</p>
            </template>
        </InfiniteScroll>
    </section>
</template>
