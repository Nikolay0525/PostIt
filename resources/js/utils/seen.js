import { router } from '@inertiajs/vue3';
import { postJson } from '@/utils/http';
import { takePanel } from '@/utils/sidePanel';

// "Seen" for a post in a list: its whole card was on screen, even for a moment. Such posts stop
// counting as new (the side panel's counters, the "Only new" filter); opening or voting on a post
// still counts too (ExcludesSeenPosts on the server).
//
// Nothing is sent per post: seen ids gather here and go to the server as one batch every
// FLUSH_EVERY_MS, only when there is something new, and right away when the page is left (another
// page, the tab hidden or closed) so the last ones aren't lost.

const FLUSH_EVERY_MS = 5000;
// MarkPostsSeenRequest::MAX_IDS
const MAX_BATCH = 100;
// Steps at which the browser re-checks a card. Fine enough to notice a card taller than the
// screen filling it, which is when such a card counts (it can never be whole on screen).
const THRESHOLDS = Array.from({ length: 21 }, (_, i) => i / 20);

const pending = new Set();
// Every id seen on this page load, sent or not, so a card scrolled past twice is sent once.
const noted = new Set();
const ids = new WeakMap();
let observer = null;

const isWhollySeen = (entry) => {
    if (!entry.isIntersecting) return false;

    const screen = entry.rootBounds?.height ?? window.innerHeight;

    return entry.intersectionRect.height >= Math.min(entry.boundingClientRect.height, screen) - 1;
};

const flush = (keepalive = false) => {
    if (!pending.size) return;

    const batch = [...pending].slice(0, MAX_BATCH);
    batch.forEach((id) => pending.delete(id));

    // The answer is the side panel as it is now, so its counters drop while the user reads. A lost
    // batch goes back in line for the next one; it's never worth bothering the reader.
    postJson('/posts/seen', { ids: batch }, { keepalive }).then((data) => {
        if (data?.side_panel) takePanel(data.side_panel);
    }).catch((error) => {
        console.error('Marking posts seen failed:', error);
        batch.forEach((id) => pending.add(id));
    });
};

const start = () => {
    observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!isWhollySeen(entry)) continue;

            const id = ids.get(entry.target);
            pending.add(id);
            noted.add(id);
            observer.unobserve(entry.target);
        }
    }, { threshold: THRESHOLDS });

    setInterval(() => flush(), FLUSH_EVERY_MS);

    // Leaving: another page of the site, the tab hidden (often the last chance on a phone) or closed.
    router.on('before', () => flush(true));
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush(true);
    });
    window.addEventListener('pagehide', () => flush(true));
};

// Watches a post's card until it has been seen whole. For signed-in users only (the caller checks).
export function trackSeen(element, postId) {
    if (noted.has(postId)) return;
    if (!observer) start();

    ids.set(element, postId);
    observer.observe(element);
}

export function untrackSeen(element) {
    observer?.unobserve(element);
}
