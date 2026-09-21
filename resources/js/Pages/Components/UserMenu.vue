<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Pages/Components/Dropdown.vue';

// Served from public/images. Kept as a variable so the build doesn't require the file to exist yet.
const avatarUrl = '/images/default-avatar.png';

const page = usePage();
const name = computed(() => page.props.auth.user.name);
const initial = computed(() => name.value.charAt(0).toUpperCase());
</script>

<template>
    <Dropdown label="Account menu" trigger-class="avatar-btn">
        <template #trigger>
            <!-- Initial shows until the default picture file exists, then the image covers it. -->
            <span aria-hidden="true">{{ initial }}</span>
            <img
                :src="avatarUrl"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
                @error="$event.target.style.display = 'none'"
            />
        </template>

        <template #default="{ close }">
            <p class="menu-heading" dir="auto">{{ name }}</p>

            <!-- No settings page yet. -->
            <Link href="#" class="menu-item" @click="close">Settings</Link>
            <Link :href="route('logout')" method="post" as="button" type="button" class="menu-item" @click="close">
                Log out
            </Link>
        </template>
    </Dropdown>
</template>
