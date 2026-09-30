<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    languages: Array,
    default_language_code: String,
    limits: Object,
});

const STEP_ONE_FIELDS = ['slug', 'name', 'description', 'language_code', 'is_private'];
// Mirrors GroupService::SLUG_PATTERN so the obvious mistakes are caught before sliding on.
const SLUG_PATTERN = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

const form = useForm({
    slug: '',
    name: '',
    description: '',
    language_code: props.default_language_code ?? '',
    is_private: false,
    rules: [{ text: '', example: '' }],
});

const step = ref(1);
// Both panels stay rendered so switching is a slide, not a reload. While the slide runs both
// must keep their height; once it ends the hidden one collapses, so a long rules list doesn't
// leave empty space under step 1 (and vice versa).
// A timer rather than `transitionend`, which never fires when the user's system disables
// animations (the slide is `transition: none` then). Matches the 300ms CSS duration.
const SLIDE_MS = 320;
const sliding = ref(false);
const stepOnePanel = ref(null);
const stepTwoPanel = ref(null);
let slideTimer = null;

watch(step, (current) => {
    sliding.value = true;
    clearTimeout(slideTimer);
    slideTimer = setTimeout(() => {
        sliding.value = false;
        // Move focus into the panel that just arrived, for keyboard and screen-reader users.
        const panel = current === 1 ? stepOnePanel.value : stepTwoPanel.value;
        panel?.querySelector('input, textarea, select')?.focus();
    }, SLIDE_MS);
});

onBeforeUnmount(() => clearTimeout(slideTimer));

const isCollapsed =(panelStep) => !sliding.value && step.value !== panelStep;

const normalizeSlug = () => {
    form.slug = form.slug.trim().toLowerCase();
};

const goToRules = () => {
    normalizeSlug();
    form.clearErrors(...STEP_ONE_FIELDS);

    // Same wording as the server's messages for these rules (lang/*/validation.php, attributes.php).
    const required = (attribute) => trans('validation.required', { attribute: trans(attribute) });
    const errors = {};
    if (!form.slug) errors.slug = required('attributes.group.slug');
    else if (!SLUG_PATTERN.test(form.slug)) errors.slug = trans('validation.custom.slug.regex');
    if (!form.name.trim()) errors.name = required('attributes.group.name');
    if (!form.description.trim()) errors.description = required('validation.attributes.description');
    if (!form.language_code) errors.language_code = required('validation.attributes.language_code');

    if (Object.keys(errors).length) {
        form.setError(errors);
        return;
    }

    step.value = 2;
};

const canAddRule = computed(() => form.rules.length < props.limits.rules);

const addRule = () => {
    if (!canAddRule.value) return;
    form.rules.push({ text: '', example: '' });
    nextTick(() => {
        const inputs = stepTwoPanel.value?.querySelectorAll('.rule-text');
        inputs?.[inputs.length - 1]?.focus();
    });
};

const removeRule = (index) => {
    form.rules.splice(index, 1);
};

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            // A rule left completely empty is just an unused row, not a rule.
            rules: data.rules
                .filter((rule) => rule.text.trim() || rule.example.trim())
                .map((rule) => ({ text: rule.text.trim(), example: rule.example.trim() || null })),
        }))
        .post(route('groups.store'), {
            onError: (errors) => {
                // The server may reject a step-1 field (e.g. a slug someone took meanwhile);
                // slide back so the error is actually visible.
                if (STEP_ONE_FIELDS.some((field) => errors[field])) step.value = 1;
            },
        });
};
</script>

