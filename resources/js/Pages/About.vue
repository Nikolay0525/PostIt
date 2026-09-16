<script setup>
</script>

<template>

    <Head title = " | About"/>

    <h1 class="text-2xl font-bold">Welcome to the About Page </h1>
    <p class="mt-4">This is the content of the about page.</p>
    <p class="mt-4">Hello, {{ $page.props.auth.user.name }}!</p>
</template>