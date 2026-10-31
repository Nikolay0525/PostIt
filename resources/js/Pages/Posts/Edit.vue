<script setup>
import { useForm } from '@inertiajs/vue3';
import MarkdownField from '@/Pages/Components/MarkdownField.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    post: Object,
});

const form = useForm({
    title: props.post.title ?? '',
    article: props.post.article,
});

const submit = () => {
    form.put(route('posts.update', [props.post.group.slug, props.post.slug]));
};
</script>

<template>
    <Head :title="` | ${$t('posts.edit.title')}`" />

    <section class="feed">
        <PageHeader :title="$t('posts.edit.title')" />

        <form class="post-form" @submit.prevent="submit">
            <TextInput :name="$t('posts.editor.title_label')" v-model="form.title" :message="form.errors.title" />

            <MarkdownField v-model="form.article" :message="form.errors.article" />

            <div class="wizard-actions">
                <Link :href="route('posts.show', [post.group.slug, post.slug])" class="btn-secondary">
                    <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back') }}
                </Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? $t('posts.edit.saving') : $t('posts.edit.save') }}
                </button>
            </div>
        </form>
    </section>
</template>
