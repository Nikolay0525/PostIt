<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const form = useForm({
    email: '',
    password: '',
    remember: null,
});

const submitForm = () => {
    form.post('/login');
};
</script>

<template>
    <Head title=" | Login" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">Log in to your account</h1>
            <p class="auth-subtitle">Welcome back — pick up where you left off.</p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput name="Email" type="email" v-model="form.email" :message="form.errors.email" />
                <TextInput name="Password" type="password" v-model="form.password" :message="form.errors.password" />

                <label class="checkbox-row">
                    <input type="checkbox" class="checkbox-input" v-model="form.remember" />
                    Remember me
                </label>

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Logging in…' : 'Log in' }}
                </button>
            </form>

            <p class="auth-footer">
                New to PostIt?
                <Link :href="route('register')" class="auth-link">Create an account</Link>
            </p>

            <p class="auth-footer">
                Forgot your password?
                <Link :href="route('forgot_password')" class="auth-link">Reset it</Link>
            </p>
        </div>
    </div>
</template>