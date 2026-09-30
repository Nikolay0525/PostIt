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
    <Head :title="` | ${$t('posts.create.title')}`" />

    <section class="feed">
        <Link :href="route('groups.show', group.slug)" class="back-link" dir="auto">
            <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back_to', { name: group.name }) }}
        </Link>

        <form class="post-form" @submit.prevent="submit">
            <h1 class="feed-title">{{ $t('posts.create.heading', { group: group.name }) }}</h1>

            <TextInput :name="$t('posts.create.title_label')" v-model="form.title" :message="form.errors.title" />

            <div>
                <label class="field-label">{{ $t('posts.create.article') }}</label>

                <div class="markdown-toolbar mb-2">
                    <button
                        type="button"
                        class="markdown-toolbar-btn font-bold"
                        :aria-label="$t('posts.create.bold')"
                        :title="$t('posts.create.bold')"
                        @click="wrapSelection('**')"
                    >B</button>
                    <button
                        type="button"
                        class="markdown-toolbar-btn italic"
                        :aria-label="$t('posts.create.italic')"
                        :title="$t('posts.create.italic')"
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
                    :placeholder="$t('posts.create.placeholder')"
                ></textarea>
                <p v-if="form.errors.article" class="field-error">{{ form.errors.article }}</p>
            </div>

            <button type="submit" class="btn-primary self-start" :disabled="form.processing">
                {{ form.processing ? $t('posts.create.submitting') : $t('posts.create.submit') }}
            </button>
        </form>
    </section>
</template>
