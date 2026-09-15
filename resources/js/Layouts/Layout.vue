<script setup>
</script>

<template>

    <div class="min-h-screen bg-gray-100 text-gray-900">
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div v-if="$page.props.auth.user" class="flex items-center gap-4">
                    <span class="font-semibold text-gray-900">Hello, {{ $page.props.auth.user.name }}!</span>
                    <Link :href="route('dashboard')" 
                    class="text-gray-600 transition hover:text-indigo-600"
                    :class="{ 'text-indigo-600' : $page.component === 'Dashboard'}">Dashboard</Link>
                    
                    <Link :href="route('logout')" method="post" as="button" type="button" class="text-gray-600 transition hover:text-indigo-600">Logout</Link>
                </div>
                <div v-else class="flex items-center gap-4">
                    <Link :href="route('login')" 
                    class="text-gray-600 transition hover:text-indigo-600"
                    :class="{ 'text-indigo-600' : $page.component === 'Auth/Login'}">Login</Link>
                    <Link :href="route('register')" 
                    class="text-gray-600 transition hover:text-indigo-600"
                    :class="{ 'text-indigo-600' : $page.component === 'Auth/Register'}">Register</Link>
                </div>
                <div class="flex items-center gap-6">
                    <Link :href="route('home')" 
                    class="font-semibold text-gray-900 transition hover:text-indigo-600"
                    :class="{ 'text-indigo-600' : $page.component === 'Home'}">Home</Link>
                    <Link :href="route('about')" 
                    class="text-gray-600 transition hover:text-indigo-600"
                    :class="{ 'text-indigo-600' : $page.component === 'About'}">About</Link>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>