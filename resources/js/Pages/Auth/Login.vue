<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: ''
});

const submitForm = () => {
    // Handle form submission logic here
    form.post('/login');
};
</script>

<template>
    <Head title = " | Login"/>
    <h1>Login into your account</h1>
    <div>
        <form @submit.prevent="submitForm">
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" v-model="form.email" required />
            </div>
            <div>
                <label for="password">Password</label>
                <input type="password" id="password" v-model="form.password" required />
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</template>
