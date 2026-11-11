<script setup>
// The strip a page starts with: the same height and line as the home page's tabs, so it sits on
// the line of the side panel's "Groups" and of a .page-aside heading. The default slot (tabs) goes
// after the title, the `actions` slot to the right.
defineProps({
    title: { type: String, required: true },
    // When the tabs take the strip: the title stays for screen readers only.
    titleHidden: Boolean,
    // Without the extra space under the line, for a page whose content brings its own.
    tight: Boolean,
});
</script>

<template>
    <!-- mb-2: with the .feed's gap-2, the page starts as far below the line as the home feed does. -->
    <div class="feed-tabs-row" :class="{ 'mb-2': !tight }">
        <h1 class="page-header-title profile-text" :class="{ 'sr-only': titleHidden }" dir="auto">{{ title }}</h1>
        <slot />
        <div v-if="$slots.actions" class="ms-auto">
            <slot name="actions" />
        </div>
    </div>
</template>
