<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BackButton from '@/Pages/Components/BackButton.vue';
import CaseContext from '@/Pages/Components/CaseContext.vue';
import GroupAside from '@/Pages/Components/GroupAside.vue';
import PageHeader from '@/Pages/Components/PageHeader.vue';
import { useGroupMembership } from '@/utils/groupMembership';
import { selectionIn } from '@/utils/selection';

// One case of the moderation queue: the content in its conversation, what it was reported for, and
// the verdict, all on one page. Neither who wrote the content nor who reported it is ever sent.
const props = defineProps({
    group: Object,
    is_member: Boolean,
    can_edit: Boolean,
    role: String,
    // { id, created_at, reports_count, target, reports }; see ModerationCaseResource.
    moderation_case: Object,
    // { post, ancestors, comment, replies }; see CaseContextResource.
    context: Object,
    // The rules a verdict can name, as they were when the case opened: [{ index, text, kind }].
    rules: Array,
    // The rule most reports named, to start with; the moderator decides.
    suggested_rule: { type: Number, default: null },
});

const membership = useGroupMembership(props);

const REASON_MAX_LENGTH = 1000;
const TYPES = ['no_violation', 'remove', 'warning'];

// Without rules nothing can be punished: a case can only be closed as "no violation".
const form = useForm({
    type: props.rules.length ? 'remove' : 'no_violation',
    rule_index: props.suggested_rule ?? props.rules[0]?.index ?? null,
    marks: [],
    reason: '',
});

const punishes = computed(() => form.type !== 'no_violation');
const chosenRule = computed(() => props.rules.find((rule) => rule.index === form.rule_index) ?? null);

// Marking happens in the judged text itself, as a translator's pop-up does: select words in it and a
// small "Mark" button appears over them; click a marked place and one appears to take it off. Only
// while the verdict can punish. A selection that overlaps a mark already there is not added twice.
const contextView = ref(null);
// { kind: 'mark' | 'unmark', x, y, index? }: where the floating button stands, on the screen.
const popover = ref(null);

const above = (rect) => ({ x: rect.left + rect.width / 2, y: rect.top - 8 });

const markSelection = () => {
    const mark = selectionIn(contextView.value?.target);

    if (mark && !form.marks.some((m) => m.start < mark.end && mark.start < m.end)) {
        form.marks.push(mark);
    }

    window.getSelection()?.removeAllRanges();
    popover.value = null;
};

const unmark = (index) => {
    form.marks.splice(index, 1);
    popover.value = null;
};

const onSelectionEnd = (event) => {
    // A press on the floating button itself: leave it be, or it would be gone before its click lands.
    if (event.target instanceof Element && event.target.closest('.mark-popover')) return;

    const target = contextView.value?.target;

    if (!punishes.value || !target) {
        popover.value = null;
        return;
    }

    const selection = window.getSelection();
    const marked = event.target instanceof Element ? event.target.closest('mark[data-from]') : null;

    // A click on a marked place, selecting nothing: offer to take that mark off.
    if (marked && target.contains(marked) && (!selection || selection.isCollapsed)) {
        const from = Number(marked.dataset.from);
        const index = form.marks.findIndex((m) => m.start <= from && from < m.end);

        if (index >= 0) {
            popover.value = { kind: 'unmark', index, ...above(marked.getBoundingClientRect()) };
            return;
        }
    }

    popover.value = selectionIn(target)
        ? { kind: 'mark', ...above(selection.getRangeAt(0).getBoundingClientRect()) }
        : null;
};

// It stands where the words were on the screen, so it goes when the page moves under it.
const hidePopover = () => (popover.value = null);

onMounted(() => {
    document.addEventListener('mouseup', onSelectionEnd);
    document.addEventListener('keyup', onSelectionEnd);
    document.addEventListener('touchend', onSelectionEnd);
    window.addEventListener('scroll', hidePopover, true);
});
onBeforeUnmount(() => {
    document.removeEventListener('mouseup', onSelectionEnd);
    document.removeEventListener('keyup', onSelectionEnd);
    document.removeEventListener('touchend', onSelectionEnd);
    window.removeEventListener('scroll', hidePopover, true);
});

watch(punishes, (can) => {
    if (!can) popover.value = null;
});

