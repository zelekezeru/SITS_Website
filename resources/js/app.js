import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

// Initialize and refresh AOS animations on every SPA client-side page navigation
router.on('navigate', () => {
    if (typeof window !== 'undefined' && typeof window.AOS !== 'undefined') {
        setTimeout(() => {
            window.AOS.init({ once: true, duration: 700, offset: 60 });
            window.AOS.refresh();
        }, 150);
    }
});

createInertiaApp({
    title: (title) => title ? `${title} - SITS ERP` : 'SITS ERP',
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.route = (...args) => {
            if (typeof window.route === 'function') {
                return window.route(...args);
            }
            return '';
        };
        // Global translator used by the merged Library pages (and available app-wide):
        // reads the `translations` shared prop, falls back to the key.
        app.config.globalProperties.__ = (key, replace = {}) => {
            const translations = (props.initialPage?.props?.translations) || {};
            let translation = translations[key] ?? key;
            Object.keys(replace).forEach((r) => {
                translation = String(translation).replace(`:${r}`, replace[r]);
            });
            return translation;
        };
        app.use(plugin)
            .mount(el);
    },
});
