<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submitForm = () => {
    form.post('/forgot-password');
};
</script>

<template>
    <Head title=" | Forgot password" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">Reset your password</h1>
            <p class="auth-subtitle">Enter your email and we'll send you a link to reset your password.</p>

            <p v-if="status" class="mt-4 rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-brand-700">
                {{ status }}
            </p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput name="Email" type="email" v-model="form.email" :message="form.errors.email" />

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Sending link…' : 'Send reset link' }}
                </button>
            </form>

            <p class="auth-footer">
                Remembered your password?
                <Link :href="route('login')" class="auth-link">Log in</Link>
            </p>
        </div>
    </div>
</template>