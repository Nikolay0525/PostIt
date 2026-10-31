<script setup>
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    // { rules, rule_text, rule_example }: the most rules, and the longest text and example.
    limits: { type: Object, required: true },
    // The form's errors: `rules` for the list, `rules.<n>.text` / `rules.<n>.example` for a row.
    errors: { type: Object, default: () => ({}) },
});

// [{ text, example }]. The rows are edited in place, so the parent's form sees every keystroke.
const rules = defineModel({ type: Array, required: true });

const list = ref(null);
const canAdd = computed(() => rules.value.length < props.limits.rules);

const add = () => {
    if (!canAdd.value) return;
    rules.value.push({ text: '', example: '' });
    nextTick(() => {
        const inputs = list.value?.querySelectorAll('.rule-text');
        inputs?.[inputs.length - 1]?.focus();
    });
};

const remove = (index) => {
    rules.value.splice(index, 1);
};
</script>

<template>
    <p class="field-hint mt-0">{{ $t('groups.rules_editor.hint') }}</p>

    <ol ref="list" class="rule-editor-list">
        <li v-for="(rule, index) in rules" :key="index" class="rule-editor">
            <div class="rule-editor-head">
                <span class="rule-editor-number">{{ index + 1 }}.</span>
                <button
                    type="button"
                    class="rule-editor-remove"
                    :aria-label="$t('groups.rules_editor.remove_n', { n: index + 1 })"
                    :title="$t('groups.rules_editor.remove')"
                    @click="remove(index)"
                >✕</button>
            </div>

            <input
                v-model="rule.text"
                class="field-input rule-text"
                :class="{ 'has-error': errors[`rules.${index}.text`] }"
                :maxlength="limits.rule_text"
                dir="auto"
                :aria-label="$t('groups.rules_editor.rule_n', { n: index + 1 })"
                :placeholder="$t('groups.rules_editor.rule_placeholder')"
            />
            <p v-if="errors[`rules.${index}.text`]" class="field-error">{{ errors[`rules.${index}.text`] }}</p>

            <input
                v-model="rule.example"
                class="field-input mt-2"
                :class="{ 'has-error': errors[`rules.${index}.example`] }"
                :maxlength="limits.rule_example"
                dir="auto"
                :aria-label="$t('groups.rules_editor.example_n', { n: index + 1 })"
                :placeholder="$t('groups.rules_editor.example_placeholder')"
            />
            <p v-if="errors[`rules.${index}.example`]" class="field-error">{{ errors[`rules.${index}.example`] }}</p>
        </li>
    </ol>

    <p v-if="errors.rules" class="field-error">{{ errors.rules }}</p>

    <button type="button" class="btn-secondary self-start" :disabled="!canAdd" @click="add">
        {{ $t('groups.rules_editor.add') }}
    </button>
    <p v-if="!canAdd" class="field-hint mt-0">{{ $t('groups.rules_editor.limit', { max: limits.rules }) }}</p>
</template>
