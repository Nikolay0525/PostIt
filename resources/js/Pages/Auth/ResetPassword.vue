<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submitForm = () => {
    form.post('/reset-password');
};
</script>

<template>
    <Head title=" | Reset password" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">Choose a new password</h1>
            <p class="auth-subtitle">Make it something you haven't used here before.</p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput
                    name="New password"
                    type="password"
                    v-model="form.password"
                    :message="form.errors.password"
                />
                <TextInput
                    name="Confirm new password"
                    type="password"
                    v-model="form.password_confirmation"
                    :message="form.errors.password_confirmation"
                />

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Changing password…' : 'Change password' }}
                </button>
            </form>
        </div>
    </div>
</template>