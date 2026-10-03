<script setup>
import { computed, ref } from 'vue';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import ShareButton from '@/Pages/Components/ShareButton.vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { excerpt, timeAgo } from '@/utils/format';

const props = defineProps({
    post: Object,
    // On the post page the full article is shown and the "See full post" link is hidden.
    full: { type: Boolean, default: false },
});

const preview = computed(() => excerpt(props.post.article_text));

const showLoginPrompt = ref(false);
</script>

<template>
    <article class="post-card">
        <header class="post-meta">
            <UserAvatar :url="post.author.avatar_url" :username="post.author.username" class="avatar" />

            <!-- dir="auto" lets each piece of user text align by its own language -->
            <p class="post-meta-text">
                <Link :href="route('groups.show', post.group.slug)" class="post-group" dir="auto">{{ post.group.name }}</Link>
                <span aria-hidden="true"> · </span>
                <time :datetime="post.created_at">{{ timeAgo(post.created_at) }}</time>
                <br />
                <Link :href="route('users.show', post.author.username)" class="post-author" dir="auto">{{ post.author.username }}</Link>
            </p>
        </header>

        <h2 v-if="post.title" class="post-title" dir="auto">
            <span v-if="full">{{ post.title }}</span>
            <Link v-else :href="route('posts.show', [post.group.slug, post.slug])" class="post-link">{{ post.title }}</Link>
        </h2>

        <!-- article_html is server-rendered from Markdown with raw HTML stripped (RendersMarkdown) —
             safe to render as markup, unlike the raw article source. -->
        <div v-if="full" class="post-content" :class="{ 'mt-4': !post.title }" dir="auto" v-html="post.article_html"></div>
        <!-- Without a title the preview itself is the link to the post. -->
        <p v-else class="post-body" :class="{ 'mt-4': !post.title }" dir="auto">
            <Link v-if="!post.title" :href="route('posts.show', [post.group.slug, post.slug])" class="post-link">{{ preview }}</Link>
            <template v-else>{{ preview }}</template>
        </p>

        <footer class="post-footer">
            <VoteButtons
                target-type="post"
                :target-id="post.id"
                :author-id="post.author.id"
                :upvotes="post.upvotes"
                :downvotes="post.downvotes"
                :controversy="post.controversy"
                :viewer-vote="post.viewer_vote"
                @needs-login="showLoginPrompt = true"
            />

            <Link
                :href="route('posts.show', [post.group.slug, post.slug])"
                class="post-stat post-action"
            ><svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="bi bi-chat-left-dots"
                viewBox="0 0 16 16"
            >
                <path
                    d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"
                />
                <path
                    d="M5 6a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0"
                />
            </svg> {{ post.comments_count }}</Link>

            <ShareButton
                :post-id="post.id"
                :url="route('posts.show', [post.group.slug, post.slug])"
                :count="post.shares_count"
            />

            <Link v-if="!full" :href="route('posts.show', [post.group.slug, post.slug])" class="post-more">{{ $t('posts.see_full') }}</Link>
        </footer>

        <p v-if="showLoginPrompt" class="post-login-prompt">
            <AuthPrompt action="vote_and_comment" />
        </p>
    </article>
</template>