<template>
    <Head :title="` | ${$t('groups.create.title')}`" />

    <section class="feed">
        <form class="post-form" @submit.prevent="submit">
            <div class="wizard-header">
                <h1 class="feed-title">{{ $t('groups.create.title') }}</h1>
                <p class="wizard-steps" aria-live="polite">
                    <span class="wizard-dot" :class="{ 'is-active': step === 1 }" aria-hidden="true"></span>
                    <span class="wizard-dot" :class="{ 'is-active': step === 2 }" aria-hidden="true"></span>
                    {{ $t('groups.create.step', { step, name: $t(step === 1 ? 'groups.create.basics' : 'groups.create.rules') }) }}
                </p>
            </div>

            <div class="wizard">
                <div class="wizard-track" :class="{ 'is-second': step === 2 }">
                    <!-- Step 1: basics -->
                    <div
                        ref="stepOnePanel"
                        class="wizard-panel"
                        :class="{ 'is-collapsed': isCollapsed(1) }"
                        :inert="step !== 1 || undefined"
                        :aria-hidden="step !== 1"
                    >
                        <div>
                            <label class="field-label" for="group-slug">{{ $t('groups.create.slug') }}</label>
                            <input
                                id="group-slug"
                                v-model="form.slug"
                                class="field-input"
                                :class="{ 'has-error': form.errors.slug }"
                                :maxlength="limits.slug"
                                autocomplete="off"
                                spellcheck="false"
                                placeholder="retro-gaming"
                                @blur="normalizeSlug"
                            />
                            <p v-if="form.errors.slug" class="field-error">{{ form.errors.slug }}</p>
                            <p v-else class="field-hint">{{ $t('groups.create.slug_hint', { max: limits.slug }) }}</p>
                        </div>

                        <TextInput :name="$t('groups.create.name')" v-model="form.name" :message="form.errors.name" />

                        <div>
                            <label class="field-label" for="group-description">{{ $t('groups.create.description') }}</label>
                            <textarea
                                id="group-description"
                                v-model="form.description"
                                class="field-input"
                                :class="{ 'has-error': form.errors.description }"
                                rows="3"
                                maxlength="250"
                                dir="auto"
                                :placeholder="$t('groups.create.description_placeholder')"
                            ></textarea>
                            <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
                            <p v-else class="field-hint text-end">{{ form.description.length }}/250</p>
                        </div>

                        <div>
                            <label class="field-label" for="group-language">{{ $t('groups.create.language') }}</label>
                            <select
                                id="group-language"
                                v-model="form.language_code"
                                class="field-input"
                                :class="{ 'has-error': form.errors.language_code }"
                            >
                                <option v-for="language in languages" :key="language.code" :value="language.code">
                                    {{ language.native_name === language.name ? language.name : `${language.name} — ${language.native_name}` }}
                                </option>
                            </select>
                            <p v-if="form.errors.language_code" class="field-error">{{ form.errors.language_code }}</p>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-ink">
                            <input v-model="form.is_private" type="checkbox" class="checkbox-input" />
                            {{ $t('groups.create.private') }}
                        </label>

                        <button type="button" class="btn-primary self-end" @click="goToRules">
                            {{ $t('groups.create.next') }} <span aria-hidden="true">→</span>
                        </button>
                    </div>

                    <!-- Step 2: rules -->
                    <div
                        ref="stepTwoPanel"
                        class="wizard-panel"
                        :class="{ 'is-collapsed': isCollapsed(2) }"
                        :inert="step !== 2 || undefined"
                        :aria-hidden="step !== 2"
                    >
                        <p class="field-hint mt-0">{{ $t('groups.create.rules_hint') }}</p>

                        <ol class="rule-editor-list">
                            <li v-for="(rule, index) in form.rules" :key="index" class="rule-editor">
                                <div class="rule-editor-head">
                                    <span class="rule-editor-number">{{ index + 1 }}.</span>
                                    <button
                                        type="button"
                                        class="rule-editor-remove"
                                        :aria-label="$t('groups.create.remove_rule_n', { n: index + 1 })"
                                        :title="$t('groups.create.remove_rule')"
                                        @click="removeRule(index)"
                                    >✕</button>
                                </div>

                                <input
                                    v-model="rule.text"
                                    class="field-input rule-text"
                                    :class="{ 'has-error': form.errors[`rules.${index}.text`] }"
                                    :maxlength="limits.rule_text"
                                    dir="auto"
                                    :aria-label="$t('groups.create.rule_n', { n: index + 1 })"
                                    :placeholder="$t('groups.create.rule_placeholder')"
                                />
                                <p v-if="form.errors[`rules.${index}.text`]" class="field-error">{{ form.errors[`rules.${index}.text`] }}</p>

                                <input
                                    v-model="rule.example"
                                    class="field-input mt-2"
                                    :class="{ 'has-error': form.errors[`rules.${index}.example`] }"
                                    :maxlength="limits.rule_example"
                                    dir="auto"
                                    :aria-label="$t('groups.create.example_n', { n: index + 1 })"
                                    :placeholder="$t('groups.create.example_placeholder')"
                                />
                                <p v-if="form.errors[`rules.${index}.example`]" class="field-error">{{ form.errors[`rules.${index}.example`] }}</p>
                            </li>
                        </ol>

                        <p v-if="form.errors.rules" class="field-error">{{ form.errors.rules }}</p>

                        <button type="button" class="btn-secondary self-start" :disabled="!canAddRule" @click="addRule">
                            {{ $t('groups.create.add_rule') }}
                        </button>
                        <p v-if="!canAddRule" class="field-hint mt-0">{{ $t('groups.create.rules_limit', { max: limits.rules }) }}</p>

                        <div class="wizard-actions">
                            <button type="button" class="btn-secondary" @click="step = 1">
                                <span aria-hidden="true">←</span> {{ $t('common.back') }}
                            </button>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                {{ form.processing ? $t('groups.create.submitting') : $t('groups.create.submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
