<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { answerTours, currentPageTours, startTours } from '@/utils/tours';

// One prompt for all of the page's tours that are not answered yet, run one after another,
// so the user is never asked twice in a row (e.g. on the home page: navigation, then the feed).
const page = usePage();

// "Later" lives only in this tab: the tours are offered again on the next visit.
const LATER_KEY = 'tours-later';
const readLater = () => {
    try {
        return JSON.parse(sessionStorage.getItem(LATER_KEY) ?? '[]');
    } catch {
        return [];
    }
};
const later = ref(readLater());

const running = ref(false);

// Navigation comes first wherever the user lands, since the navbar is on every page.
const offered = computed(() => {
    const tours = page.props.tours;

    // Guests get `tours: null` and never see a tour.
    if (tours === null || running.value) return [];

    return [...new Set(['navigation', ...currentPageTours(page.component)])]
        .filter((name) => !(name in tours) && !later.value.includes(name));
});

function yes() {
    const names = offered.value;
    running.value = true;
    startTours(names, () => {
        answerTours(page, names, 'completed');
        running.value = false;
    });
}

function no() {
    answerTours(page, offered.value, 'declined');
}

function postpone() {
    later.value = [...later.value, ...offered.value];
    try {
        sessionStorage.setItem(LATER_KEY, JSON.stringify(later.value));
    } catch {
        // Storage may be blocked; then "later" just lasts until the page is reloaded.
    }
}
</script>

<template>
    <div v-if="offered.length" class="tour-prompt" role="dialog" :aria-label="$t('tours.prompt.title')">
        <p class="text-base font-semibold text-ink">{{ $t('tours.prompt.title') }}</p>
        <p class="mt-1 text-sm text-muted">{{ $t('tours.prompt.text') }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" class="btn-primary" @click="yes">{{ $t('tours.prompt.yes') }}</button>
            <button type="button" class="btn-secondary" @click="postpone">{{ $t('tours.prompt.later') }}</button>
            <button type="button" class="btn-secondary" @click="no">{{ $t('tours.prompt.no') }}</button>
        </div>
    </div>
</template>
