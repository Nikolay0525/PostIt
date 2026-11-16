<script setup>
import { ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { fullDate } from '@/utils/format';
import { postJson } from '@/utils/http';

// The author's way to appeal a verdict that took their content down: a button that opens a short text
// box, then, once it is sent, what became of it. Sent outside Inertia, like a vote, so the page they are
// reading stays as it is. The server says again whether they may (ModerationVerdictPolicy::appeal()).
const props = defineProps({
    // { id, can_appeal, appeal_until, appeal: { status, statement } | null }; see AuthorVerdictResource.
    verdict: { type: Object, required: true },
});

const MAX_LENGTH = 500;

const appeal = ref(props.verdict.appeal);
const writing = ref(false);
const statement = ref('');
const sending = ref(false);
const error = ref(null);

const send = async () => {
    const text = statement.value.trim();

    if (!text || sending.value) return;

    sending.value = true;
    error.value = null;

    try {
        appeal.value = await postJson(route('appeals.store', props.verdict.id), { statement: text });
        writing.value = false;
    } catch (failure) {
        console.error('Appeal failed:', failure);
        error.value = failure.message || trans('common.something_went_wrong');
    } finally {
        sending.value = false;
    }
};
</script>

<template>
    <div class="mt-2 flex flex-col gap-2 text-sm">
        <!-- Sent: what became of it. The removed content stays removed while it waits. -->
        <template v-if="appeal">
            <p class="font-medium">{{ $t(`moderation.appeal.status.${appeal.status}`) }}</p>
            <p class="text-muted" dir="auto">{{ $t('moderation.appeal.your_statement') }}: {{ appeal.statement }}</p>
        </template>

        <template v-else-if="verdict.can_appeal">
            <p class="text-muted">{{ $t('moderation.appeal.until', { date: fullDate(verdict.appeal_until) }) }}</p>

            <button v-if="!writing" type="button" class="btn-secondary self-start" @click="writing = true">
                {{ $t('moderation.appeal.open') }}
            </button>

            <form v-else class="comment-form" @submit.prevent="send">
                <textarea
                    v-model="statement"
                    class="field-input"
                    rows="3"
                    :maxlength="MAX_LENGTH"
                    dir="auto"
                    :placeholder="$t('moderation.appeal.placeholder')"
                    :aria-label="$t('moderation.appeal.placeholder')"
                    :disabled="sending"
                ></textarea>
                <span class="flex items-center justify-between gap-3">
                    <span class="text-xs text-muted">{{ statement.length }} / {{ MAX_LENGTH }}</span>
                    <span class="flex gap-2">
                        <button type="button" class="btn-secondary" :disabled="sending" @click="writing = false">{{ $t('common.cancel') }}</button>
                        <button type="submit" class="btn-primary" :disabled="sending || !statement.trim()">
                            {{ sending ? $t('moderation.appeal.sending') : $t('moderation.appeal.send') }}
                        </button>
                    </span>
                </span>
                <p v-if="error" class="vote-error" :title="error">⚠ {{ $t('moderation.appeal.failed') }}</p>
            </form>
        </template>

        <p v-else class="text-muted">{{ $t('moderation.appeal.closed', { date: fullDate(verdict.appeal_until) }) }}</p>
    </div>
</template>
