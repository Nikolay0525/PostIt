<script setup>
import { computed, ref, watch } from 'vue';

// A person's round picture, or the first letter of their name when they have none (or the
// picture fails to load). Size and colours come from the class the caller passes (.avatar,
// .avatar-sm, .avatar-btn…).
const props = defineProps({
    url: { type: String, default: null },
    username: { type: String, required: true },
});

const failed = ref(false);
watch(() => props.url, () => (failed.value = false));

const initial = computed(() => props.username.charAt(0).toUpperCase());
</script>

<template>
    <span class="overflow-hidden" aria-hidden="true">
        <img v-if="url && !failed" :src="url" alt="" class="h-full w-full object-cover" @error="failed = true" />
        <template v-else>{{ initial }}</template>
    </span>
</template>
