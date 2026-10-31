<script setup>
import { provide, ref } from 'vue';
import { InfiniteScroll } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import PostCard from '@/Pages/Components/PostCard.vue';
import CommentNode from '@/Pages/Components/CommentNode.vue';
import { useGroupMembership } from '@/utils/groupMembership';
import { postJson } from '@/utils/http';

// `comments` is a paginated prop: <InfiniteScroll> loads the next page as the user scrolls down.
const props = defineProps({
    post: Object,
    // The post's group, for its card beside the post.
    group: Object,
    is_member: Boolean,
    // Whether the viewer is the group's Owner, who may edit it.
    can_edit: Boolean,
    comments: Object,
});

const membership = useGroupMembership(props);

// The comments' report form needs the group, and a comment node only knows its post.
provide('groupId', props.group.id);

const commentText = ref('');
const posting = ref(false);
const commentError = ref(null);

// Posted outside Inertia, like voting: a full visit would only hand back page one of `comments`,
// discarding whatever the reader has already scrolled past via <InfiniteScroll>. The new comment
// is appended to the already-loaded page locally instead of waiting on a round-trip reload.
const submitComment = async () => {
    const text = commentText.value.trim();

    if (!text || posting.value) {
        return;
    }

    posting.value = true;
    commentError.value = null;

    try {
        const comment = await postJson('/comments', {
            post_id: props.post.id,
            text,
        });

        props.comments.data.push(comment);
        commentText.value = '';
    } catch (error) {
        console.error('Comment failed:', error);
        commentError.value = error.message || trans('common.something_went_wrong');
    } finally {
        posting.value = false;
    }
};
</script>

<template>
    <Head :title="` | ${post.title ?? post.author.username}`" />

    <!-- name-tag h2: the page's h1 is the strip's "Post". -->
    <GroupAside :group="group" :can-edit="can_edit" :membership="membership" name-tag="h2" />

    <section class="feed">
        <PageHeader :title="$t('posts.show.title')" />

        <PostCard :post="post" full />

        <div class="comments">
            <h2 class="feed-title">{{ $t('comments.heading') }}</h2>

            <!-- Guests can read comments but not write them. -->
            <form v-if="$page.props.auth.user" class="comment-form" @submit.prevent="submitComment">
                <textarea
                    v-model="commentText"
                    class="field-input"
                    rows="3"
                    maxlength="500"
                    dir="auto"
                    :placeholder="$t('comments.placeholder')"
                    :aria-label="$t('comments.placeholder')"
                    :disabled="posting"
                ></textarea>
                <button type="submit" class="btn-primary self-end" :disabled="posting || !commentText.trim()">{{ $t('comments.submit') }}</button>
                <p v-if="commentError" class="vote-error" :title="commentError">⚠ {{ $t('comments.failed') }}</p>
            </form>
            <p v-else class="post-login-prompt">
                <AuthPrompt action="discuss" />
            </p>

            <p v-if="!comments.data.length" class="text-sm text-muted">{{ $t('comments.none') }}</p>

            <InfiniteScroll data="comments" class="feed-list">
                <CommentNode v-for="c in comments.data" :key="c.id" :comment="c" />

                <template #loading>
                    <p class="text-sm text-muted">{{ $t('comments.loading') }}</p>
                </template>
            </InfiniteScroll>
        </div>
    </section>
</template>
