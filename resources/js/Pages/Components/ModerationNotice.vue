<script setup>
import { computed } from 'vue';

// What the author of a post or comment is told of its moderation: it waits for the moderators' decision
// (`pending`), or it was taken down (`removed`) and then, where the page has it, which rule it broke, what
// breaking it leads to, and the moderator's own reason. `compact` leaves the details out, for a list.
// Nobody who decided is named: the server sends no such thing.
const props = defineProps({
    // { state: 'pending' | 'removed', verdict?: { type, rule, marks, reason } | null }
    moderation: { type: Object, required: true },
    kind: { type: String, default: 'post' },
    compact: Boolean,
});

const verdict = computed(() => props.moderation.verdict ?? null);
const removedKey = computed(() => (verdict.value?.type === 'warning' ? 'warned' : 'removed'));
</script>

<template>
    <div class="moderation-notice" role="status">
        <p v-if="moderation.state === 'pending'">{{ $t(`moderation.author.pending.${kind}`) }}</p>

        <template v-else>
            <p class="font-medium">{{ $t(`moderation.author.${removedKey}.${kind}`) }}</p>

            <template v-if="verdict && !compact">
                <p v-if="verdict.rule" dir="auto">
                    {{ $t('moderation.author.rule', { number: verdict.rule.index + 1, text: verdict.rule.text }) }}
                    <span class="text-muted">{{ $t(`moderation.author.rule_kind.${verdict.rule.kind}`) }}</span>
                </p>
                <p v-if="verdict.reason" dir="auto">
                    <span class="font-medium">{{ $t('moderation.author.reason') }}:</span> {{ verdict.reason }}
                </p>
                <p v-if="verdict.marks.length" class="text-muted">{{ $t('moderation.author.marked') }}</p>
            </template>
        </template>

        <!-- Where an appeal against the decision will go. -->
        <slot />
    </div>
</template>
