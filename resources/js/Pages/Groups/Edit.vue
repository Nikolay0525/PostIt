<script setup>
import { useForm } from '@inertiajs/vue3';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import RulesEditor from '@/Pages/Components/RulesEditor.vue';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    group: Object,
    languages: Array,
    limits: Object,
});

const form = useForm({
    name: props.group.name,
    description: props.group.description,
    language_code: props.group.language_code,
    rules: props.group.rules.map((rule) => ({ text: rule.text, example: rule.example ?? '' })),
});

const submit = () => {
    form
        .transform((data) => ({
            ...data,
            // A rule left completely empty is just an unused row, not a rule.
            rules: data.rules
                .filter((rule) => rule.text.trim() || rule.example.trim())
                .map((rule) => ({ text: rule.text.trim(), example: rule.example.trim() || null })),
        }))
        .put(route('groups.update', props.group.slug));
};
</script>

<template>
    <Head :title="` | ${$t('groups.edit.title')}`" />

    <section class="feed">
        <PageHeader :title="$t('groups.edit.title')" />

        <form class="post-form" @submit.prevent="submit">
            <fieldset class="settings-section">
                <legend class="settings-heading">{{ $t('groups.edit.basics') }}</legend>

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
            </fieldset>

            <fieldset class="settings-section">
                <legend class="settings-heading">{{ $t('groups.show.rules') }}</legend>

                <p class="field-hint mt-0">{{ $t('groups.edit.rules_versioned') }}</p>
                <RulesEditor v-model="form.rules" :limits="limits" :errors="form.errors" />
            </fieldset>

            <div class="wizard-actions">
                <Link :href="route('groups.show', group.slug)" class="btn-secondary">
                    <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back') }}
                </Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? $t('groups.edit.saving') : $t('groups.edit.save') }}
                </button>
            </div>
        </form>
    </section>
</template>
