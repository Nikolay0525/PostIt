<script setup>
import { computed, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import InboxMenu from '@/Pages/Components/InboxMenu.vue';
import NotificationsMenu from '@/Pages/Components/NotificationsMenu.vue';
import SearchBox from '@/Pages/Components/SearchBox.vue';
import SidePanel from '@/Pages/Components/SidePanel.vue';
import SidePanelButton from '@/Pages/Components/SidePanelButton.vue';
import ThemeToggle from '@/Pages/Components/ThemeToggle.vue';
import TourPrompt from '@/Pages/Components/TourPrompt.vue';
import UserMenu from '@/Pages/Components/UserMenu.vue';
import { panel, panelOpen, takePanel } from '@/utils/sidePanel';

const page = usePage();

// The side panel lives here, not in a page: the layout stays mounted from one page to the next,
// so the panel and its counters stay in place. `side_panel` is shared with every page (null for a
// guest, who gets no panel); the Auth/* pages are forms, where it would only distract.
watch(() => page.props.side_panel, (next) => {
    if (next !== undefined) takePanel(next);
}, { immediate: true });

const showPanel = computed(() => panel.value !== null && !page.component.startsWith('Auth/'));
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink">
        <header class="border-b border-line bg-surface">
            <nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-3">
                        <SidePanelButton v-if="showPanel" />
                        <Link :href="route('home')" class="brand-mark" data-tour="navigation.home">
                            Post<span class="brand-mark-accent">It.</span>
                        </Link>
                    </div>

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

        <!-- With the panel the page may use the whole width: the grid caps its own columns. -->
        <main class="mx-auto px-4 py-8 sm:px-6 lg:px-8" :class="showPanel ? 'max-w-none' : 'max-w-7xl'">
            <!-- The page always in the middle column, the panel in the left one, a page's
                 .page-aside in the right one (see .home-layout). -->
            <div v-if="showPanel" class="home-layout">
                <SidePanel :panel="panel" :open="panelOpen" @close="panelOpen = false" />
                <div class="home-layout-page">
                    <slot />
                </div>
            </div>
            <slot v-else />
        </main>

        <!-- Offers the current page's tours (see usePageTours); the navbar's on any page. -->
        <TourPrompt />
    </div>
</template>