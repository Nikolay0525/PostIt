<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    can_enable_adult_content: Boolean,
    ui_languages: Array,
    speaking_languages: Array,
    max_speaking_languages: Number,
    status: String,
});

const form = useForm({ ...props.settings, speaking_languages: [...props.settings.speaking_languages] });

const languagesByCode = computed(() => Object.fromEntries(props.speaking_languages.map((l) => [l.code, l])));
// Always by name, so the order doesn't change between editing and reloading the page.
const selectedLanguages = computed(() => form.speaking_languages
    .map((code) => languagesByCode.value[code])
    .filter(Boolean)
    .sort((a, b) => a.name.localeCompare(b.name)));
const atLanguageLimit = computed(() => form.speaking_languages.length >= props.max_speaking_languages);

const languageQuery = ref('');
// Matches the English name, the language's own name, or its code ("uk", "укр", "Ukrainian").
// Best match first — an exact code, then a name starting with the query, then one containing
// it — so "de" puts German above Nederlands and Enter picks the likely intended language.
const matchRank = (language, query) => {
    const names = [language.name, language.native_name].map((n) => n.toLocaleLowerCase());
    if (language.code === query) return 0;
    if (names.some((n) => n.startsWith(query))) return 1;
    if (names.some((n) => n.includes(query))) return 2;
    return null;
};

const languageMatches = computed(() => {
    const query = languageQuery.value.trim().toLocaleLowerCase();
    if (!query) return [];

    return props.speaking_languages
        .filter((l) => !form.speaking_languages.includes(l.code))
        .map((l) => ({ language: l, rank: matchRank(l, query) }))
        .filter((m) => m.rank !== null)
        .sort((a, b) => a.rank - b.rank)
        .slice(0, 8)
        .map((m) => m.language);
});

const addLanguage = (code) => {
    if (atLanguageLimit.value || form.speaking_languages.includes(code)) return;
    form.speaking_languages.push(code);
    languageQuery.value = '';
};

const removeLanguage = (code) => {
    form.speaking_languages = form.speaking_languages.filter((c) => c !== code);
};

// Enter in the search box picks the first match instead of submitting the whole form.
const addFirstMatch = () => {
    if (languageMatches.value.length) addLanguage(languageMatches.value[0].code);
};

const languageLabel = (language) => language.native_name === language.name
    ? language.name
    : `${language.native_name} — ${language.name}`;

const languageErrors = computed(() => Object.entries(form.errors)
    .filter(([key]) => key === 'speaking_languages' || key.startsWith('speaking_languages.'))
    .map(([, message]) => message));

const toggles = [
    { section: 'Content', key: 'show_swear_words', label: 'Show swear words', hint: 'Otherwise they are masked in posts and comments.' },
    { section: 'Content', key: 'show_adult_content', label: 'Show adult (18+) content', hint: 'Only available if you are 18 or older.' },
    { section: 'Privacy', key: 'allow_messages', label: 'Allow direct messages', hint: 'Other users can write to you privately.' },
    { section: 'Privacy', key: 'enable_cookies', label: 'Allow optional cookies', hint: 'Cookies needed to keep you signed in are always used.' },
    { section: 'Appearance', key: 'dark_theme', label: 'Dark theme', hint: null },
];

const togglesBySection = computed(() => toggles.reduce((sections, toggle) => {
    (sections[toggle.section] ??= []).push(toggle);
    return sections;
}, {}));

const isDisabled = (key) => key === 'show_adult_content' && !props.can_enable_adult_content;

const submit = () => {
    form.patch(route('settings.update'), {
        preserveScroll: true,
        // The saved values become the new baseline, so the form reads as clean again.
        onSuccess: () => form.defaults(),
    });
};
</script>

<template>
    <Head :title="` | Settings`" />

    <section class="feed">
        <form class="post-form" @submit.prevent="submit">
            <h1 class="feed-title">Settings</h1>

            <p v-if="status && !form.isDirty" class="settings-status" role="status">{{ status }}</p>

            <fieldset class="settings-section">
                <legend class="settings-heading">Languages</legend>

                <div>
                    <label class="field-label" for="ui-language">Interface language</label>
                    <select
                        id="ui-language"
                        v-model="form.ui_language_code"
                        class="field-input"
                        :class="{ 'has-error': form.errors.ui_language_code }"
                    >
                        <option v-for="language in ui_languages" :key="language.code" :value="language.code">
                            {{ language.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.ui_language_code" class="field-error">{{ form.errors.ui_language_code }}</p>
                </div>

                <div>
                    <label class="field-label" for="language-search">Languages you speak</label>
                    <p class="field-hint mt-0 mb-2">Used to suggest groups in these languages. Up to {{ max_speaking_languages }}.</p>

                    <ul v-if="selectedLanguages.length" class="language-chips" aria-label="Selected languages">
                        <li v-for="language in selectedLanguages" :key="language.code" class="language-chip">
                            <span dir="auto">{{ languageLabel(language) }}</span>
                            <button
                                type="button"
                                class="language-chip-remove"
                                :aria-label="`Remove ${language.name}`"
                                @click="removeLanguage(language.code)"
                            >✕</button>
                        </li>
                    </ul>
                    <p v-else class="field-hint mt-0 mb-2">No languages selected.</p>

                    <div class="relative">
                        <input
                            id="language-search"
                            v-model="languageQuery"
                            type="search"
                            class="field-input"
                            :class="{ 'has-error': languageErrors.length }"
                            :disabled="atLanguageLimit"
                            :placeholder="atLanguageLimit ? 'Limit reached — remove one to add another' : 'Search: English, Українська, de…'"
                            autocomplete="off"
                            role="combobox"
                            aria-controls="language-results"
                            :aria-expanded="languageMatches.length > 0"
                            @keydown.enter.prevent="addFirstMatch"
                        />
                        <ul v-if="languageMatches.length" id="language-results" class="language-results" role="listbox">
                            <li v-for="language in languageMatches" :key="language.code" role="option">
                                <button type="button" class="language-result" @click="addLanguage(language.code)">
                                    <span dir="auto">{{ languageLabel(language) }}</span>
                                    <span class="language-code">{{ language.code }}</span>
                                </button>
                            </li>
                        </ul>
                        <p v-else-if="languageQuery.trim()" class="field-hint">No matching language.</p>
                    </div>
                    <p v-for="message in languageErrors" :key="message" class="field-error">{{ message }}</p>
                </div>
            </fieldset>

            <fieldset v-for="(items, section) in togglesBySection" :key="section" class="settings-section">
                <legend class="settings-heading">{{ section }}</legend>

                <div v-for="toggle in items" :key="toggle.key">
                    <label class="settings-toggle" :class="{ 'is-disabled': isDisabled(toggle.key) }">
                        <input
                            v-model="form[toggle.key]"
                            type="checkbox"
                            class="checkbox-input mt-0.5"
                            :disabled="isDisabled(toggle.key)"
                        />
                        <span>
                            <span class="block text-sm font-medium text-ink">{{ toggle.label }}</span>
                            <span v-if="toggle.hint" class="block text-xs text-muted">{{ toggle.hint }}</span>
                        </span>
                    </label>
                    <p v-if="form.errors[toggle.key]" class="field-error">{{ form.errors[toggle.key] }}</p>
                </div>
            </fieldset>

            <div class="wizard-actions">
                <span class="text-sm text-muted">{{ form.isDirty ? 'Unsaved changes' : '' }}</span>
                <button type="submit" class="btn-primary" :disabled="form.processing || !form.isDirty">
                    {{ form.processing ? 'Saving…' : 'Save' }}
                </button>
            </div>
        </form>
    </section>
</template>
