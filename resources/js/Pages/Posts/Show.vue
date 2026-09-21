<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';
import PostCard from '@/Pages/Components/PostCard.vue';
import CommentNode from '@/Pages/Components/CommentNode.vue';

// `comments` is a paginated prop: <InfiniteScroll> loads the next page as the user scrolls down.
defineProps({
    post: Object,
    comments: Object,
});
</script>

<template>
    <Head :title="` | ${post.title ?? post.author.name}`" />

    <section class="feed">
        <Link :href="route('groups.show', post.group_id)" class="back-link" dir="auto">
            <span class="back-arrow" aria-hidden="true">←</span> Back to {{ post.group.name }}
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

            <p v-if="!comments.data.length" class="text-sm text-muted">No comments yet.</p>

            <InfiniteScroll data="comments">
                <CommentNode v-for="c in comments.data" :key="c.id" :comment="c" />

                <template #loading>
                    <p class="text-sm text-muted">Loading more comments…</p>
                </template>
            </InfiniteScroll>
        </div>
    </section>
</template>
