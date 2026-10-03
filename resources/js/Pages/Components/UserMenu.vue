<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Pages/Components/Dropdown.vue';
import UserAvatar from '@/Pages/Components/UserAvatar.vue';

const page = usePage();
const username = computed(() => page.props.auth.user.username);
</script>

<template>
    <Dropdown :label="$t('nav.account_menu')" trigger-class="avatar-btn">
        <template #trigger>
            <UserAvatar :url="page.props.auth.user.avatar_url" :username="username" class="flex h-full w-full items-center justify-center" />
        </template>

        <template #default="{ close }">
            <p class="menu-heading" dir="auto">{{ username }}</p>

            <Link :href="route('users.show', username)" class="menu-item" @click="close">{{ $t('nav.profile') }}</Link>
            <Link :href="route('groups.create')" class="menu-item" @click="close">{{ $t('nav.create_group') }}</Link>
            <Link :href="route('settings.edit')" class="menu-item" @click="close">{{ $t('nav.settings') }}</Link>
            <Link :href="route('logout')" method="post" as="button" type="button" class="menu-item" @click="close">
                {{ $t('nav.log_out') }}
            </Link>
        </template>
    </Dropdown>
</template>
