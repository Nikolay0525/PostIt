<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatCount } from '@/utils/format';
import { postJson } from '@/utils/http';

const props = defineProps({
    targetType: { type: String, required: true }, // 'post' | 'comment'
    targetId: { type: String, required: true },
    authorId: { type: String, default: null },
    upvotes: Number,
    downvotes: Number,
    // Rounded (2 significant figures) controversy score from the server; grows with both the
    // vote split and the total volume. Null once there are too few votes on both sides.
    controversy: { type: Number, default: null },
    // true = viewer already upvoted, false = downvoted, null = no vote (or a guest).
    viewerVote: { type: Boolean, default: null },
});

// Guests can read everything, so the parent shows a login prompt when they try to vote.
const emit = defineEmits(['needs-login']);

const page = usePage();

const isOwnContent = computed(() => props.authorId !== null && page.props.auth.user?.id === props.authorId);

// A vote is sent outside Inertia (see utils/http.js), so these are local copies updated
// directly from the vote response, and re-synced whenever a genuine fresh page load hands
// down new props.
const localUpvotes = ref(props.upvotes);
const localDownvotes = ref(props.downvotes);
const localControversy = ref(props.controversy);
const localViewerVote = ref(props.viewerVote);

watch(
    () => [props.upvotes, props.downvotes, props.controversy, props.viewerVote],
    ([upvotes, downvotes, controversy, viewerVote]) => {
        localUpvotes.value = upvotes;
        localDownvotes.value = downvotes;
        localControversy.value = controversy;
        localViewerVote.value = viewerVote;
    },
);

const score = computed(() => localUpvotes.value - localDownvotes.value);
const voting = ref(false);
// Shown next to the buttons on failure, instead of only logging to a console nobody is
// watching — a silently-swallowed error here once looked, from the outside, like a broken button.
const voteError = ref(null);

const vote = async (positive) => {
    if (!page.props.auth.user) {
        emit('needs-login');
        return;
    }

    if (voting.value || isOwnContent.value) {
        return;
    }

    voting.value = true;
    voteError.value = null;

    try {
        const result = await postJson('/votes', {
            target_type: props.targetType,
            target_id: props.targetId,
            positive,
        });

        localUpvotes.value = result.upvotes;
        localDownvotes.value = result.downvotes;
        localControversy.value = result.controversy;
        localViewerVote.value = result.viewer_vote;
    } catch (error) {
        console.error('Vote failed:', error);
        voteError.value = error.message || 'Something went wrong.';
    } finally {
        voting.value = false;
    }
};
</script>

<template>
    <span class="vote-group">
        <button
            v-if="!isOwnContent"
            type="button"
            class="post-action"
            :class="{ 'vote-active-up': localViewerVote === true }"
            aria-label="Upvote"
            :aria-pressed="localViewerVote === true"
            :disabled="voting"
            @click="vote(true)"
        ><svg
                xmlns="http://www.w3.org/2000/svg"
                width="28"
                height="28"
                fill="currentColor"
                class="bi bi-arrow-up-short"
                viewBox="0 0 16 16"
            >
                <path
                    fill-rule="evenodd"
                    d="M8 12a.5.5 0 0 0 .5-.5V5.707l2.146 2.147a.5.5 0 0 0 .708-.708l-3-3a.5.5 0 0 0-.708 0l-3 3a.5.5 0 1 0 .708.708L7.5 5.707V11.5a.5.5 0 0 0 .5.5"
                />
            </svg></button>
        <span class="vote-score">{{ score }}</span>
        <button
            v-if="!isOwnContent"
            type="button"
            class="post-action"
            :class="{ 'vote-active-down': localViewerVote === false }"
            aria-label="Downvote"
            :aria-pressed="localViewerVote === false"
            :disabled="voting"
            @click="vote(false)"
        ><svg
                xmlns="http://www.w3.org/2000/svg"
                width="28"
                height="28"
                fill="currentColor"
                class="bi bi-arrow-down-short"
                viewBox="0 0 16 16"
            >
                <path
                    fill-rule="evenodd"
                    d="M8 4a.5.5 0 0 1 .5.5v5.793l2.146-2.147a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 1 1 .708-.708L7.5 10.293V4.5A.5.5 0 0 1 8 4"
                />
            </svg></button>

        <span
            v-if="localControversy !== null"
            class="controversy-badge"
            title="People are split roughly evenly between upvotes and downvotes here — the higher this number, the bigger the disagreement"
        ><svg
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                fill="currentColor"
                class="bi bi-fire"
                viewBox="0 0 16 16"
            >
                <path
                    d="M8 16c3.314 0 6-2 6-5.5 0-1.5-.5-4-2.5-6 .25 1.5-1.25 2-1.25 2C11 4 9 .5 6 0c.357 2 .5 4-2 6-1.25 1-2 2.729-2 4.5C2 14 4.686 16 8 16m0-1c-1.657 0-3-1-3-2.75 0-.75.25-2 1.25-3C6.125 10 7 10.5 7 10.5c-.375-1.25.5-3.25 2-3.5-.179 1-.25 2 1 3 .625.5 1 1.364 1 2.25C11 14 9.657 15 8 15"
                />
            </svg> {{ formatCount(localControversy) }}</span>

        <span v-if="voteError" class="vote-error" :title="voteError">⚠ Vote failed</span>
    </span>
</template>
