<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import ContentMenu from '@/Pages/Components/ContentMenu.vue';
import HighlightedText from '@/Pages/Components/HighlightedText.vue';
import ModerationNotice from '@/Pages/Components/ModerationNotice.vue';
import ReportForm from '@/Pages/Components/ReportForm.vue';
import ShareButton from '@/Pages/Components/ShareButton.vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import VoteButtons from '@/Pages/Components/VoteButtons.vue';
import { excerpt, fullDate, timeAgo } from '@/utils/format';
import { trackSeen, untrackSeen } from '@/utils/seen';

const props = defineProps({
    post: Object,
    // On the post page the full article is shown and the "See full post" link is hidden.
    full: { type: Boolean, default: false },
});

const preview = computed(() => excerpt(props.post.article_text));

// Hidden or removed. Its own author alone gets `moderation` and their text with it; for everyone
// else it stays a placeholder.
const own = computed(() => Boolean(props.post.moderation));
const concealed = computed(() => props.post.is_hidden && !own.value);
// A removed post is shown to its author as plain text with the marked places picked out.
const verdict = computed(() => (props.post.moderation?.state === 'removed' ? props.post.moderation.verdict : null));

const showLoginPrompt = ref(false);
const reporting = ref(false);

// In a list, a card seen whole stops its post counting as new (utils/seen.js). Not on the post's
// own page (`full`), where opening it already did; not for a guest, who has nothing to count.
const card = ref(null);
const page = usePage();
onMounted(() => {
    if (!props.full && page.props.auth.user) trackSeen(card.value, props.post.id);
});
onBeforeUnmount(() => untrackSeen(card.value));
</script>

<template>
    <article ref="card" class="post-card">
        <header class="post-meta">
            <UserAvatar v-if="!concealed" :url="post.author.avatar_url" :username="post.author.username" class="avatar" />

            <!-- dir="auto" lets each piece of user text align by its own language -->
            <p class="post-meta-text">
                <Link :href="route('groups.show', post.group.slug)" class="post-group" dir="auto">{{ post.group.name }}</Link>
                <span class="group-lang ms-1" :title="$t('groups.show.language')">{{ post.group.language_code }}</span>
                <span aria-hidden="true"> · </span>
                <time :datetime="post.created_at">{{ timeAgo(post.created_at) }}</time>
                <template v-if="post.edited_at">
                    <span aria-hidden="true"> · </span>
                    <time :datetime="post.edited_at" :title="fullDate(post.edited_at)">({{ $t('common.edited', { time: timeAgo(post.edited_at) }) }})</time>
                </template>
                <template v-if="!concealed">
                    <br />
                    <Link :href="route('users.show', post.author.username)" class="post-author" dir="auto">{{ post.author.username }}</Link>
                </template>
            </p>
        </header>

        <!-- Its own author is told where it stands and, on its page, gets their text back. -->
        <template v-if="own">
            <ModerationNotice :moderation="post.moderation" kind="post" :compact="!full" class="mt-4" />

            <template v-if="full">
                <h2 v-if="post.title" class="post-title" dir="auto">{{ post.title }}</h2>
                <HighlightedText v-if="verdict" :text="post.article_text" :marks="verdict.marks" class="post-content" />
                <!-- article_html is server-rendered from Markdown with raw HTML stripped (RendersMarkdown). -->
                <div v-else class="post-content" :class="{ 'mt-4': !post.title }" dir="auto" v-html="post.article_html"></div>
            </template>
        </template>

        <!-- Under a moderation case the server sends no text and no author to anyone else: only that it is hidden. -->
        <p v-else-if="concealed" class="comment-deleted mt-4">
            <Link v-if="!full" :href="route('posts.show', [post.group.slug, post.slug])" class="post-link">{{ post.is_removed ? $t('posts.removed') : $t('posts.hidden') }}</Link>
            <template v-else>{{ post.is_removed ? $t('posts.removed') : $t('posts.hidden') }}</template>
        </p>

        <template v-else>
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
        </template>

        <!-- Its own author keeps the comments and copies counters of a hidden or removed post, read only: no
             score, no copying the link, no menu. Everyone else sees no footer at all. -->
        <footer v-if="!concealed" class="post-footer">
            <!-- A hidden or removed post is not voted on, so its author sees no score either. -->
            <VoteButtons
                v-if="!own"
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

            <span v-if="own" class="post-stat">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z" />
                    <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z" />
                </svg>
                {{ post.shares_count }}
            </span>
            <ShareButton
                v-else
                :post-id="post.id"
                :url="route('posts.show', [post.group.slug, post.slug])"
                :count="post.shares_count"
            />

            <!-- What can be done with the post: its author edits it, anyone else (a guest has no menu) reports it. -->
            <ContentMenu v-if="page.props.auth.user && !own">
                <template #default="{ close }">
                    <Link v-if="post.can_edit" :href="route('posts.edit', [post.group.slug, post.slug])" class="menu-item" @click="close">{{ $t('posts.edit.link') }}</Link>
                    <button v-else type="button" class="menu-item" @click="close(); reporting = true">{{ $t('reports.menu') }}</button>
                </template>
            </ContentMenu>

            <Link v-if="!full" :href="route('posts.show', [post.group.slug, post.slug])" class="post-more">{{ $t('posts.see_full') }}</Link>
        </footer>

        <p v-if="showLoginPrompt" class="post-login-prompt">
            <AuthPrompt action="vote_and_comment" />
        </p>

        <ReportForm v-if="reporting" :group-id="post.group.id" target-type="post" :target-id="post.id" @close="reporting = false" />
    </article>
</template>
