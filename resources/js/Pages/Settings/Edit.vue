<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';

const props = defineProps({
    settings: Object,
    can_enable_adult_content: Boolean,
    ui_languages: Array,
    speaking_languages: Array,
    max_speaking_languages: Number,
    max_attached_subscriptions: Number,
    // { groups: [{ id, slug, name, icon_url, is_private, attached_on_feed }], people: [{ id, username, avatar_url, attached_on_feed }] }, by name.
    subscriptions: Object,
    status: String,
});

const attachedIds = (items) => items.filter((item) => item.attached_on_feed).map((item) => item.id);

const form = useForm({
    ...props.settings,
    speaking_languages: [...props.settings.speaking_languages],
    attached_groups: attachedIds(props.subscriptions.groups),
    attached_people: attachedIds(props.subscriptions.people),
});

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

// Texts are lang keys: settings.sections.<section>, settings.toggles.<key>.label / .hint.
const toggles = [
    { section: 'content', key: 'show_swear_words', hasHint: true },
    { section: 'content', key: 'show_adult_content', hasHint: true },
    { section: 'privacy', key: 'allow_messages', hasHint: true },
    { section: 'privacy', key: 'enable_cookies', hasHint: true },
];

// App\Enums\ThemeMode; texts are settings.theme.<mode>.label / .hint.
const themeModes = ['clock', 'browser', 'manual'];

const togglesBySection = computed(() => toggles.reduce((sections, toggle) => {
    (sections[toggle.section] ??= []).push(toggle);
    return sections;
}, {}));

const isDisabled = (key) => key === 'show_adult_content' && !props.can_enable_adult_content;

// The ticks are ids in `form` (attached_groups / attached_people), saved with the rest by Save.
const subscriptionKinds = [
    { kind: 'groups', field: 'attached_groups', name: (group) => group.name },
    { kind: 'people', field: 'attached_people', name: (person) => person.username },
];

const subscriptionQuery = ref('');
const visibleSubscriptions = (kind) => {
    const query = subscriptionQuery.value.trim().toLocaleLowerCase();
    if (!query) return props.subscriptions[kind.kind];

    return props.subscriptions[kind.kind].filter((item) => kind.name(item).toLocaleLowerCase().includes(query));
};

const submit = () => {
    form.patch(route('settings.update'), {
        preserveScroll: true,
        // The saved values become the new baseline, so the form reads as clean again.
        onSuccess: () => form.defaults(),
    });
};
</script>

