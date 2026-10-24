<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';

// The groups and people the user attached in Settings, on the home page. On a wide screen it is
// always there, left of the feed; on a narrow one it stays hidden until the feed's panel button
// opens it as a drawer — there is no hover on a touch screen, so it opens with a tap.
const props = defineProps({
    // { groups: [{ id, slug, name, is_private, new_posts_count }], people: [{ id, username, avatar_url }] }.
    // Groups with the most new posts come first, then by name; people by name.
    panel: { type: Object, required: true },
    open: Boolean,
    // 'groups' / 'people' while the Following feed shows that source, so its heading reads as active.
    activeSource: { type: String, default: null },
});

// show-source: a section heading switches the Following feed to that source.
const emit = defineEmits(['close', 'show-source']);

const closeButton = ref(null);

const onKeydown = (e) => {
    if (e.key === 'Escape' && props.open) emit('close');
};

// The page behind the drawer must not scroll along with it.
watch(() => props.open, async (open) => {
    document.body.classList.toggle('overflow-hidden', open);
    if (open) {
        await nextTick();
        closeButton.value?.focus();
    }
});

const showSource = (source) => {
    emit('show-source', source);
    emit('close');
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<template>
    <div v-if="open" class="side-panel-backdrop" aria-hidden="true" @click="emit('close')" />

    <aside id="side-panel" class="side-panel" :class="{ 'is-open': open }" :aria-label="$t('home.side_panel.label')">
        <div class="-mb-3 flex items-center justify-end xl:hidden">
            <button
                ref="closeButton"
                type="button"
                class="language-chip-remove"
                :aria-label="$t('home.side_panel.close')"
                @click="emit('close')"
            >✕</button>
        </div>

        <section v-for="kind in ['groups', 'people']" :key="kind" class="side-panel-section">
            <div class="feed-tabs-row side-panel-heading">
                <button
                    type="button"
                    class="feed-tab"
                    :class="{ 'is-active': activeSource === kind }"
                    :title="$t(`home.side_panel.show_posts.${kind}`)"
                    @click="showSource(kind)"
                >{{ $t(`home.sources.${kind}`) }}</button>
            </div>

            <ul v-if="panel[kind].length" class="flex flex-col">
                <li v-for="item in panel[kind]" :key="item.id">
                    <Link
                        v-if="kind === 'groups'"
                        :href="route('groups.show', item.slug)"
                        class="side-panel-row"
                        @click="emit('close')"
                    >
                        <span class="avatar-sm rounded-md" aria-hidden="true">{{ item.name.charAt(0).toUpperCase() }}</span>
                        <span class="min-w-0 truncate" dir="auto">{{ item.name }}</span>
                        <span v-if="item.is_private" :title="$t('groups.show.private')">🔒</span>
                        <span
                            v-if="item.new_posts_count"
                            class="side-panel-new"
                            :title="$tChoice('home.side_panel.new_posts', item.new_posts_count, { num: item.new_posts_count })"
                        >{{ item.new_posts_count }}</span>
                    </Link>
                    <Link
                        v-else
                        :href="route('users.show', item.username)"
                        class="side-panel-row"
                        @click="emit('close')"
                    >
                        <UserAvatar :url="item.avatar_url" :username="item.username" class="avatar-sm" />
                        <span class="min-w-0 truncate" dir="auto">{{ item.username }}</span>
                    </Link>
                </li>
            </ul>
            <p v-else class="px-2 text-xs text-muted">{{ $t(`home.side_panel.empty.${kind}`) }}</p>
        </section>

        <Link :href="`${route('settings.edit')}#subscriptions`" class="side-panel-manage">
            {{ $t('home.side_panel.manage') }}
        </Link>
    </aside>
</template>
