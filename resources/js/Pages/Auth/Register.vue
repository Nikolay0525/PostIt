<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const form = useForm({
    name: '',
    date_of_birth: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitForm = () => {
    form.post('/register');
};
</script>

<template>
    <Head title=" | Register" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">Create your account</h1>
            <p class="auth-subtitle">Join communities built around long-form writing and open discussion.</p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput name="Name" type="text" v-model="form.name" :message="form.errors.name" />
                <TextInput name="Date of Birth" type="date" v-model="form.date_of_birth" :message="form.errors.date_of_birth" />
                <TextInput name="Email" type="email" v-model="form.email" :message="form.errors.email" />
                <TextInput name="Password" type="password" v-model="form.password" :message="form.errors.password" />
                <TextInput name="Confirm Password" type="password" v-model="form.password_confirmation" :message="form.errors.password_confirmation" />

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? 'Creating account…' : 'Create account' }}
                </button>
            </form>

            <p class="auth-footer">
                Already have an account?
                <Link :href="route('login')" class="auth-link">Log in</Link>
            </p>
        </div>
    </div>
</template>