<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    upvotes: Number,
    downvotes: Number,
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
    </span>
</template>
