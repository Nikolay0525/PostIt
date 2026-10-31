<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import ContentMenu from '@/Pages/Components/ContentMenu.vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { fullDate, timeAgo } from '@/utils/format';
import { postJson, putJson } from '@/utils/http';

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

// Editing happens in place and outside Inertia, like replying: the new text and time are merged into
// the comment already on the page, so the scrolled comment list stays where it is.
const editing = ref(false);
const editText = ref('');
const saving = ref(false);
const editError = ref(null);

const startEdit = () => {
    editText.value = props.comment.text;
    editError.value = null;
    editing.value = true;
};

const cancelEdit = () => {
    editing.value = false;
    editError.value = null;
};

const saveEdit = async () => {
    const text = editText.value.trim();

    if (!text || saving.value) {
        return;
    }

    saving.value = true;
    editError.value = null;

    try {
        const saved = await putJson(`/comments/${props.comment.id}`, { text });

        props.comment.text = saved.text;
        props.comment.edited_at = saved.edited_at;
        editing.value = false;
    } catch (error) {
        console.error('Edit failed:', error);
        editError.value = error.message || trans('common.something_went_wrong');
    } finally {
        saving.value = false;
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
                    <template v-if="comment.edited_at">
                        <span aria-hidden="true"> · </span>
                        <time :datetime="comment.edited_at" :title="fullDate(comment.edited_at)">({{ $t('common.edited', { time: timeAgo(comment.edited_at) }) }})</time>
                    </template>
                </p>

                <form v-if="editing" class="comment-form mt-3" @submit.prevent="saveEdit">
                    <textarea
                        v-model="editText"
                        class="field-input"
                        rows="3"
                        maxlength="500"
                        dir="auto"
                        :aria-label="$t('comments.edit')"
                        :disabled="saving"
                    ></textarea>
                    <span class="flex gap-2 self-end">
                        <button type="button" class="btn-secondary" @click="cancelEdit">{{ $t('common.cancel') }}</button>
                        <button type="submit" class="btn-primary" :disabled="saving || !editText.trim()">{{ $t('comments.save') }}</button>
                    </span>
                    <p v-if="editError" class="vote-error" :title="editError">⚠ {{ $t('comments.edit_failed') }}</p>
                </form>
                <p v-else class="post-body text-ink" dir="auto">{{ comment.text }}</p>
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
                    <!-- Edit lives in the menu, not as a button of its own; "Report" joins it there. -->
                    <ContentMenu v-if="comment.can_edit && !editing">
                        <template #default="{ close }">
                            <button type="button" class="menu-item" @click="close(); startEdit()">{{ $t('comments.edit') }}</button>
                        </template>
                    </ContentMenu>
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
                        :aria-label="$t('comments.reply_placeholder')"
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
