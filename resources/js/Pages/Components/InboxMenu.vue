<script setup>
import { ref, computed } from 'vue';
import Dropdown from '@/Pages/Components/Dropdown.vue';

// Dummy data until messaging exists on the backend.
const messages = {
    received: [
        { id: 1, person: 'Marta Kowalska', text: 'Thanks for the tip about queues!', time: '10m ago', unread: true },
        { id: 2, person: 'أحمد الفارسي', text: 'هل يمكنك مشاركة الرابط مرة أخرى؟', time: '1h ago', unread: true },
        { id: 3, person: 'Jan Nowak', text: 'See you at the meetup.', time: '1d ago', unread: false },
    ],
    sent: [
        { id: 4, person: 'Oliver Grant', text: 'I use a plain editor with a few plugins.', time: '2h ago', unread: false },
        { id: 5, person: 'דנה כהן', text: 'תודה על ההמלצה!', time: '2d ago', unread: false },
    ],
};

const tab = ref('received');
const list = computed(() => messages[tab.value]);
const unreadCount = messages.received.filter((m) => m.unread).length;
</script>

<template>
    <Dropdown label="Inbox">
        <template #trigger>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <rect x="2.5" y="4.5" width="15" height="11" rx="2" />
                <path d="m3 6 7 5 7-5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span v-if="unreadCount" class="icon-badge">{{ unreadCount }}</span>
        </template>

        <div class="menu-tabs" role="tablist">
            <button
                type="button"
                role="tab"
                class="menu-tab"
                :class="{ 'menu-tab-active': tab === 'received' }"
                @click="tab = 'received'"
            >Received</button>
            <button
                type="button"
                role="tab"
                class="menu-tab"
                :class="{ 'menu-tab-active': tab === 'sent' }"
                @click="tab = 'sent'"
            >Sent</button>
        </div>

        <ul class="menu-list">
            <li v-for="m in list" :key="m.id" class="menu-row">
                <span v-if="m.unread" class="unread-dot" aria-label="Unread"></span>
                <span v-else class="unread-dot-space" aria-hidden="true"></span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-ink" dir="auto">{{ m.person }}</p>
                    <p class="truncate text-sm text-muted" dir="auto">{{ m.text }}</p>
                </div>
                <span class="shrink-0 text-xs text-muted">{{ m.time }}</span>
            </li>
        </ul>
    </Dropdown>
</template>
