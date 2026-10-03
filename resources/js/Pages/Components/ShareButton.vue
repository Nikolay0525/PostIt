<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { postJson } from '@/utils/http';

const props = defineProps({
    postId: { type: String, required: true },
    // The full address to copy (Ziggy's route() is absolute).
    url: { type: String, required: true },
    count: { type: Number, default: 0 },
});

const page = usePage();
const shares = ref(props.count);
const copied = ref(false);
let resetTimer = null;

// Copying works for everyone, guests included. Only a member's share is counted, once per
// member (the server ignores repeats), so the number can't be pumped up by clicking.
const share = async () => {
    try {
        await navigator.clipboard.writeText(props.url);
    } catch {
        // No clipboard access (an insecure origin, or the browser refused): let the person copy it.
        window.prompt('', props.url);
    }

    copied.value = true;
    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => (copied.value = false), 2000);

    if (!page.props.auth.user) return;

    try {
        shares.value = (await postJson(`/posts/${props.postId}/share`)).shares_count;
    } catch (error) {
        // The link is already copied; a missed count isn't worth bothering the reader about.
        console.error('Share count failed:', error);
    }
};

onBeforeUnmount(() => clearTimeout(resetTimer));
</script>

<template>
    <button
        type="button"
        class="post-stat post-action share-btn"
        :title="$t('posts.copy_link')"
        :aria-label="$t('posts.copy_link')"
        @click="share"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M4.715 6.542 3.343 7.914a3 3 0 1 0 4.243 4.243l1.828-1.829A3 3 0 0 0 8.586 5.5L8 6.086a1 1 0 0 0-.154.199 2 2 0 0 1 .861 3.337L6.88 11.45a2 2 0 1 1-2.83-2.83l.793-.792a4 4 0 0 1-.128-1.287z" />
            <path d="M6.586 4.672A3 3 0 0 0 7.414 9.5l.775-.776a2 2 0 0 1-.896-3.346L9.12 3.55a2 2 0 1 1 2.83 2.83l-.793.792c.112.42.155.855.128 1.287l1.372-1.372a3 3 0 1 0-4.243-4.243z" />
        </svg>
        <span v-if="copied" class="share-copied" role="status">{{ $t('posts.copied') }}</span>
        <template v-else>{{ shares }}</template>
    </button>
</template>
