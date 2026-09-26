<script setup>
import { ref } from 'vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { timeAgo } from '@/utils/format';

// Comments are nested (parent_id), so a node renders itself for each of its replies.
defineProps({
    comment: Object,
});

const showLoginPrompt = ref(false);
</script>

<template>
    <article class="comment-node">
        <div class="comment-card">
            <p v-if="comment.is_deleted" class="comment-deleted">[deleted]</p>

            <template v-else>
                <p class="post-meta-text">
                    <a href="#" class="post-author font-medium text-ink" dir="auto">{{ comment.author.name }}</a>
                    <span aria-hidden="true"> · </span>
                    <time :datetime="comment.created_at">{{ timeAgo(comment.created_at) }}</time>
                </p>
                <p class="post-body text-ink" dir="auto">{{ comment.text }}</p>
                <p class="post-footer">
                    <VoteButtons
                        target-type="comment"
                        :target-id="comment.id"
                        :upvotes="comment.upvotes"
                        :downvotes="comment.downvotes"
                        :controversy="comment.controversy"
                        :viewer-vote="comment.viewer_vote"
                        @needs-login="showLoginPrompt = true"
                    />
                </p>
                <p v-if="showLoginPrompt" class="post-login-prompt">
                    <Link :href="route('login')" class="auth-link">Log in</Link>
                    or
                    <Link :href="route('register')" class="auth-link">sign up</Link>
                    to vote.
                </p>
            </template>
        </div>

        <!-- Replies stay visible under a deleted comment so the thread still makes sense. -->
        <div v-if="comment.replies.length" class="comment-replies">
            <CommentNode v-for="reply in comment.replies" :key="reply.id" :comment="reply" />
        </div>
    </article>
</template>
