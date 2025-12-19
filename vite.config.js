import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        // OPSI 1: Konfigurasi Manual Chunks
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        // Memisahkan library besar ke chunk tersendiri
                        // Contoh: memisahkan vue, inertia, dan library UI
                        if (id.includes("vue") || id.includes("@vue")) {
                            return "vue-vendor";
                        }
                        if (id.includes("@inertiajs")) {
                            return "inertia-vendor";
                        }
                        // html5-qrcode atau library scan yang besar
                        if (id.includes("html5-qrcode")) {
                            return "scanner-vendor";
                        }

                        // Sisanya masuk ke vendor umum
                        return "vendor";
                    }
                },
            },
        },
    },
    server: {
        host: "0.0.0.0", // biar bisa diakses dari luar (hp)
        port: 5173,
        // strictPort: true, // Jika port 5173 dipakai, jangan ganti ke 5174 (error aja)
        hmr: {
            host: "192.168.0.20", // ganti dengan IP lokal PC kamu
            // host: "localhost",
        },
        // allowedHosts: "all",
    },
});
