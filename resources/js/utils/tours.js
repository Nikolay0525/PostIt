import { onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import '../../css/tours.css';
import { trans } from 'laravel-vue-i18n';
import { patchJson } from '@/utils/http';

// Each tour is a list of steps; a step points at the element marked data-tour="<tour>.<step>"
// and takes its title and text from lang/*/tours.php (tours.<tour>.<step>.title / .text).
// A step can name its own selector instead, for a part of an element marked for another step.
const TOURS = {
    navigation: ['home', 'search', 'inbox', 'account'],
    feed: [
        'tabs',
        // Only the first card of the list is marked, so the step lands on one post.
        'card',
        { step: 'counters', selector: '[data-tour="feed.card"] .post-footer' },
    ],
};

// The navbar is on every page, so a page without tours of its own still has this one.
const DEFAULT_TOURS = ['navigation'];

// Which tours the current page explains. Kept with the page component it belongs to, so the
// persistent layout never mixes up an old page's tours with the new page's.
const registered = ref({ component: null, tours: DEFAULT_TOURS });

// Called by a page with tours of its own, e.g. the home page: ['navigation', 'feed'].
export function usePageTours(tours) {
    const page = usePage();

    onMounted(() => {
        registered.value = { component: page.component, tours };
    });
}

// The tours of the page now shown: what "Tour of the site" in the account menu runs.
export function currentPageTours(component) {
    return registered.value.component === component ? registered.value.tours : DEFAULT_TOURS;
}

function stepsOf(name) {
    return TOURS[name]
        .map((entry) => (typeof entry === 'string' ? { step: entry } : entry))
        .map(({ step, selector }) => ({
            element: selector ?? `[data-tour="${name}.${step}"]`,
            popover: {
                title: trans(`tours.${name}.${step}.title`),
                description: trans(`tours.${name}.${step}.text`),
            },
        }));
}

// Runs several tours as one, without a pause between them. onEnd is called once, whether the
// user finished or closed it on the way: either way they took it, so it is not offered again.
export function startTours(names, onEnd = () => {}) {
    const steps = names
        .flatMap(stepsOf)
        // An element hidden on this screen (e.g. a phone layout) is skipped rather than shown in the void.
        .filter((step) => document.querySelector(step.element)?.offsetParent);

    if (!steps.length) return;

    driver({
        steps,
        showProgress: true,
        popoverClass: 'tour-popover',
        nextBtnText: trans('tours.buttons.next'),
        prevBtnText: trans('tours.buttons.prev'),
        doneBtnText: trans('tours.buttons.done'),
        progressText: trans('tours.buttons.progress', { current: '{{current}}', total: '{{total}}' }),
        onDestroyed: onEnd,
    }).drive();
}

// Saves the answer for each tour, here and on the server. "Later" is never sent: it is only
// remembered in the browser until the tab is closed.
export function answerTours(page, names, status) {
    page.props.tours = { ...page.props.tours, ...Object.fromEntries(names.map((name) => [name, status])) };

    for (const name of names) {
        patchJson(route('settings.tours'), { tour: name, status }).catch(() => {});
    }
}