const submit = () => {
    form.post(route('groups.moderation.verdict', [props.group.slug, props.moderation_case.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="` | ${$t('moderation.case.title')} | ${group.name}`" />

    <GroupAside :group="group" :can-edit="can_edit" :role="role" :membership="membership" />

    <section class="feed">
        <PageHeader :title="$t('moderation.case.title')" />

        <!-- Spaced as the posts of the home feed are. -->
        <div class="feed-list">
            <p v-if="!context.post && !context.comment" class="comment-deleted">{{ $t('moderation.gone') }}</p>
            <!-- Since when it is hidden and how many reported it: on the judged content's own line. -->
            <CaseContext
                v-else
                ref="contextView"
                :context="context"
                :marks="form.marks"
                :judged-info="{ hidden_at: moderation_case.created_at, reports_count: moderation_case.reports_count }"
            />

            <section class="post-card flex flex-col gap-2 text-sm">
                <h2 class="feed-title">{{ $t('moderation.reported_for') }}</h2>
                <template v-if="moderation_case.reports.length">
                    <p v-for="report in moderation_case.reports" :key="report.id" class="case-report" dir="auto">
                        <span class="case-report-kind">{{ $t(`moderation.kind.${report.kind}`) }}</span>
                        <template v-if="report.rule"> — {{ report.rule.index + 1 }}. {{ report.rule.text }}</template>
                        <template v-if="report.text"> — {{ report.text }}</template>
                    </p>
                </template>
                <p v-else class="text-muted">{{ $t('moderation.case.no_reports') }}</p>
            </section>

            <!-- The verdict, with the content above in view, laid out as the Settings form is. -->
            <form class="post-form" @submit.prevent="submit">
                <fieldset class="settings-section">
                    <legend class="settings-heading">{{ $t('moderation.verdict.title') }}</legend>

                    <div v-for="type in TYPES" :key="type">
                        <label class="settings-toggle" :class="{ 'is-disabled': type !== 'no_violation' && !rules.length }">
                            <input
                                v-model="form.type"
                                type="radio"
                                name="verdict-type"
                                class="checkbox-input mt-0.5 rounded-full"
                                :value="type"
                                :disabled="type !== 'no_violation' && !rules.length"
                            />
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $t(`moderation.verdict.types.${type}`) }}</span>
                                <span class="block text-xs text-muted">{{ $t(`moderation.verdict.type_hints.${type}`) }}</span>
                            </span>
                        </label>
                    </div>
                    <p v-if="!rules.length" class="field-hint mt-0">{{ $t('moderation.verdict.no_rules') }}</p>
                    <p v-if="form.errors.type" class="field-error">{{ form.errors.type }}</p>
                </fieldset>

                <fieldset v-if="punishes" class="settings-section">
                    <legend class="settings-heading">{{ $t('moderation.verdict.grounds') }}</legend>

                    <div>
                        <label class="field-label" for="verdict-rule">{{ $t('moderation.verdict.rule') }}</label>
                        <select id="verdict-rule" v-model.number="form.rule_index" class="field-input" :class="{ 'has-error': form.errors.rule_index }">
                            <option v-for="rule in rules" :key="rule.index" :value="rule.index">{{ rule.index + 1 }}. {{ rule.text }}</option>
                        </select>
                        <!-- What breaking it gives, if the verdict is a warning: fixed by the rule, not chosen here. -->
                        <p v-if="chosenRule" class="field-hint">{{ $t(`moderation.verdict.rule_kind.${chosenRule.kind}`) }}</p>
                        <p v-if="form.errors.rule_index" class="field-error">{{ form.errors.rule_index }}</p>
                    </div>

                    <!-- The marks are made in the judged text above; here they are listed, each with its ✕. -->
                    <div>
                        <p class="field-label">{{ $t('moderation.verdict.marks') }}</p>
                        <p class="field-hint mt-0 mb-2">{{ $t('moderation.verdict.marks_hint') }}</p>

                        <ul v-if="form.marks.length" class="flex flex-wrap gap-2">
                            <li v-for="(mark, index) in form.marks" :key="`${mark.start}-${mark.end}`" class="case-mark-chip">
                                <span dir="auto">«{{ mark.quote }}»</span>
                                <button type="button" class="language-chip-remove" :aria-label="$t('moderation.verdict.unmark')" @click="unmark(index)">✕</button>
                            </li>
                        </ul>
                        <p v-else class="field-hint">{{ $t('moderation.verdict.no_marks') }}</p>
                        <p v-if="form.errors.marks" class="field-error">{{ form.errors.marks }}</p>
                    </div>

                    <div>
                        <label class="field-label" for="verdict-reason">{{ $t('moderation.verdict.reason') }}</label>
                        <textarea
                            id="verdict-reason"
                            v-model="form.reason"
                            class="field-input"
                            :class="{ 'has-error': form.errors.reason }"
                            rows="3"
                            :maxlength="REASON_MAX_LENGTH"
                            dir="auto"
                            :placeholder="$t('moderation.verdict.reason_placeholder')"
                        ></textarea>
                        <p class="field-hint">{{ form.reason.length }} / {{ REASON_MAX_LENGTH }}</p>
                        <p v-if="form.errors.reason" class="field-error">{{ form.errors.reason }}</p>
                    </div>
                </fieldset>

                <div class="wizard-actions">
                    <BackButton :fallback="route('groups.moderation', group.slug)" />
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        {{ form.processing ? $t('moderation.verdict.submitting') : $t('moderation.verdict.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </section>

    <!-- In <body>, out of the page's layout, which stretches every element of the page to the column's
         width. A site button, only smaller. mousedown.prevent: pressing it must not clear the selection
         it is about. -->
    <Teleport to="body">
        <button
            v-if="popover"
            type="button"
            class="btn-secondary mark-popover px-3 py-1.5 text-xs"
            :style="{ left: `${popover.x}px`, top: `${popover.y}px` }"
            @mousedown.prevent
            @click="popover.kind === 'mark' ? markSelection() : unmark(popover.index)"
        >
            {{ popover.kind === 'mark' ? $t('moderation.verdict.mark') : $t('moderation.verdict.unmark') }}
        </button>
    </Teleport>
</template>
