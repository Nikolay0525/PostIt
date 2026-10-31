<script setup>
import { computed } from 'vue';
import AuthPrompt from '@/Pages/Components/AuthPrompt.vue';
import { formatCount } from '@/utils/format';

// The group's card (name, language, members, join or edit, description, rules): to the right of
// the feed on the group's page and on its posts' pages. A direct child of the page, so that
// .home-layout can put it in the right column.
const props = defineProps({
    group: { type: Object, required: true },
    // Whether the viewer is the group's Owner, who may edit it.
    canEdit: Boolean,
    // useGroupMembership()'s answer: the page owns it, because the page reads `joined` too.
    membership: { type: Object, required: true },
    // The page's own title is the h1 on a post's page.
    nameTag: { type: String, default: 'h1' },
});

// Language names are stored the way their speakers write them in a list, in lower case.
const languageName = computed(() => props.group.language_name.charAt(0).toUpperCase() + props.group.language_name.slice(1));

const { joined, showLoginPrompt, subscribing, subscribeError, buttonLabel, toggleJoin } = props.membership;
</script>

<template>
    <!-- On a wide screen this goes to the right of the feed (.page-aside, see .home-layout); on a
         narrower one, and for a guest, it stays above the feed. -->
    <aside class="page-aside">
        <!-- In the right column only: a heading on the same line as the feed's strip and the
             panel's "Groups", like those. More cards (the moderators) will go under it. -->
        <div class="feed-tabs-row page-aside-heading">
            <h2 class="page-header-title">{{ $t('groups.show.about') }}</h2>
        </div>

        <header class="group-header">
            <span class="group-avatar" aria-hidden="true">{{ group.name.charAt(0).toUpperCase() }}</span>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2">
                    <component :is="nameTag" class="group-name" dir="auto">{{ group.name }}</component>
                    <!-- The whole name in its own language, not the code ("UK" reads as a country). -->
                    <span class="group-lang normal-case" :title="$t('groups.show.language')" dir="auto">{{ languageName }}</span>
                </div>
                <p class="group-stats">
                    {{ $tChoice('groups.show.members', group.members_count, { num: formatCount(group.members_count) }) }}<span v-if="group.is_private"> · {{ $t('groups.show.private') }}</span>
                </p>
            </div>

            <!-- The Owner is always a member, so in this place they get "Edit" instead of "Subscribe";
                 in the header, so it is there on a phone too. -->
            <Link v-if="canEdit" :href="route('groups.edit', group.slug)" class="group-join btn-secondary justify-center">
                {{ $t('groups.show.edit') }}
            </Link>
            <button
                v-else
                type="button"
                class="group-join"
                :class="joined ? 'btn-secondary' : 'btn-primary'"
                :disabled="subscribing"
                @click="toggleJoin"
            >{{ buttonLabel }}</button>

            <p class="group-desc" dir="auto">{{ group.description }}</p>
            <div v-if="group.rules?.length" class="group-rules">
                <p class="font-medium text-ink">{{ $t('groups.show.rules') }}</p>
                <ol class="group-rules-list">
                    <li v-for="(rule, index) in group.rules" :key="index" dir="auto">
                        {{ rule.text }}
                        <span v-if="rule.example" class="group-rule-example">{{ $t('groups.show.rule_example', { example: rule.example }) }}</span>
                    </li>
                </ol>
            </div>

            <p v-if="subscribeError" class="vote-error w-full" :title="subscribeError">⚠ {{ subscribeError }}</p>

            <p v-if="showLoginPrompt" class="post-login-prompt w-full">
                <AuthPrompt :action="group.is_private ? 'request_join' : 'subscribe'" />
            </p>
        </header>
    </aside>
</template>
