<script setup>
import { nextTick, ref } from 'vue';

defineProps({
    message: String,
});

// The post's text: a textarea with a Bold / Italic toolbar, on both the create and the edit page.
const article = defineModel({ type: String, required: true });

const input = ref(null);

// Wraps the current textarea selection in Markdown syntax (or inserts empty markers with the
// cursor placed between them when nothing is selected) — a plain textarea, not a rich-text
// editor, so "bold"/"italic" means inserting **/* around the text rather than styling it live.
const wrapSelection = (marker) => {
    const el = input.value;
    if (!el) return;

    const start = el.selectionStart;
    const end = el.selectionEnd;
    const selected = article.value.slice(start, end);

    article.value = article.value.slice(0, start) + marker + selected + marker + article.value.slice(end);

    nextTick(() => {
        el.focus();
        el.setSelectionRange(start + marker.length, start + marker.length + selected.length);
    });
};
</script>

<template>
    <div>
        <label for="post-article" class="field-label">{{ $t('posts.editor.article') }}</label>

        <div class="markdown-toolbar mb-2">
            <button
                type="button"
                class="markdown-toolbar-btn font-bold"
                :aria-label="$t('posts.editor.bold')"
                :title="$t('posts.editor.bold')"
                @click="wrapSelection('**')"
            >B</button>
            <button
                type="button"
                class="markdown-toolbar-btn italic"
                :aria-label="$t('posts.editor.italic')"
                :title="$t('posts.editor.italic')"
                @click="wrapSelection('*')"
            >I</button>
        </div>

        <textarea
            id="post-article"
            ref="input"
            v-model="article"
            class="field-input"
            :class="{ 'has-error': message }"
            rows="12"
            dir="auto"
            :placeholder="$t('posts.editor.placeholder')"
        ></textarea>
        <p v-if="message" class="field-error">{{ message }}</p>
    </div>
</template>
