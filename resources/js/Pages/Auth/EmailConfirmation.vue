<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({});

const cooldown = ref(60);
let timer = null;

const formattedCooldown = computed(() => {
    const m = String(Math.floor(cooldown.value / 60)).padStart(2, '0');
    const s = String(cooldown.value % 60).padStart(2, '0');
    return `${m}:${s}`;
});

const startCooldown = () => {
    cooldown.value = 60;
    clearInterval(timer);
    timer = setInterval(() => {
        cooldown.value--;
        if (cooldown.value <= 0) clearInterval(timer);
    }, 1000);
};
startCooldown();

onUnmounted(() => clearInterval(timer));

const resendEmail = () => {
    if (cooldown.value > 0) return;
    form.post('/email/verification-notification', {
        onSuccess: () => startCooldown(),
    });
};
</script>

<template>
    <Head title=" | Verify email" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">Verify your email</h1>
            <p class="auth-subtitle">
                We've sent a verification link to your email address. Click the link to activate your account.
            </p>

            <p v-if="status === 'verification-link-sent'" class="mt-4 rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-brand-700">
                A new verification link has been sent to your email address.
            </p>

            <p class="auth-footer">
                <span v-if="cooldown > 0">
                    Resend available in <span class="font-medium text-danger">{{ formattedCooldown }}</span>
                </span>
                <span v-else>
                    Didn't get the email?
                    <button type="button" class="auth-link" @click="resendEmail">Resend verification link</button>
                </span>
            </p>
        </div>
    </div>
</template>