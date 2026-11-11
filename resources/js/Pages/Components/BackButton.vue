<script setup>
import { router } from '@inertiajs/vue3';
import { hasPreviousPage } from '@/utils/navigation';

// "Back" goes to the page the user came from, whichever it was: a page with several ways in (the
// edit form of a post is opened from the feed, the group and the post itself) can't know. Opened
// by its address there is no such page, so `fallback` is used.
const props = defineProps({
    fallback: { type: String, required: true },
});

const goBack = (event) => {
    // Opening in a new tab or window is left to the browser, and the address is a real link for it.
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) return;

    event.preventDefault();

    if (hasPreviousPage()) {
        window.history.back();
    } else {
        router.visit(props.fallback);
    }
};
</script>

<template>
    <a :href="fallback" class="btn-secondary" @click="goBack">
        <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back') }}
    </a>
</template>
