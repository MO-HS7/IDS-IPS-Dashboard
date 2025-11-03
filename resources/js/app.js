import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import { route } from 'ziggy-js';
import ClickOutside from './Directives/ClickOutside';
import i18n from './i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(i18n)
            .directive('click-outside', ClickOutside);
            
        app.config.globalProperties.route = route

        // Global error handler
        app.config.errorHandler = (err, instance, info) => {
            console.error('Global error:', err);
            console.error('Component instance:', instance);
            console.error('Error info:', info);
            
            // You can integrate with error tracking services here
            if (import.meta.env.PROD) {
                // Example: Sentry.captureException(err)
            }
        };

        return app.mount(el);
    },
    progress: {
        color: '#4F46E5',
    },
});
