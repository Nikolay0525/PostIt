<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InboxMenu from '@/Pages/Components/InboxMenu.vue';
import NotificationsMenu from '@/Pages/Components/NotificationsMenu.vue';
import SearchBox from '@/Pages/Components/SearchBox.vue';
import SidePanel from '@/Pages/Components/SidePanel.vue';
import ThemeToggle from '@/Pages/Components/ThemeToggle.vue';
import TourPrompt from '@/Pages/Components/TourPrompt.vue';
import UserMenu from '@/Pages/Components/UserMenu.vue';
import { panel, panelOpen, takePanel } from '@/utils/sidePanel';

const page = usePage();

// The side panel lives here, not in a page: the layout stays mounted from one page to the next,
// so going from the home page to a group (both pass `side_panel`) keeps the panel and its counters
// in place. A page without the prop shows no panel; null (a guest) shows none either.
watch(() => page.props.side_panel, (next) => {
    if (next !== undefined) takePanel(next);
}, { immediate: true });

const showPanel = computed(() => page.props.side_panel !== undefined && panel.value !== null);
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink">
        <header class="border-b border-line bg-surface">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <Link :href="route('home')" class="brand-mark" data-tour="navigation.home">
                        Post<span class="brand-mark-accent">It.</span>
                    </Link>

                    <div class="hidden items-center gap-6 sm:flex">
                        <Link
                            :href="route('home')"
                            class="nav-link"
                            :class="{ 'nav-link-active': $page.component === 'Feed/Index' }"
                        >{{ $t('nav.home') }}</Link>
                    </div>
                </div>

                <SearchBox class="order-last w-full sm:order-none sm:w-auto sm:flex-1" data-tour="navigation.search" />

                <div class="flex items-center gap-4">
                    <ThemeToggle />
                    <template v-if="$page.props.auth.user">
                        <!-- One tour step highlights both, so they share a wrapper. -->
                        <div class="flex items-center gap-4" data-tour="navigation.inbox">
                            <InboxMenu />
                            <NotificationsMenu />
                        </div>
                        <UserMenu data-tour="navigation.account" />
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
            <!-- The page always in the middle column, the panel in the left one (see .home-layout). -->
            <div v-if="showPanel" class="home-layout">
                <SidePanel :panel="panel" :open="panelOpen" @close="panelOpen = false" />
                <div class="min-w-0 xl:col-start-2 xl:row-start-1">
                    <slot />
                </div>
            </div>
            <slot v-else />
        </main>

        <!-- Offers the current page's tours (see usePageTours); the navbar's on any page. -->
        <TourPrompt />
    </div>
</template>