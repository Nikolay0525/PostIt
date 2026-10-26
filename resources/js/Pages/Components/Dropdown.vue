<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';

defineProps({
    label: String,
    triggerClass: { type: String, default: 'icon-btn' },
    // Under its button at every width. Without it, on a phone the panel spans the screen under the
    // navbar — right for the navbar's own menus, wrong for one in the page (a feed's Filters),
    // which would open far from its button or on top of it.
    anchored: { type: Boolean, default: false },
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

        <div v-if="open" :class="anchored ? 'menu-panel-anchored' : 'menu-panel'">
            <slot :close="close" />
        </div>
    </div>
</template>
