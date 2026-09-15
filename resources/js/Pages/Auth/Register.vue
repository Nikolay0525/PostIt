<script setup>
import { useForm } from '@inertiajs/vue3';

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
            <div>
                <label>Name</label>
                <input type="text" id="name" v-model="form.name"/>
                <small class="text-danger" v-if="form.errors.name">{{ form.errors.name }}</small>
            </div>
            <div>
                <label>Email</label>
                <input type="email" id="email" v-model="form.email"/>
                <small class="text-danger" v-if="form.errors.email">{{ form.errors.email }}</small>
            </div>
            <div>
                <label>Password</label>
                <input type="password" id="password" v-model="form.password"/>
                <small class="text-danger" v-if="form.errors.password">{{ form.errors.password }}</small>
            </div>
            <div>
                <label>Confirm Password</label>
                <input type="password" id="password_confirmation" v-model="form.password_confirmation"/>
            </div>
            <button type="submit">Register</button>
        </form>
    </div>
</template>
