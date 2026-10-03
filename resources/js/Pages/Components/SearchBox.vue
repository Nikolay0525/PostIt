<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import { formatCount } from '@/utils/format';
import { getJson } from '@/utils/http';

// The navbar search: Enter opens the results page; while typing, the best few groups, people
// and posts show up under the field (Roblox-style), plus "Search "…" in posts / groups / people".
const MIN_LENGTH = 2;
const DEBOUNCE_MS = 250;

const page = usePage();

const root = ref(null);
const term = ref('');
const open = ref(false);
const suggestions = ref({ groups: [], people: [], posts: [] });
// Index into `items` picked with the arrow keys; -1 = none, Enter then searches.
const active = ref(-1);

// On the search page the field shows what was searched for; anywhere else it starts empty.
watch(
    () => (page.component === 'Search' ? page.props.q : ''),
    (q) => (term.value = q ?? ''),
    { immediate: true },
);

const trimmed = computed(() => term.value.trim());

// Everything the panel lists, in display order, so the arrow keys walk them the same way.
const items = computed(() => {
    if (trimmed.value.length < MIN_LENGTH) return [];

    const { groups, people, posts } = suggestions.value;

    return [
        ...groups.map((group) => ({ kind: 'group', key: `g${group.id}`, href: route('groups.show', group.slug), group })),
        ...people.map((person) => ({ kind: 'person', key: `u${person.id}`, href: route('users.show', person.username), person })),
        ...posts.map((post) => ({ kind: 'post', key: `p${post.id}`, href: route('posts.show', [post.group.slug, post.slug]), post })),
        ...['posts', 'groups', 'people'].map((type) => ({
            kind: 'search',
            key: `s${type}`,
            href: route('search', { q: trimmed.value, type }),
            type,
        })),
    ];
});

const section = (kind) => items.value.filter((item) => item.kind === kind);
const indexOf = (item) => items.value.indexOf(item);

// --- Fetching, debounced; an answer that arrives after a newer request is dropped. ------------

let timer = null;
let controller = null;

const fetchSuggestions = async (q) => {
    controller?.abort();
    controller = new AbortController();

    try {
        suggestions.value = await getJson(route('search.suggest', { q }), { signal: controller.signal });
    } catch (error) {
        // A cancelled request isn't a failure; anything else just leaves the old suggestions.
        if (error.name !== 'AbortError') console.error('Search suggestions failed:', error);
    }
};

watch(trimmed, (q) => {
    clearTimeout(timer);
    active.value = -1;

    if (q.length < MIN_LENGTH) {
        controller?.abort();
        suggestions.value = { groups: [], people: [], posts: [] };
        return;
    }

    timer = setTimeout(() => fetchSuggestions(q), DEBOUNCE_MS);
});

// --- Choosing -------------------------------------------------------------------------------

const close = () => {
    open.value = false;
    active.value = -1;
};

const go = (href) => {
    close();
    router.visit(href);
};

const submit = () => {
    if (active.value >= 0) {
        go(items.value[active.value].href);
    } else if (trimmed.value) {
        go(route('search', { q: trimmed.value }));
    }
};

const move = (step) => {
    if (!items.value.length) return;

    open.value = true;
    active.value = (active.value + step + items.value.length) % items.value.length;
};

const onDocumentClick = (event) => {
    if (root.value && !root.value.contains(event.target)) close();
};

// Leaving the page (any link, back/forward) closes the panel.
let stopListening = null;

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    stopListening = router.on('navigate', close);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
    stopListening?.();
    clearTimeout(timer);
    controller?.abort();
});
</script>

<template>
    <div ref="root" class="relative max-w-md">
        <form class="search-bar" role="search" @submit.prevent="submit">
            <svg class="search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <circle cx="9" cy="9" r="5.5" />
                <path d="m13.5 13.5 3.5 3.5" stroke-linecap="round" />
            </svg>
            <input
                v-model="term"
                type="search"
                name="q"
                maxlength="100"
                autocomplete="off"
                spellcheck="false"
                dir="auto"
                class="search-input"
                role="combobox"
                aria-controls="search-suggestions"
                aria-autocomplete="list"
                :aria-expanded="open && items.length > 0"
                :aria-activedescendant="active >= 0 ? `search-option-${active}` : undefined"
                :placeholder="$t('nav.search_placeholder')"
                :aria-label="$t('nav.search')"
                @focus="open = true"
                @input="open = true"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.esc="close"
            />
        </form>

        <div v-if="open && items.length" id="search-suggestions" class="search-panel" role="listbox" :aria-label="$t('nav.search')">
            <template v-for="kind in ['group', 'person', 'post']" :key="kind">
                <template v-if="section(kind).length">
                    <p class="search-panel-heading">{{ $t(`search.suggest.${kind}`) }}</p>

                    <Link
                        v-for="item in section(kind)"
                        :id="`search-option-${indexOf(item)}`"
                        :key="item.key"
                        :href="item.href"
                        role="option"
                        :aria-selected="active === indexOf(item)"
                        class="search-option"
                        :class="{ 'is-active': active === indexOf(item) }"
                        @mouseenter="active = indexOf(item)"
                        @click="close"
                    >
                        <template v-if="kind === 'group'">
                            <span class="avatar-sm rounded-md" aria-hidden="true">{{ item.group.name.charAt(0).toUpperCase() }}</span>
                            <span class="min-w-0 flex-1 truncate" dir="auto">
                                {{ item.group.name }} <span v-if="item.group.is_private" aria-hidden="true">🔒</span>
                            </span>
                            <span class="search-option-meta">
                                {{ $tChoice('groups.show.members', item.group.members_count, { num: formatCount(item.group.members_count) }) }}
                            </span>
                        </template>

                        <template v-else-if="kind === 'person'">
                            <UserAvatar :url="item.person.avatar_url" :username="item.person.username" class="avatar-sm" />
                            <span class="min-w-0 flex-1 truncate" dir="auto">{{ item.person.username }}</span>
                            <span class="search-option-meta">
                                {{ $tChoice('users.show.followers', item.person.followers_count, { num: formatCount(item.person.followers_count) }) }}
                            </span>
                        </template>

                        <template v-else>
                            <span class="min-w-0 flex-1 truncate" dir="auto">{{ item.post.title || item.post.preview }}</span>
                            <span class="search-option-meta truncate" dir="auto">{{ item.post.group.name }}</span>
                        </template>
                    </Link>
                </template>
            </template>

            <div class="search-panel-actions">
                <Link
                    v-for="item in section('search')"
                    :id="`search-option-${indexOf(item)}`"
                    :key="item.key"
                    :href="item.href"
                    role="option"
                    :aria-selected="active === indexOf(item)"
                    class="search-option"
                    :class="{ 'is-active': active === indexOf(item) }"
                    @mouseenter="active = indexOf(item)"
                    @click="close"
                >
                    <svg class="h-4 w-4 shrink-0 text-muted" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <circle cx="9" cy="9" r="5.5" />
                        <path d="m13.5 13.5 3.5 3.5" stroke-linecap="round" />
                    </svg>
                    <span class="min-w-0 flex-1 truncate" dir="auto">{{ $t(`search.suggest.in_${item.type}`, { q: trimmed }) }}</span>
                </Link>
            </div>
        </div>
    </div>
</template>
