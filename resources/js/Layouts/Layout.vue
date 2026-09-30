<script setup>
import InboxMenu from '@/Pages/Components/InboxMenu.vue';
import NotificationsMenu from '@/Pages/Components/NotificationsMenu.vue';
import ThemeToggle from '@/Pages/Components/ThemeToggle.vue';
import UserMenu from '@/Pages/Components/UserMenu.vue';
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink">
        <header class="border-b border-line bg-surface">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <Link :href="route('home')" class="brand-mark">
                        Post<span class="brand-mark-accent">It.</span>
                    </Link>

                    <div class="hidden items-center gap-6 sm:flex">
                        <Link
                            :href="route('home')"
                            class="nav-link"
                            :class="{ 'nav-link-active': $page.component === 'Home' }"
                        >{{ $t('nav.home') }}</Link>
                    </div>
                </div>

                <form class="search-bar order-last w-full sm:order-none sm:w-auto sm:flex-1" role="search" @submit.prevent>
                    <svg class="search-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <circle cx="9" cy="9" r="5.5" />
                        <path d="m13.5 13.5 3.5 3.5" stroke-linecap="round" />
                    </svg>
                    <input
                        type="search"
                        dir="auto"
                        class="search-input"
                        :placeholder="$t('nav.search_placeholder')"
                        :aria-label="$t('nav.search')"
                    />
                </form>

                <div class="flex items-center gap-4">
                    <ThemeToggle />
                    <template v-if="$page.props.auth.user">
                        <InboxMenu />
                        <NotificationsMenu />
                        <UserMenu />
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="nav-link"
                            :class="{ 'nav-link-active': $page.component === 'Auth/Login' }"
                        >{{ $t('nav.log_in') }}</Link>
                        <Link :href="route('register')" class="btn-primary">{{ $t('nav.sign_up') }}</Link>
                    </template>
                </div>
            </nav>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>