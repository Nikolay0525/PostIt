<script setup>
import Dropdown from '@/Pages/Components/Dropdown.vue';

// Dummy data until notifications exist on the backend.
const notifications = [
    { id: 1, text: 'Marta Kowalska replied to your comment', time: '5m ago', unread: true },
    { id: 2, text: 'Your post in Laravel Developers reached 100 votes', time: '3h ago', unread: true },
    { id: 3, text: 'Jan Nowak started following you', time: '1d ago', unread: false },
];

const unreadCount = notifications.filter((n) => n.unread).length;
</script>

<template>
    <Dropdown label="Notifications">
        <template #trigger>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M5 8a5 5 0 0 1 10 0c0 4 1.5 5.5 1.5 5.5h-13S5 12 5 8Z" stroke-linejoin="round" />
                <path d="M8.5 16.5a1.6 1.6 0 0 0 3 0" stroke-linecap="round" />
            </svg>
            <span v-if="unreadCount" class="icon-badge">{{ unreadCount }}</span>
        </template>

        <p class="menu-heading">Notifications</p>

        <ul class="menu-list">
            <li v-for="n in notifications" :key="n.id" class="menu-row">
                <span v-if="n.unread" class="unread-dot" aria-label="Unread"></span>
                <span v-else class="unread-dot-space" aria-hidden="true"></span>
                <p class="min-w-0 flex-1 text-sm text-ink" dir="auto">{{ n.text }}</p>
                <span class="shrink-0 text-xs text-muted">{{ n.time }}</span>
            </li>
        </ul>
    </Dropdown>
</template>
