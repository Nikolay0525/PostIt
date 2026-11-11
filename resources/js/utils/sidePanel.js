import { ref } from 'vue';

// The side panel's state, shared by the layout that draws it, the pages' panel buttons (on a
// narrow screen it's a drawer) and utils/seen.js, whose answers bring fresh counters.

// { groups, people, version } from SidePanelService, or null when there's nothing to show.
export const panel = ref(null);
export const panelOpen = ref(false);

// The newest copy wins (`version` is when the server built it): a page restored from history or
// a page's props after a partial reload carry an older one than a "seen" answer may have brought.
export function takePanel(next) {
    if (next === null) {
        panel.value = null;
    } else if (!panel.value || next.version > panel.value.version) {
        panel.value = next;
    }
}
