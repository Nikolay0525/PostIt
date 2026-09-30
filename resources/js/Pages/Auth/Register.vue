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
    <Head :title="` | ${$t('auth.register.title')}`" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">{{ $t('auth.register.heading') }}</h1>
            <p class="auth-subtitle">{{ $t('auth.register.subtitle') }}</p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput :name="$t('auth.fields.name')" type="text" v-model="form.name" :message="form.errors.name" />
                <TextInput :name="$t('auth.fields.date_of_birth')" type="date" v-model="form.date_of_birth" :message="form.errors.date_of_birth" />
                <TextInput :name="$t('auth.fields.email')" type="email" v-model="form.email" :message="form.errors.email" />
                <TextInput :name="$t('auth.fields.password')" type="password" v-model="form.password" :message="form.errors.password" />
                <TextInput :name="$t('auth.fields.password_confirmation')" type="password" v-model="form.password_confirmation" :message="form.errors.password_confirmation" />

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? $t('auth.register.submitting') : $t('auth.register.submit') }}
                </button>
            </form>

            <p class="auth-footer">
                {{ $t('auth.register.has_account') }}
                <Link :href="route('login')" class="auth-link">{{ $t('auth.register.login_link') }}</Link>
            </p>
        </div>
    </div>
</template>