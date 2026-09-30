import '../css/app.css';
import { createApp, h } from 'vue'
import { createInertiaApp, Head, Link, router } from '@inertiajs/vue3'
import { i18nVue, getActiveLanguage, loadLanguageAsync } from 'laravel-vue-i18n'
import Layout from './Layouts/Layout.vue'
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

createInertiaApp({
    title: title => `PostIt${title}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        let page = pages[`./Pages/${name}.vue`];
        page.default.layout = page.default.layout || Layout
        return page
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18nVue, {
                lang: props.initialPage.props.locale,
                fallbackLang: 'en',
                fallbackMissingTranslations: true,
                resolve: async lang => {
                    const langs = import.meta.glob('../../lang/php_*.json')
                    return await langs[`../../lang/php_${lang}.json`]()
                },
            })
            .component('Head', Head) 
            .component('Link', Link)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
        showSpinner: true,
        includeCSS: true,
    }
})

// The locale changes without a full reload when the user saves another interface language.
// Both events are needed: 'navigate' is skipped when a visit replaces the history entry (saving
// settings redirects back to the same URL), and 'success' does not fire on back/forward.
const syncLocale = event => {
    const locale = event.detail.page.props.locale

    if (locale && locale !== getActiveLanguage()) {
        loadLanguageAsync(locale)
    }
}

router.on('success', syncLocale)
router.on('navigate', syncLocale)
