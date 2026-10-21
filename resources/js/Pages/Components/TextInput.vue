<script setup>
import { ref, computed, useId } from "vue";

const props = defineProps({
  name: { type: String, required: true },
  type: { type: String, default: "text" },
  message: String,
});

const model = defineModel({ type: null, required: true });

// Ties the label to its input, so clicking the label focuses it and screen readers announce it.
const id = useId();
const visible = ref(false);
const isPassword = computed(() => props.type === "password");
const inputType = computed(() =>
  isPassword.value && visible.value ? "text" : props.type
);
</script>

<template>
  <div>
    <label :for="id" class="field-label">{{ name }}</label>
    <div class="relative">
      <input
        :id="id"
        :type="inputType"
        v-model="model"
        class="field-input"
        :class="{ 'has-error': message, 'pe-10': isPassword }"
      />
      <button
        v-if="isPassword"
        type="button"
        @click="visible = !visible"
        :aria-label="visible ? $t('common.hide_password') : $t('common.show_password')"
        :title="visible ? $t('common.hide_password') : $t('common.show_password')"
        :aria-pressed="visible"
        class="absolute inset-y-0 end-0 flex items-center px-3 text-muted hover:text-ink"
      >
        <svg
          v-if="visible"
          class="h-4 w-4"
          viewBox="0 0 16 16"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"
          />
          <path
            d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"
          />
          <path
            d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238l.708.709zm10.296 8.884-12-12 .708-.708 12 12z"
          />
        </svg>
        <svg
          v-else
          class="h-4 w-4"
          viewBox="0 0 16 16"
          fill="currentColor"
          aria-hidden="true"
        >
          <path
            d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"
          />
          <path
            d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"
          />
        </svg>
      </button>
    </div>
    <p v-if="message" class="field-error">{{ message }}</p>
  </div>
</template>
