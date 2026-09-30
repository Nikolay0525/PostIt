<script setup>
import { ref } from 'vue';
import { InfiniteScroll } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import PostCard from '@/Pages/Components/PostCard.vue';
import CommentNode from '@/Pages/Components/CommentNode.vue';
import { postJson } from '@/utils/http';

// `comments` is a paginated prop: <InfiniteScroll> loads the next page as the user scrolls down.
const props = defineProps({
    post: Object,
    comments: Object,
});

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
    <Head :title="` | ${post.title ?? post.author.name}`" />

    <section class="feed">
        <Link :href="route('groups.show', post.group.slug)" class="back-link" dir="auto">
            <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back_to', { name: post.group.name }) }}
        </Link>

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
