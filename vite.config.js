import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import viteCompression from "vite-plugin-compression";

export default defineConfig({
    plugins: [
        viteCompression(),
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
                        // 3. Pisahkan CORE (Vue, Inertia, Axios) agar ter-cache browser
                        if (
                            id.includes("vue") ||
                            id.includes("inertia") ||
                            id.includes("axios")
                        ) {
                            return "vendor-core";
                        }
                        // 1. Pisahkan APEXCHARTS
                        if (
                            id.includes("apexcharts") ||
                            id.includes("vue3-apexcharts")
                        ) {
                            return "vendor-charts";
                        }
                        if (id.includes("@inertiajs")) {
                            return "vendor-inertia";
                        }
                        // html5-qrcode atau library scan yang besar
                        if (id.includes("html5-qrcode")) {
                            return "vendor-scanner";
                        }

                        // Sisanya masuk ke vendor umum
                        return "vendor-common";
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
