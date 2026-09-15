<script setup>
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const form = useForm({
    email: '',
    password: '',
    remember: null
});

const submitForm = () => {
    // Handle form submission logic here
    form.post('/login');
};
</script>

<template>

    <Head title=" | Login" />
    <h1>Login into your account</h1>
    <div>
        <form @submit.prevent="submitForm">
            <TextInput name="Email" type="email" v-model="form.email" :message="form.errors.email" />
            <TextInput name="Password" type="password" v-model="form.password" :message="form.errors.password" />
            
            <div class ="flex items-center mt-4 gap-2">
                <label>
                    <input type="checkbox" v-model="form.remember" />
                    Remember Me
                </label>
            </div>
            
            <button type="submit">Login</button>
        </form>
    </div>
</template>
