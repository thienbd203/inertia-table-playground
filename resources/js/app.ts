import {
    Archive,
    ArchiveRestore,
    CircleCheck,
    Clock3,
    Inbox,
    Star,
    Stars,
} from '@lucide/vue';
import { createInertiaApp } from '@inertiajs/vue3';
import {
    createInertiaTable,
    setIconResolver,
    type IconResolver,
    vi,
} from '@musing/inertia-table-vue';
import { createApp, createSSRApp, h, type Plugin } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'Inertia Table Playground';

const icons = {
    Archive,
    ArchiveRestore,
    CircleCheck,
    Clock3,
    Inbox,
    Star,
    Stars,
};

// The linked package has its own development-only Vue install. Vite dedupes
// Vue at runtime; these casts bridge the two equivalent compile-time identities.
setIconResolver(
    ((name: string) =>
        icons[name as keyof typeof icons]) as unknown as IconResolver,
);

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#0f766e',
    },
    setup({ el, App, props, plugin }) {
        const app = (el ? createApp : createSSRApp)({
            render: () => h(App, props),
        })
            .use(plugin as unknown as Plugin)
            .use(
                createInertiaTable({
                    locale: 'vi-VN',
                    messages: vi,
                }) as unknown as Plugin,
            );

        if (el) {
            app.mount(el);
        }

        return app;
    },
});
