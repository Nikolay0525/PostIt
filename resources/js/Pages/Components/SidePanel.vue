<script setup>
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';
import { postJson } from '@/utils/http';
import { takePanel } from '@/utils/sidePanel';

// The groups and people the user attached in Settings, drawn by the layout on every page but
// Auth/*. On a wide screen it is always there, left of the page; on a narrow one it stays
// hidden until the navbar's panel button opens it as a drawer — there is no hover on a touch screen, so it
// opens with a tap.
const props = defineProps({
    // { groups: [{ id, slug, name, is_private, new_posts_count }], people: [{ id, username, avatar_url, new_posts_count }] }.
    // In both, the most new posts first, then by name. A post by someone you follow in a group
    // you're in counts in both, and reading it once takes it off both.
    panel: { type: Object, required: true },
    open: Boolean,
});

const emit = defineEmits(['close']);

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

// "Read all" (✓ next to a counter): every new post of that group or person counts as seen. The
// answer is the fresh panel, as for a "seen" batch. If it fails the counter simply stays.
const reading = reactive(new Set());
const readAll = async (kind, item) => {
    const key = `${kind}:${item.id}`;
    if (reading.has(key)) return;

    reading.add(key);
    try {
        const data = await postJson(`/${kind === 'groups' ? 'groups' : 'users'}/${item.id}/seen`);
        if (data?.side_panel) takePanel(data.side_panel);
    } catch (error) {
        console.error('Read all failed:', error);
    } finally {
        reading.delete(key);
    }
};

const itemName = (kind, item) => (kind === 'groups' ? item.name : item.username);

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
            <!-- Only a label: which posts the feed shows is switched in the feed itself. -->
            <div class="feed-tabs-row side-panel-heading">
                <h2 class="side-panel-title">{{ $t(`home.sources.${kind}`) }}</h2>
            </div>

            <ul v-if="panel[kind].length" class="flex flex-col">
                <li v-for="item in panel[kind]" :key="item.id" class="side-panel-item group">
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
                        <span
                            v-if="item.new_posts_count"
                            class="side-panel-new"
                            :title="$tChoice('home.side_panel.new_posts', item.new_posts_count, { num: item.new_posts_count })"
                        >{{ item.new_posts_count }}</span>
                    </Link>
                    <!-- Beside the link, not in it: a button inside a link would open the page too. -->
                    <button
                        v-if="item.new_posts_count"
                        type="button"
                        class="side-panel-read"
                        :aria-label="$t('home.side_panel.read_all', { name: itemName(kind, item) })"
                        :title="$t('home.side_panel.read_all', { name: itemName(kind, item) })"
                        :disabled="reading.has(`${kind}:${item.id}`)"
                        @click="readAll(kind, item)"
                    >✓</button>
                </li>
            </ul>
            <p v-else class="px-2 text-xs text-muted">{{ $t(`home.side_panel.empty.${kind}`) }}</p>
        </section>

        <Link :href="`${route('settings.edit')}#subscriptions`" class="side-panel-manage">
            {{ $t('home.side_panel.manage') }}
        </Link>
    </aside>
</template>
