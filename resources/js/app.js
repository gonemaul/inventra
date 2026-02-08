import "../css/app.css";
import "./bootstrap";

import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";

import Toast, { POSITION } from "vue-toastification";
import "vue-toastification/dist/index.css";
import "vue-toastification/dist/index.css";

import { registerSW } from "virtual:pwa-register";

// Register Service Worker (Hanya reload jika ada update baru)
const updateSW = registerSW({
    onNeedRefresh() {
        if (confirm("Konten baru tersedia. Reload sekarang?")) {
            updateSW(true);
        }
    },
    onOfflineReady() {
        console.log("App siap bekerja offline!");
    },
});
const toastOptions = {
    position: POSITION.TOP_RIGHT,
    timeout: 5000,
    // hideProgressBar: true,
    newestOnTop: true,
};

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, toastOptions)
            .mount(el);
    },
    progress: {
        color: "#A3E635",
    },
});
