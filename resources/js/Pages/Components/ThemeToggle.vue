<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { patchJson } from '@/utils/http';

// The theme itself is picked and applied by the inline script in the page <head>
// (resources/views/partials/theme-script.blade.php); this button only asks it to flip.
const page = usePage();
const dark = ref(window.PostItTheme.isDark());

const onThemeChange = (event) => (dark.value = event.detail.dark);

onMounted(() => window.addEventListener('themechange', onThemeChange));
onBeforeUnmount(() => window.removeEventListener('themechange', onThemeChange));

// In the 'manual' mode the choice is the user's saved setting; in the others the script keeps a
// temporary override in the browser, and nothing is sent.
const toggle = async () => {
    const theme = window.PostItTheme;
    const nowDark = theme.toggle();

    if (theme.mode() !== 'manual') return;

    try {
        await patchJson('/settings/theme', { dark: nowDark });
        // Keep the shared prop in step: a partial reload keeps old props, and app.js would
        // otherwise re-apply the stale value from them.
        page.props.theme.dark = nowDark;
    } catch (error) {
        console.error('Saving the theme failed:', error);
        // Not saved: switch back so the page doesn't show a choice that is lost on reload.
        theme.toggle();
    }
};
</script>

<template>
    <button
        type="button"
        class="icon-btn"
        :aria-label="$t(dark ? 'nav.theme_to_light' : 'nav.theme_to_dark')"
        :title="$t(dark ? 'nav.theme_to_light' : 'nav.theme_to_dark')"
        @click="toggle"
    >
        <!-- Shows where the button takes you: a sun in the dark theme, a moon in the light one. -->
        <svg v-if="dark" class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <circle cx="10" cy="10" r="3.5" />
            <path d="M10 2v1.5M10 16.5V18M2 10h1.5M16.5 10H18M4.3 4.3l1.1 1.1M14.6 14.6l1.1 1.1M4.3 15.7l1.1-1.1M14.6 5.4l1.1-1.1" stroke-linecap="round" />
        </svg>
        <svg v-else class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <path d="M16.5 12.2A6.5 6.5 0 0 1 7.8 3.5a6.5 6.5 0 1 0 8.7 8.7Z" stroke-linejoin="round" />
        </svg>
    </button>
</template>
