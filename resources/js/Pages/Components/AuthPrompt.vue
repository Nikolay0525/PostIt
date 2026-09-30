<script setup>
import TransSlots from '@/Pages/Components/TransSlots.vue';

// "Log in or sign up to …" for guests; `action` names the line in lang/*/prompts.php.
defineProps({
    action: { type: String, required: true },
});
</script>

<template>
    <TransSlots :text="$t(`prompts.${action}`)">
        <template #login>
            <Link :href="route('login')" class="auth-link">{{ $t('prompts.log_in') }}</Link>
        </template>
        <template #register>
            <Link :href="route('register')" class="auth-link">{{ $t('prompts.sign_up') }}</Link>
        </template>
    </TransSlots>
</template>
