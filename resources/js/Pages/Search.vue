<script setup>
import { computed } from 'vue';
import { InfiniteScroll, router } from '@inertiajs/vue3';
import GroupRow from '@/Pages/Components/GroupRow.vue';
import PersonRow from '@/Pages/Components/PersonRow.vue';
import PostFeed from '@/Pages/Components/PostFeed.vue';
import { formatCount } from '@/utils/format';

const props = defineProps({
    // What was typed, as typed (trimmed).
    q: String,
    // 'all', 'posts', 'groups' or 'people'.
    type: String,
    min_length: Number,
    // Each is null when not on this tab (or the term is too short). On All, groups and people are
    // a short preview — meta.total says how many there are in all; a tab's own list scrolls.
    posts: Object,
    groups: Object,
    people: Object,
});

const tabs = ['all', 'posts', 'groups', 'people'];

// Like the home tabs: reload only the results, starting the list over from page 1.
const show = (type) => {
    if (type === props.type) return;

    router.get(route('search'), type === 'all' ? { q: props.q } : { q: props.q, type }, {
        only: ['type', 'posts', 'groups', 'people'],
        reset: ['posts', 'groups', 'people'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const tooShort = computed(() => !props.posts && !props.groups && !props.people);
const nothingFound = computed(() =>
    !tooShort.value && [props.posts, props.groups, props.people].every((list) => !list?.data.length));

// "All 12 groups" under a preview that doesn't show them all.
const more = (list) => (list?.meta && list.meta.total > list.data.length ? list.meta.total : 0);
</script>

<template>
    <Head :title="` | ${$t('search.title')}${q ? `: ${q}` : ''}`" />

    <section class="feed">
        <h1 class="feed-title profile-text" dir="auto">
            {{ q ? $t('search.heading', { q }) : $t('search.title') }}
        </h1>

        <p v-if="!q" class="text-sm text-muted">{{ $t('search.empty_query') }}</p>
        <p v-else-if="tooShort" class="text-sm text-muted">{{ $t('search.too_short', { min: min_length }) }}</p>

        <template v-else>
            <div class="feed-tabs-row">
                <div class="feed-tabs" role="tablist" :aria-label="$t('search.tabs_label')">
                    <button
                        v-for="tab in tabs"
                        :key="tab"
                        type="button"
                        role="tab"
                        class="feed-tab"
                        :class="{ 'is-active': type === tab }"
                        :aria-selected="type === tab"
                        @click="show(tab)"
                    >{{ $t(`search.tabs.${tab}`) }}</button>
                </div>
            </div>

            <p v-if="nothingFound" class="text-sm text-muted">{{ $t('search.nothing', { q }) }}</p>

            <!-- Groups and people: a short preview on All, the whole scrolling list on their tab. -->
            <div v-if="groups?.data.length" class="search-section">
                <h2 v-if="type === 'all'" class="settings-heading" dir="auto">{{ $t('search.tabs.groups') }}</h2>
                <InfiniteScroll v-if="type === 'groups'" data="groups" class="search-list">
                    <GroupRow v-for="group in groups.data" :key="group.id" :group="group" />
                </InfiniteScroll>
                <div v-else class="search-list">
                    <GroupRow v-for="group in groups.data" :key="group.id" :group="group" />
                    <button v-if="more(groups)" type="button" class="profile-link-btn self-start" @click="show('groups')">
                        {{ $t('search.all_groups', { num: formatCount(more(groups)) }) }}
                    </button>
                </div>
            </div>

            <div v-if="people?.data.length" class="search-section">
                <h2 v-if="type === 'all'" class="settings-heading" dir="auto">{{ $t('search.tabs.people') }}</h2>
                <InfiniteScroll v-if="type === 'people'" data="people" class="search-list">
                    <PersonRow v-for="person in people.data" :key="person.id" :person="person" />
                </InfiniteScroll>
                <div v-else class="search-list">
                    <PersonRow v-for="person in people.data" :key="person.id" :person="person" />
                    <button v-if="more(people)" type="button" class="profile-link-btn self-start" @click="show('people')">
                        {{ $t('search.all_people', { num: formatCount(more(people)) }) }}
                    </button>
                </div>
            </div>

            <PostFeed
                v-if="posts?.data.length"
                :title="type === 'all' ? $t('search.tabs.posts') : ''"
                :posts="posts"
            />
        </template>
    </section>
</template>
