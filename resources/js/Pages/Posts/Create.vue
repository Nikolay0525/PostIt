<script setup>
import { useForm } from '@inertiajs/vue3';
import MarkdownField from '@/Pages/Components/MarkdownField.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import TextInput from '@/Pages/Components/TextInput.vue';

const props = defineProps({
    group: Object,
});

const form = useForm({
    group_id: props.group.id,
    title: '',
    article: '',
});

const submit = () => {
    form.post(route('posts.store'));
};
</script>

<template>
    <Head :title="` | ${$t('posts.create.title')}`" />

    <section class="feed">
        <PageHeader :title="$t('posts.create.heading', { group: group.name })" />

        <form class="post-form" @submit.prevent="submit">
            <TextInput :name="$t('posts.editor.title_label')" v-model="form.title" :message="form.errors.title" />

            <MarkdownField v-model="form.article" :message="form.errors.article" />

            <div class="wizard-actions">
                <Link :href="route('groups.show', group.slug)" class="btn-secondary">
                    <span class="back-arrow" aria-hidden="true">←</span> {{ $t('common.back') }}
                </Link>
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    {{ form.processing ? $t('posts.create.submitting') : $t('posts.create.submit') }}
                </button>
            </div>
        </form>
    </section>
</template>
