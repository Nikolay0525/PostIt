<script setup>
import { computed } from 'vue';

// Renders an already translated line whose :placeholders are filled by same-named slots, so a
// link or button can sit inside a sentence whatever word order the language uses:
// ":login or :register to vote." / ":login або :register, щоб голосувати."
const props = defineProps({
    text: { type: String, required: true },
});

const parts = computed(() => props.text.split(/(:[a-z_]+)/));
</script>

<template>
    <template v-for="(part, index) in parts" :key="index">
        <slot v-if="part.startsWith(':') && $slots[part.slice(1)]" :name="part.slice(1)" />
        <template v-else>{{ part }}</template>
    </template>
</template>
