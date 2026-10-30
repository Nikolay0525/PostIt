<script setup>
import { panelOpen } from '@/utils/sidePanel';

// Opens the side panel as a drawer; only below xl, where the panel isn't on screen already.
// The layout puts it in the navbar, next to the logo.
</script>

<template>
    <button
        type="button"
        class="feed-filter-btn xl:hidden"
        aria-controls="side-panel"
        :aria-expanded="panelOpen"
        :aria-label="$t('home.side_panel.open')"
        @click="panelOpen = true"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
        </svg>
    </button>
</template>
