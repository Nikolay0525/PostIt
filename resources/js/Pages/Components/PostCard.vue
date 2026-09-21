<script setup>
import { computed, ref } from 'vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { findGroup } from '@/data/dummyGroups';
import { excerpt, timeAgo } from '@/utils/format';

const props = defineProps({
    post: Object,
    // On the post page the full article is shown and the "See full post" link is hidden.
    full: { type: Boolean, default: false },
});

const initial = computed(() => props.post.author.name.charAt(0).toUpperCase());
// Posts from the backend carry their group. The dummy fallback stays until the feed and group page use the backend too.
const group = computed(() => props.post.group ?? findGroup(props.post.group_id));
const preview = computed(() => excerpt(props.post.article));

const showLoginPrompt = ref(false);
</script>

<template>
    <article class="post-card">
        <header class="post-meta">
            <span class="avatar" aria-hidden="true">{{ initial }}</span>

            <!-- dir="auto" lets each piece of user text align by its own language -->
            <p class="post-meta-text">
                <Link :href="route('groups.show', post.group_id)" class="post-group" dir="auto">{{ group?.name }}</Link>
                <span aria-hidden="true"> · </span>
                <time :datetime="post.created_at">{{ timeAgo(post.created_at) }}</time>
                <br />
                <a href="#" class="post-author" dir="auto">{{ post.author.name }}</a>
            </p>
        </header>

        <h2 v-if="post.title" class="post-title" dir="auto">
            <span v-if="full">{{ post.title }}</span>
            <Link v-else :href="route('posts.show', post.id)" class="post-link">{{ post.title }}</Link>
        </h2>

        <p v-if="full" class="post-content" :class="{ 'mt-4': !post.title }" dir="auto">{{ post.article }}</p>
        <!-- Without a title the preview itself is the link to the post. -->
        <p v-else class="post-body" :class="{ 'mt-4': !post.title }" dir="auto">
            <Link v-if="!post.title" :href="route('posts.show', post.id)" class="post-link">{{ preview }}</Link>
            <template v-else>{{ preview }}</template>
        </p>

        <footer class="post-footer">
            <VoteButtons :upvotes="post.upvotes" :downvotes="post.downvotes" @needs-login="showLoginPrompt = true" />

            <Link :href="route('posts.show', post.id)" class="post-stat post-action">💬 {{ post.comments_count }}</Link>

            <Link v-if="!full" :href="route('posts.show', post.id)" class="post-more">See full post</Link>
        </footer>

        <p v-if="showLoginPrompt" class="post-login-prompt">
            <Link :href="route('login')" class="auth-link">Log in</Link>
            or
            <Link :href="route('register')" class="auth-link">sign up</Link>
            to vote and comment.
        </p>
    </article>
</template>
