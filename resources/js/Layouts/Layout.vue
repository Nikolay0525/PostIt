<script setup>
</script>

<template>

    <div class="min-h-screen bg-gray-100 text-gray-900">
        <header class="border-b border-gray-200 bg-white shadow-sm">
            <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-6">
                    <Link href="/" class="font-semibold text-gray-900 transition hover:text-indigo-600">Home</Link>
                    <Link href="/about" class="text-gray-600 transition hover:text-indigo-600">About</Link>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>