<template>
    <Head :title="` | ${$t('settings.title')}`" />

    <section class="feed">
        <form class="post-form" @submit.prevent="submit">
            <h1 class="feed-title">{{ $t('settings.title') }}</h1>

            <p v-if="status === 'settings-saved' && !form.isDirty" class="settings-status" role="status">{{ $t('settings.saved') }}</p>

            <fieldset class="settings-section">
                <legend class="settings-heading">{{ $t('settings.languages') }}</legend>

                <div>
                    <label class="field-label" for="ui-language">{{ $t('settings.ui_language') }}</label>
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
                    <label class="field-label" for="language-search">{{ $t('settings.speaking') }}</label>
                    <p class="field-hint mt-0 mb-2">{{ $t('settings.speaking_hint', { max: max_speaking_languages }) }}</p>

                    <ul v-if="selectedLanguages.length" class="language-chips" :aria-label="$t('settings.selected')">
                        <li v-for="language in selectedLanguages" :key="language.code" class="language-chip">
                            <span dir="auto">{{ languageLabel(language) }}</span>
                            <button
                                type="button"
                                class="language-chip-remove"
                                :aria-label="$t('settings.remove_language', { name: language.name })"
                                @click="removeLanguage(language.code)"
                            >✕</button>
                        </li>
                    </ul>
                    <p v-else class="field-hint mt-0 mb-2">{{ $t('settings.none_selected') }}</p>

                    <div class="relative">
                        <input
                            id="language-search"
                            v-model="languageQuery"
                            type="search"
                            class="field-input"
                            :class="{ 'has-error': languageErrors.length }"
                            :disabled="atLanguageLimit"
                            :placeholder="$t(atLanguageLimit ? 'settings.limit_reached' : 'settings.search_placeholder')"
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
                        <p v-else-if="languageQuery.trim()" class="field-hint">{{ $t('settings.no_match') }}</p>
                    </div>
                    <p v-for="message in languageErrors" :key="message" class="field-error">{{ message }}</p>
                </div>
            </fieldset>

            <fieldset v-for="(items, section) in togglesBySection" :key="section" class="settings-section">
                <legend class="settings-heading">{{ $t(`settings.sections.${section}`) }}</legend>

                <div v-for="toggle in items" :key="toggle.key">
                    <label class="settings-toggle" :class="{ 'is-disabled': isDisabled(toggle.key) }">
                        <input
                            v-model="form[toggle.key]"
                            type="checkbox"
                            class="checkbox-input mt-0.5"
                            :disabled="isDisabled(toggle.key)"
                        />
                        <span>
                            <span class="block text-sm font-medium text-ink">{{ $t(`settings.toggles.${toggle.key}.label`) }}</span>
                            <span v-if="toggle.hasHint" class="block text-xs text-muted">{{ $t(`settings.toggles.${toggle.key}.hint`) }}</span>
                        </span>
                    </label>
                    <p v-if="form.errors[toggle.key]" class="field-error">{{ form.errors[toggle.key] }}</p>
                </div>
            </fieldset>

            <!-- The home page's side panel links here. -->
            <fieldset id="subscriptions" class="settings-section scroll-mt-6">
                <legend class="settings-heading">{{ $t('settings.sections.subscriptions') }}</legend>
                <p class="field-hint mt-0">{{ $t('settings.subscriptions.hint') }}</p>

                <div>
                    <label class="field-label" for="attached-amount">{{ $t('settings.subscriptions.amount') }}</label>
                    <input
                        id="attached-amount"
                        v-model.number="form.amount_of_attached_subscriptions"
                        type="number"
                        min="1"
                        :max="max_attached_subscriptions"
                        class="field-input w-24"
                        :class="{ 'has-error': form.errors.amount_of_attached_subscriptions }"
                    />
                    <p class="field-hint">{{ $t('settings.subscriptions.amount_hint', { max: max_attached_subscriptions }) }}</p>
                    <p v-if="form.errors.amount_of_attached_subscriptions" class="field-error">{{ form.errors.amount_of_attached_subscriptions }}</p>
                </div>

                <input
                    v-if="subscriptions.groups.length || subscriptions.people.length"
                    v-model="subscriptionQuery"
                    type="search"
                    class="field-input"
                    :placeholder="$t('settings.subscriptions.search_placeholder')"
                    :aria-label="$t('settings.subscriptions.search_placeholder')"
                    autocomplete="off"
                    @keydown.enter.prevent
                />

                <div v-for="kind in subscriptionKinds" :key="kind.kind">
                    <h3 class="field-label flex items-baseline justify-between gap-3">
                        <span>{{ $t(`settings.subscriptions.${kind.kind}`) }}</span>
                        <span v-if="subscriptions[kind.kind].length" class="text-xs font-normal text-muted">
                            {{ $t('settings.subscriptions.attached_count', { attached: form[kind.field].length, total: subscriptions[kind.kind].length }) }}
                        </span>
                    </h3>

                    <ul v-if="visibleSubscriptions(kind).length" class="flex flex-col gap-2">
                        <li v-for="item in visibleSubscriptions(kind)" :key="item.id">
                            <label class="settings-toggle items-center">
                                <input
                                    v-model="form[kind.field]"
                                    type="checkbox"
                                    class="checkbox-input"
                                    :value="item.id"
                                />
                                <span v-if="kind.kind === 'groups'" class="avatar-sm rounded-md" aria-hidden="true">{{ item.name.charAt(0).toUpperCase() }}</span>
                                <UserAvatar v-else :url="item.avatar_url" :username="item.username" class="avatar-sm" />
                                <span class="min-w-0 truncate text-sm text-ink" dir="auto">
                                    {{ kind.name(item) }}
                                    <span v-if="item.is_private" :title="$t('groups.show.private')">🔒</span>
                                </span>
                            </label>
                        </li>
                    </ul>
                    <p v-else-if="subscriptions[kind.kind].length" class="field-hint mt-0">{{ $t('settings.subscriptions.no_match') }}</p>
                    <p v-else class="field-hint mt-0">{{ $t(`settings.subscriptions.no_${kind.kind}`) }}</p>
                </div>            </fieldset>

            <fieldset class="settings-section">
                <legend class="settings-heading">{{ $t('settings.sections.appearance') }}</legend>

                <label v-for="mode in themeModes" :key="mode" class="settings-toggle">
                    <input
                        v-model="form.theme_mode"
                        type="radio"
                        name="theme_mode"
                        :value="mode"
                        class="checkbox-input mt-0.5 rounded-full"
                    />
                    <span>
                        <span class="block text-sm font-medium text-ink">{{ $t(`settings.theme.${mode}.label`) }}</span>
                        <span class="block text-xs text-muted">{{ $t(`settings.theme.${mode}.hint`) }}</span>
                    </span>
                </label>
                <p v-if="form.errors.theme_mode" class="field-error">{{ form.errors.theme_mode }}</p>
            </fieldset>

            <div class="wizard-actions">
                <span class="text-sm text-muted">{{ form.isDirty ? $t('settings.unsaved') : '' }}</span>
                <button type="submit" class="btn-primary" :disabled="form.processing || !form.isDirty">
                    {{ form.processing ? $t('settings.saving') : $t('settings.save') }}
                </button>
            </div>
        </form>
    </section>
</template>
