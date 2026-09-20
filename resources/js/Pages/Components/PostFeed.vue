<script setup>
import PostCard from '@/Pages/Components/PostCard.vue';
import { dummyPosts } from '@/data/dummyPosts';

defineProps({
    title: { type: String, default: 'Your feed' },
});
</script>

<template>
    <section class="feed">
        <h2 class="feed-title">{{ title }}</h2>

        <PostCard v-for="post in dummyPosts" :key="post.id" :post="post" />
    </section>
</template>
