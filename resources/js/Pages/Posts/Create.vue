<script setup>
import { nextTick, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    group: Object,
});

const form = useForm({
    group_id: props.group.id,
    title: '',
    article: '',
});

const articleInput = ref(null);

// Wraps the current textarea selection in Markdown syntax (or inserts empty markers with the
// cursor placed between them when nothing is selected) — a plain textarea, not a rich-text
// editor, so "bold"/"italic" means inserting **/* around the text rather than styling it live.
const wrapSelection = (marker) => {
    const el = articleInput.value;
    if (!el) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const selected = form.article.slice(start, end);

    form.article = form.article.slice(0, start) + marker + selected + marker + form.article.slice(end);

    nextTick(() => {
        el.focus();
        el.setSelectionRange(start + marker.length, start + marker.length + selected.length);
    });
};

const submit = () => {
    form.post(route('posts.store'));
};
</script>

<template>
    <Head :title="` | New post`" />

    <section class="feed">
        <Link :href="route('groups.show', group.slug)" class="back-link" dir="auto">
            <span class="back-arrow" aria-hidden="true">←</span> Back to {{ group.name }}
        </Link>

        <form class="post-form" @submit.prevent="submit">
            <h1 class="feed-title">New post in {{ group.name }}</h1>

            <TextInput name="Title (optional)" v-model="form.title" :message="form.errors.title" />

            <div>
                <label class="field-label">Article</label>

                <div class="markdown-toolbar mb-2">
                    <button
                        type="button"
                        class="markdown-toolbar-btn font-bold"
                        aria-label="Bold"
                        title="Bold"
                        @click="wrapSelection('**')"
                    >B</button>
                    <button
                        type="button"
                        class="markdown-toolbar-btn italic"
                        aria-label="Italic"
                        title="Italic"
                        @click="wrapSelection('*')"
                    >I</button>
                </div>

                <textarea
                    ref="articleInput"
                    v-model="form.article"
                    class="field-input"
                    :class="{ 'has-error': form.errors.article }"
                    rows="12"
                    dir="auto"
                    placeholder="Write your post. Use **bold** and *italic*, and leave a blank line between paragraphs."
                ></textarea>
                <p v-if="form.errors.article" class="field-error">{{ form.errors.article }}</p>
            </div>

            <button type="submit" class="btn-primary self-start" :disabled="form.processing">
                {{ form.processing ? 'Posting…' : 'Post' }}
            </button>
        </form>
    </section>
</template>
