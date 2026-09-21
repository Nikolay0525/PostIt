<script setup>
defineProps({
    name: { type: String, required: true },
    type: { type: String, default: 'text' },
    message: String,
});

const model = defineModel({ type: null, required: true });
</script>

<template>
    <div>
        <label class="field-label">{{ name }}</label>
        <input
            :type="type"
            v-model="model"
            class="field-input"
            :class="{ 'has-error': message }"
        />
        <p v-if="message" class="field-error">{{ message }}</p>
    </div>
</template>