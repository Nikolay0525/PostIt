<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    post: Object,
});

const page = usePage();
const initial = computed(() => props.post.author.charAt(0).toUpperCase());

// Guests can read everything but any interaction asks them to log in.
const showLoginPrompt = ref(false);

const interact = () => {
    if (!page.props.auth.user) {
        showLoginPrompt.value = true;
        return;
    }
    // Real vote / comment handling comes with the backend.
};
</script>

<template>
    <article class="post-card">
        <header class="post-meta">
            <span class="avatar" aria-hidden="true">{{ initial }}</span>

            <!-- dir="auto" lets each piece of user text align by its own language -->
            <p class="post-meta-text">
                <a href="#" class="post-group" dir="auto">{{ post.group }}</a>
                <span aria-hidden="true"> · </span>
                <span>{{ post.time }}</span>
                <br />
                <a href="#" class="post-author" dir="auto">{{ post.author }}</a>
            </p>
        </header>

        <h2 class="post-title" dir="auto">{{ post.title }}</h2>
        <p class="post-body" dir="auto">{{ post.body }}</p>

        <footer class="post-footer">
            <button type="button" class="post-stat post-action" @click="interact">▲ {{ post.votes }}</button>
            <button type="button" class="post-stat post-action" @click="interact">💬 {{ post.comments }}</button>
        </footer>

        <p v-if="showLoginPrompt" class="post-login-prompt">
            <Link :href="route('login')" class="auth-link">Log in</Link>
            or
            <Link :href="route('register')" class="auth-link">sign up</Link>
            to vote and comment.
        </p>
    </article>
</template>
