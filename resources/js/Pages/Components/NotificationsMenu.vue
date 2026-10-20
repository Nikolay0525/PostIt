<script setup>
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import Dropdown from '@/Pages/Components/Dropdown.vue';
import { getJson, postJson } from '@/utils/http';
import { timeAgo } from '@/utils/format';

const page = usePage();

// The badge comes with every page; the list is fetched only when the dropdown opens.
const unread = ref(page.props.unread_notifications);
watch(() => page.props.unread_notifications, (count) => (unread.value = count));

const notifications = ref([]);
const state = ref('idle'); // idle | loading | ready | failed

async function load() {
    state.value = 'loading';
    try {
        const data = await getJson(route('notifications.index'));
        notifications.value = data.notifications;
        unread.value = data.unread;
        state.value = 'ready';
    } catch {
        state.value = 'failed';
    }
}

// A notification stores ids only; the sentence is built here, in the page language.
function text(n) {
    if (n.post_title === null) return trans('notifications.post_deleted');

    const key = `notifications.kinds.${n.type}`;
    const sentence = trans(key, { user: n.author ?? trans('notifications.someone'), post: n.post_title });

    return sentence === key ? trans('notifications.kinds.unknown') : sentence;
}

// Only a notification the user opens becomes read. One that leads nowhere (its post was
// deleted) is still marked read, and the user stays on the current page.
function open(n, close) {
    router.post(route('notifications.open', n.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (!n.read) unread.value = Math.max(0, unread.value - 1);
            n.read = true;
        },
    });

    if (n.has_link) close();
}

async function readAll() {
    await postJson(route('notifications.read_all'));
    notifications.value.forEach((n) => (n.read = true));
    unread.value = 0;
}
</script>

<template>
    <Dropdown :label="$t('nav.notifications')" @open="load">
        <template #trigger>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M5 8a5 5 0 0 1 10 0c0 4 1.5 5.5 1.5 5.5h-13S5 12 5 8Z" stroke-linejoin="round" />
                <path d="M8.5 16.5a1.6 1.6 0 0 0 3 0" stroke-linecap="round" />
            </svg>
            <span v-if="unread" class="icon-badge">{{ unread }}</span>
        </template>

        <template #default="{ close }">
            <div class="menu-heading menu-heading-row">
                <span>{{ $t('nav.notifications') }}</span>
                <button v-if="unread" type="button" class="menu-heading-action" @click="readAll">
                    {{ $t('notifications.read_all') }}
                </button>
            </div>

            <p v-if="state === 'loading' && !notifications.length" class="menu-note">{{ $t('notifications.loading') }}</p>
            <p v-else-if="state === 'failed'" class="menu-note">{{ $t('notifications.load_failed') }}</p>
            <p v-else-if="state === 'ready' && !notifications.length" class="menu-note">{{ $t('notifications.empty') }}</p>

            <ul v-if="notifications.length" class="menu-list">
                <li v-for="n in notifications" :key="n.id">
                    <button
                        type="button"
                        class="notification-row"
                        :class="{ 'notification-row-important': n.important }"
                        @click="open(n, close)"
                    >
                        <span v-if="!n.read" class="unread-dot" :aria-label="$t('nav.unread')"></span>
                        <span v-else class="unread-dot-space" aria-hidden="true"></span>
                        <span class="min-w-0 flex-1">
                            <span v-if="n.important" class="notification-important-label">{{ $t('notifications.important') }}</span>
                            <span class="block text-sm text-ink" :class="{ 'text-muted': n.read }" dir="auto">{{ text(n) }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-muted">{{ timeAgo(n.created_at) }}</span>
                    </button>
                </li>
            </ul>
        </template>
    </Dropdown>
</template>
