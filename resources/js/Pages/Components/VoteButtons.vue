<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatCount } from '@/utils/format';

const props = defineProps({
    upvotes: Number,
    downvotes: Number,
    // Rounded (2 significant figures) controversy score from the server; grows with both the
    // vote split and the total volume, so a bigger, more contested item reads as a bigger
    // number. Null once there are too few votes on both sides for the number to mean anything,
    // so the badge simply does not appear.
    controversy: { type: Number, default: null },
});

// Guests can read everything, so the parent shows a login prompt when they try to vote.
const emit = defineEmits(['needs-login']);

const page = usePage();
const score = computed(() => props.upvotes - props.downvotes);

const vote = () => {
    if (!page.props.auth.user) {
        emit('needs-login');
        return;
    }
    // Real voting comes with the backend.
};
</script>

<template>
    <span class="vote-group">
        <button type="button" class="post-action" aria-label="Upvote" @click="vote">▲</button>
        <span class="vote-score">{{ score }}</span>
        <button type="button" class="post-action" aria-label="Downvote" @click="vote">▼</button>

        <span
            v-if="controversy !== null"
            class="controversy-badge"
            title="People are split roughly evenly between upvotes and downvotes here — the higher this number, the bigger the disagreement"
        >🔥 {{ formatCount(controversy) }}</span>
    </span>
</template>
