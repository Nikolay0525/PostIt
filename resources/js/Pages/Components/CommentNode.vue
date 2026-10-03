<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { timeAgo } from '@/utils/format';
import { postJson } from '@/utils/http';

// Comments are nested (parent_id), so a node renders itself for each of its replies.
const props = defineProps({
    comment: Object,
});

const page = usePage();

const showLoginPrompt = ref(false);

const showReplyForm = ref(false);
const replyText = ref('');
const replying = ref(false);
const replyError = ref(null);

const toggleReplyForm = () => {
    if (!page.props.auth.user) {
        showLoginPrompt.value = true;
        return;
    }

    showReplyForm.value = !showReplyForm.value;
};

const cancelReply = () => {
    showReplyForm.value = false;
    replyText.value = '';
    replyError.value = null;
};

// Same reasoning as the top-level comment form on Posts/Show.vue: posted outside Inertia so a
// full visit doesn't reset the already-scrolled comment list, and the reply is appended locally.
const submitReply = async () => {
    const text = replyText.value.trim();

    if (!text || replying.value) {
        return;
    }

    replying.value = true;
    replyError.value = null;

    try {
        const reply = await postJson('/comments', {
            post_id: props.comment.post_id,
            parent_id: props.comment.id,
            text,
        });

        props.comment.replies.push(reply);
        replyText.value = '';
        showReplyForm.value = false;
    } catch (error) {
        console.error('Reply failed:', error);
        replyError.value = error.message || trans('common.something_went_wrong');
    } finally {
        replying.value = false;
    }
};
</script>

<template>
    <article class="comment-node">
        <div class="comment-card">
            <p v-if="comment.is_deleted" class="comment-deleted">{{ $t('comments.deleted') }}</p>

            <template v-else>
                <p class="post-meta-text flex items-center gap-2">
                    <UserAvatar :url="comment.author.avatar_url" :username="comment.author.username" class="avatar-sm" />
                    <Link :href="route('users.show', comment.author.username)" class="post-author font-medium text-ink" dir="auto">{{ comment.author.username }}</Link>
                    <span aria-hidden="true"> · </span>
                    <time :datetime="comment.created_at">{{ timeAgo(comment.created_at) }}</time>
                </p>
                <p class="post-body text-ink" dir="auto">{{ comment.text }}</p>
                <p class="post-footer">
                    <VoteButtons
                        target-type="comment"
                        :target-id="comment.id"
                        :author-id="comment.author.id"
                        :upvotes="comment.upvotes"
                        :downvotes="comment.downvotes"
                        :controversy="comment.controversy"
                        :viewer-vote="comment.viewer_vote"
                        @needs-login="showLoginPrompt = true"
                    />
                    <button type="button" class="post-action" @click="toggleReplyForm">{{ $t('comments.reply') }}</button>
                </p>
                <p v-if="showLoginPrompt" class="post-login-prompt">
                    <AuthPrompt action="vote" />
                </p>

                <form v-if="showReplyForm" class="comment-form mt-3" @submit.prevent="submitReply">
                    <textarea
                        v-model="replyText"
                        class="field-input"
                        rows="2"
                        maxlength="500"
                        dir="auto"
                        :placeholder="$t('comments.reply_placeholder')"
                        :disabled="replying"
                    ></textarea>
                    <span class="flex gap-2 self-end">
                        <button type="button" class="btn-secondary" @click="cancelReply">{{ $t('common.cancel') }}</button>
                        <button type="submit" class="btn-primary" :disabled="replying || !replyText.trim()">{{ $t('comments.reply') }}</button>
                    </span>
                    <p v-if="replyError" class="vote-error" :title="replyError">⚠ {{ $t('comments.reply_failed') }}</p>
                </form>
            </template>
        </div>

        <!-- Replies stay visible under a deleted comment so the thread still makes sense. -->
        <div v-if="comment.replies.length" class="comment-replies">
            <CommentNode v-for="reply in comment.replies" :key="reply.id" :comment="reply" />
        </div>
    </article>
</template>
