<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitForm = () => {
    // Handle form submission logic here
    form.post('/register');
};
</script>

<template>

    <Head title="Register" />

    <h1 class="title">Register a new account</h1>

    <div>
        <form @submit.prevent="submitForm">
            <TextInput name="Name" type="text" v-model="form.name" :message="form.errors.name" />

            <TextInput name="Email" type="email" v-model="form.email" :message="form.errors.email" />

            <TextInput name="Password" type="password" v-model="form.password" :message="form.errors.password" />

            <TextInput name="Confirm Password" type="password" v-model="form.password_confirmation"
                :message="form.errors.password_confirmation" />
            <button type="submit">Register</button>
        </form>
    </div>
</template>
