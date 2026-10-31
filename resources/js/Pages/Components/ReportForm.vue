<script setup>
import { computed, onMounted, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import { getJson, postJson } from '@/utils/http';

// A report on a post or a comment, under it: first what kind of wrong it is (the group's rules, the
// platform's, something else), and only for the group's rules which one. A feed shows a post of a
// group whose rules the reader may not know, so they are offered after that choice. The rules are
// fetched when the form opens, not sent with every card of a feed. `limit` is the reason's longest.
const props = defineProps({
    groupId: { type: String, required: true },
    targetType: { type: String, required: true },
    targetId: { type: String, required: true },
    limit: { type: Number, default: 100 },
});

const emit = defineEmits(['close']);

const GROUP = 'group';
const PLATFORM = 'platform';
const OTHER = 'other';

const rules = ref(null);
// GROUP, PLATFORM or OTHER; null until picked.
const kind = ref(null);
// With GROUP: the index of the rule; null until picked.
const ruleIndex = ref(null);
const text = ref('');
const sending = ref(false);
const sent = ref(false);
const error = ref(null);

onMounted(async () => {
    try {
        rules.value = (await getJson(`/groups/${props.groupId}/rules`)).rules;
    } catch (e) {
        console.error('Loading the rules failed:', e);
        error.value = e.message || trans('reports.failed');
    }
});

// The reason is asked for "something else" (required) and for the platform's rules (optional).
const asksReason = computed(() => kind.value === OTHER || kind.value === PLATFORM);
const canSend = computed(() => {
    if (sending.value) return false;
    if (kind.value === GROUP) return ruleIndex.value !== null;
    if (kind.value === OTHER) return !!text.value.trim();

    return kind.value === PLATFORM;
});

const send = async () => {
    if (!canSend.value) {
        return;
    }

    sending.value = true;
    error.value = null;

    try {
        await postJson('/reports', {
            target_type: props.targetType,
            target_id: props.targetId,
            rule_index: kind.value === GROUP ? ruleIndex.value : null,
            platform_violation: kind.value === PLATFORM,
            text: asksReason.value ? text.value.trim() || null : null,
        });
        sent.value = true;
    } catch (e) {
        // "You have already reported this." comes from the server in the user's language.
        console.error('Report failed:', e);
        error.value = e.message || trans('reports.failed');
    } finally {
        sending.value = false;
    }
};
</script>

<template>
    <p v-if="sent" class="report-sent" role="status">
        {{ $t('reports.sent') }}
    </p>

    <form v-else class="comment-form mt-3" @submit.prevent="send">
        <p v-if="rules === null && !error" class="text-sm text-muted">{{ $t('reports.loading_rules') }}</p>

        <fieldset v-else-if="rules !== null" class="flex flex-col gap-2">
            <legend class="field-label">{{ $t('reports.why') }}</legend>

            <!-- A group without rules has nothing to pick from, so this is not offered. -->
            <label v-if="rules.length" class="settings-toggle">
                <input v-model="kind" type="radio" name="report-kind" class="checkbox-input mt-0.5 rounded-full" :value="GROUP" />
                <span class="text-sm text-ink">{{ $t('reports.group_rules') }}</span>
            </label>

            <div v-if="kind === GROUP" class="flex flex-col gap-2 ps-6">
                <p class="text-sm text-muted">{{ $t('reports.which_rule') }}</p>
                <label v-for="(rule, index) in rules" :key="index" class="settings-toggle">
                    <input v-model="ruleIndex" type="radio" name="report-rule" class="checkbox-input mt-0.5 rounded-full" :value="index" />
                    <span class="text-sm text-ink" dir="auto">{{ rule.text }}</span>
                </label>
            </div>

            <label class="settings-toggle">
                <input v-model="kind" type="radio" name="report-kind" class="checkbox-input mt-0.5 rounded-full" :value="PLATFORM" />
                <span class="text-sm text-ink">{{ $t('reports.platform') }}</span>
            </label>

            <label class="settings-toggle">
                <input v-model="kind" type="radio" name="report-kind" class="checkbox-input mt-0.5 rounded-full" :value="OTHER" />
                <span class="text-sm text-ink">{{ $t('reports.other') }}</span>
            </label>

            <input
                v-if="asksReason"
                v-model="text"
                class="field-input"
                :maxlength="limit"
                dir="auto"
                :placeholder="$t(kind === OTHER ? 'reports.placeholder' : 'reports.platform_placeholder')"
                :aria-label="$t(kind === OTHER ? 'reports.placeholder' : 'reports.platform_placeholder')"
                :disabled="sending"
            />
        </fieldset>

        <span class="flex gap-2 self-end">
            <button type="button" class="btn-secondary" @click="emit('close')">{{ $t('common.cancel') }}</button>
            <button type="submit" class="btn-primary" :disabled="!canSend">
                {{ sending ? $t('reports.sending') : $t('reports.send') }}
            </button>
        </span>
        <p v-if="error" class="vote-error" :title="error">⚠ {{ error }}</p>
    </form>
</template>
