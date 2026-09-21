<script setup>
import PostCard from '@/Pages/Components/PostCard.vue';
import { publicPosts } from '@/data/dummyPosts';

defineProps({
    title: { type: String, default: 'Your feed' },
});

const posts = publicPosts();
</script>

<template>
    <section class="feed">
        <h2 class="feed-title">{{ title }}</h2>

        <PostCard v-for="post in posts" :key="post.id" :post="post" />
    </section>
</template>
