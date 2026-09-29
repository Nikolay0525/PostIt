<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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

    const errors = {};
    if (!form.slug) errors.slug = 'The slug is required.';
    else if (!SLUG_PATTERN.test(form.slug)) errors.slug = 'Only lowercase Latin letters, digits and single hyphens between words (e.g. "retro-gaming").';
    if (!form.name.trim()) errors.name = 'The name is required.';
    if (!form.description.trim()) errors.description = 'The description is required.';
    if (!form.language_code) errors.language_code = 'Pick a language.';

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
    <Head :title="` | New group`" />

    <section class="feed">
        <form class="post-form" @submit.prevent="submit">
            <div class="wizard-header">
                <h1 class="feed-title">New group</h1>
                <p class="wizard-steps" aria-live="polite">
                    <span class="wizard-dot" :class="{ 'is-active': step === 1 }" aria-hidden="true"></span>
                    <span class="wizard-dot" :class="{ 'is-active': step === 2 }" aria-hidden="true"></span>
                    Step {{ step }} of 2 · {{ step === 1 ? 'Basics' : 'Rules' }}
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
                            <label class="field-label" for="group-slug">Slug</label>
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
                            <p v-else class="field-hint">
                                The group's permanent short name, in Latin letters: lowercase letters, digits and hyphens, up to {{ limits.slug }} characters.
                            </p>
                        </div>

                        <TextInput name="Name" v-model="form.name" :message="form.errors.name" />

                        <div>
                            <label class="field-label" for="group-description">Description</label>
                            <textarea
                                id="group-description"
                                v-model="form.description"
                                class="field-input"
                                :class="{ 'has-error': form.errors.description }"
                                rows="3"
                                maxlength="250"
                                dir="auto"
                                placeholder="What is this group about?"
                            ></textarea>
                            <p v-if="form.errors.description" class="field-error">{{ form.errors.description }}</p>
                            <p v-else class="field-hint text-end">{{ form.description.length }}/250</p>
                        </div>

                        <div>
                            <label class="field-label" for="group-language">Language</label>
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
                            Private group — only members can see its posts
                        </label>

                        <button type="button" class="btn-primary self-end" @click="goToRules">
                            Next: rules <span aria-hidden="true">→</span>
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
                        <p class="field-hint mt-0">
                            Optional. One rule per item; add an example where the rule alone could be read more than one way.
                        </p>

                        <ol class="rule-editor-list">
                            <li v-for="(rule, index) in form.rules" :key="index" class="rule-editor">
                                <div class="rule-editor-head">
                                    <span class="rule-editor-number">{{ index + 1 }}.</span>
                                    <button
                                        type="button"
                                        class="rule-editor-remove"
                                        :aria-label="`Remove rule ${index + 1}`"
                                        title="Remove rule"
                                        @click="removeRule(index)"
                                    >✕</button>
                                </div>

                                <input
                                    v-model="rule.text"
                                    class="field-input rule-text"
                                    :class="{ 'has-error': form.errors[`rules.${index}.text`] }"
                                    :maxlength="limits.rule_text"
                                    dir="auto"
                                    :aria-label="`Rule ${index + 1}`"
                                    placeholder="Rule, e.g. No spam."
                                />
                                <p v-if="form.errors[`rules.${index}.text`]" class="field-error">{{ form.errors[`rules.${index}.text`] }}</p>

                                <input
                                    v-model="rule.example"
                                    class="field-input mt-2"
                                    :class="{ 'has-error': form.errors[`rules.${index}.example`] }"
                                    :maxlength="limits.rule_example"
                                    dir="auto"
                                    :aria-label="`Example for rule ${index + 1}`"
                                    placeholder="Example (optional), e.g. Posting the same link in several threads."
                                />
                                <p v-if="form.errors[`rules.${index}.example`]" class="field-error">{{ form.errors[`rules.${index}.example`] }}</p>
                            </li>
                        </ol>

                        <p v-if="form.errors.rules" class="field-error">{{ form.errors.rules }}</p>

                        <button type="button" class="btn-secondary self-start" :disabled="!canAddRule" @click="addRule">
                            + Add rule
                        </button>
                        <p v-if="!canAddRule" class="field-hint mt-0">A group can have up to {{ limits.rules }} rules.</p>

                        <div class="wizard-actions">
                            <button type="button" class="btn-secondary" @click="step = 1">
                                <span aria-hidden="true">←</span> Back
                            </button>
                            <button type="submit" class="btn-primary" :disabled="form.processing">
                                {{ form.processing ? 'Creating…' : 'Create group' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</template>
