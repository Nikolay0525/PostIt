<template>
    <Head title = " | Dashboard"/>

    <h1 class="text-2xl font-bold">Welcome to the Dashboard</h1>
    <p class="mt-4">This is the content of the dashboard page.</p>
    <p class="mt-4">Hello, {{ $page.props.auth.user.name }}!</p>
</template>