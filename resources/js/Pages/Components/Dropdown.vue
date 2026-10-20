<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({
    label: String,
    triggerClass: { type: String, default: 'icon-btn' },
});

// `open` lets a menu load its content only when it is actually opened.
const emit = defineEmits(['open']);

const open = ref(false);
const root = ref(null);

const close = () => (open.value = false);
const toggle = () => {
    open.value = !open.value;
    if (open.value) emit('open');
};

// Clicking anywhere outside closes the menu, which also means opening
// another dropdown closes this one.
const onDocumentClick = (e) => {
    if (root.value && !root.value.contains(e.target)) close();
};
const onKeydown = (e) => {
    if (e.key === 'Escape') close();
};

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            :class="triggerClass"
            :aria-label="label"
            aria-haspopup="true"
            :aria-expanded="open"
            @click="toggle"
        >
            <slot name="trigger" />
        </button>

        <div v-if="open" class="menu-panel">
            <slot :close="close" />
        </div>
    </div>
</template>
