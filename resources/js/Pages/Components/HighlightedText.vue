<script setup>
import { computed } from 'vue';

// A text with the places a moderator marked in it picked out. The marks are positions in this very
// text (the plain text the moderator read) plus the quote; if the positions no longer fit, the quote is
// looked for. Drawn with plain elements, never as markup: the text is the author's own words.
const props = defineProps({
    text: { type: String, required: true },
    // [{ start, end, quote }]
    marks: { type: Array, default: () => [] },
});

const parts = computed(() => {
    const spans = [];

    for (const { start, end, quote } of props.marks) {
        let from = start;
        let to = end;

        if (props.text.slice(from, to) !== quote) {
            const found = props.text.indexOf(quote);

            if (found < 0) continue;

            from = found;
            to = found + quote.length;
        }

        spans.push([from, to]);
    }

    // Overlapping or touching marks become one.
    spans.sort((a, b) => a[0] - b[0]);
    const merged = [];
    for (const span of spans) {
        const last = merged[merged.length - 1];

        if (last && span[0] <= last[1]) last[1] = Math.max(last[1], span[1]);
        else merged.push([...span]);
    }

    const result = [];
    let at = 0;

    for (const [from, to] of merged) {
        if (from > at) result.push({ text: props.text.slice(at, from), marked: false });
        result.push({ text: props.text.slice(from, to), marked: true });
        at = to;
    }

    if (at < props.text.length) result.push({ text: props.text.slice(at), marked: false });

    return result;
});
</script>

<template>
    <p class="whitespace-pre-line" dir="auto"><template v-for="(part, index) in parts" :key="index"><mark v-if="part.marked" class="moderation-mark">{{ part.text }}</mark><template v-else>{{ part.text }}</template></template></p>
</template>
