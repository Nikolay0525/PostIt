<script setup>
import { computed } from 'vue';
import PostCard from '@/Pages/Components/PostCard.vue';
import CommentNode from '@/Pages/Components/CommentNode.vue';
import { findPost } from '@/data/dummyPosts';
import { findGroup } from '@/data/dummyGroups';
import { dummyComments, buildCommentTree } from '@/data/dummyComments';

const props = defineProps({
    id: String,
});

const post = computed(() => findPost(props.id));
const group = computed(() => post.value && findGroup(post.value.group_id));
const comments = buildCommentTree(dummyComments);
</script>

<template>
    <Head :title="post ? ` | ${post.title ?? post.author.name}` : ' | Post not found'" />

    <section v-if="post" class="feed">
        <Link :href="route('groups.show', post.group_id)" class="back-link" dir="auto">
            <span class="back-arrow" aria-hidden="true">←</span> Back to {{ group?.name }}
        </Link>

        <PostCard :post="post" full />

        <div class="comments">
            <h2 class="feed-title">Comments</h2>

            <!-- Guests can read comments but not write them. -->
            <form v-if="$page.props.auth.user" class="comment-form" @submit.prevent>
                <textarea class="field-input" rows="3" maxlength="500" dir="auto" placeholder="Write a comment"></textarea>
                <button type="submit" class="btn-primary self-end">Comment</button>
            </form>
            <p v-else class="post-login-prompt">
                <Link :href="route('login')" class="auth-link">Log in</Link>
                or
                <Link :href="route('register')" class="auth-link">sign up</Link>
                to join the discussion.
            </p>

            <CommentNode v-for="c in comments" :key="c.id" :comment="c" />
        </div>
    </section>

    <section v-else class="feed">
        <h1 class="feed-title">Post not found</h1>
        <p class="text-sm text-muted">This post doesn't exist or was removed.</p>
        <Link :href="route('home')" class="auth-link">Back to home</Link>
    </section>
</template>
