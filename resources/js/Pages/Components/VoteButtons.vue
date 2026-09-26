<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatCount } from '@/utils/format';
import { postJson } from '@/utils/http';

const props = defineProps({
    targetType: { type: String, required: true }, // 'post' | 'comment'
    targetId: { type: String, required: true },
    upvotes: Number,
    downvotes: Number,
    // Rounded (2 significant figures) controversy score from the server; grows with both the
    // vote split and the total volume. Null once there are too few votes on both sides.
    controversy: { type: Number, default: null },
});

// Guests can read everything, so the parent shows a login prompt when they try to vote.
const emit = defineEmits(['needs-login']);

const page = usePage();

// A vote is sent outside Inertia (see utils/http.js), so these are local copies updated
// directly from the vote response, and re-synced whenever a genuine fresh page load hands
// down new props.
const localUpvotes = ref(props.upvotes);
const localDownvotes = ref(props.downvotes);
const localControversy = ref(props.controversy);

watch(
    () => [props.upvotes, props.downvotes, props.controversy],
    ([upvotes, downvotes, controversy]) => {
        localUpvotes.value = upvotes;
        localDownvotes.value = downvotes;
        localControversy.value = controversy;
    },
);

const score = computed(() => localUpvotes.value - localDownvotes.value);
const voting = ref(false);

const vote = async (positive) => {
    if (!page.props.auth.user) {
        emit('needs-login');
        return;
    }

    if (voting.value) {
        return;
    }

    voting.value = true;

    try {
        const result = await postJson('/votes', {
            target_type: props.targetType,
            target_id: props.targetId,
            positive,
        });

        localUpvotes.value = result.upvotes;
        localDownvotes.value = result.downvotes;
        localControversy.value = result.controversy;
    } catch (error) {
        // A failed vote isn't worth interrupting the reader over; the buttons just stay as they were.
        console.error('Vote failed', error);
    } finally {
        voting.value = false;
    }
};
</script>

<template>
    <span class="vote-group">
        <button type="button" class="post-action" aria-label="Upvote" :disabled="voting" @click="vote(true)">▲</button>
        <span class="vote-score">{{ score }}</span>
        <button type="button" class="post-action" aria-label="Downvote" :disabled="voting" @click="vote(false)">▼</button>

        <span
            v-if="localControversy !== null"
            class="controversy-badge"
            title="People are split roughly evenly between upvotes and downvotes here — the higher this number, the bigger the disagreement"
        >🔥 {{ formatCount(localControversy) }}</span>
    </span>
</template>
