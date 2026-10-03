<script setup>
import InboxMenu from '@/Pages/Components/InboxMenu.vue';
import NotificationsMenu from '@/Pages/Components/NotificationsMenu.vue';
import SearchBox from '@/Pages/Components/SearchBox.vue';
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

                <SearchBox class="order-last w-full sm:order-none sm:w-auto sm:flex-1" />

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