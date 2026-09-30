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
    <Head :title="` | ${$t('auth.login.title')}`" />

    <div class="auth-shell">
        <div class="auth-card">
            <p class="brand-mark">Post<span class="brand-mark-accent">It.</span></p>

            <h1 class="auth-title mt-6">{{ $t('auth.login.heading') }}</h1>
            <p class="auth-subtitle">{{ $t('auth.login.subtitle') }}</p>

            <form class="auth-form" @submit.prevent="submitForm">
                <TextInput :name="$t('auth.fields.email')" type="email" v-model="form.email" :message="form.errors.email" />
                <TextInput :name="$t('auth.fields.password')" type="password" v-model="form.password" :message="form.errors.password" />

                <label class="checkbox-row">
                    <input type="checkbox" class="checkbox-input" v-model="form.remember" />
                    {{ $t('auth.fields.remember') }}
                </label>

                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? $t('auth.login.submitting') : $t('auth.login.submit') }}
                </button>
            </form>

            <p class="auth-footer">
                {{ $t('auth.login.no_account') }}
                <Link :href="route('register')" class="auth-link">{{ $t('auth.login.register_link') }}</Link>
            </p>

            <p class="auth-footer">
                {{ $t('auth.login.forgot_password') }}
                <Link :href="route('forgot_password')" class="auth-link">{{ $t('auth.login.reset_link') }}</Link>
            </p>
        </div>
    </div>
</template>