<script setup>
import Dropdown from '@/Pages/Components/Dropdown.vue';

// The "···" menu of a post or a comment: what can be done with it (edit it, report it), as items
// in the default slot, which gets `close`. The caller shows it only when it has an item for this viewer.
</script>

<template>
    <Dropdown class="content-menu" :label="$t('common.more_actions')" trigger-class="content-menu-btn" anchored>
        <template #trigger>
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3m5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3" />
            </svg>
        </template>

        <template #default="{ close }">
            <slot :close="close" />
        </template>
    </Dropdown>
</template>
