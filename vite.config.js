import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import viteCompression from "vite-plugin-compression";
import { VitePWA } from "vite-plugin-pwa";

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
        VitePWA({
            registerType: "autoUpdate",
            outDir: "public", // Output ke folder public build Laravel
            scope: "/",
            base: "/",
            manifest: {
                name: "Inventra",
                short_name: "Inventra",
                description: "Aplikasi Pintar Manajemen Toko",
                theme_color: "#ffffff",
                background_color: "#ffffff",
                start_url: "/",
                display: "standalone", // Agar terlihat seperti native app (tanpa browser bar)
                orientation: "portrait",
                icons: [
                    {
                        src: "/icons/pwa-192x192.png",
                        sizes: "192x192",
                        type: "image/png",
                        purpose: "any", // Icon standar (biasanya background transparan)
                    },
                    {
                        src: "/icons/pwa-512x512.png",
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "any",
                    },
                    {
                        src: "/icons/pwa-512x512.png", // Gunakan gambar yang sama atau khusus maskable (full bleed)
                        sizes: "512x512",
                        type: "image/png",
                        purpose: "maskable", // Icon untuk Android adaptif (biasanya background penuh warna)
                    },
                ],
                screenshots: [
                    {
                        // Screenshot Tampilan Mobile
                        src: "/screenshots/mobile-home.png", // Pastikan file ini ada di public/screenshots
                        sizes: "401x811", // Sesuaikan dengan ukuran asli gambar
                        type: "image/png",
                        label: "Tampilan Beranda Mobile",
                    },
                    {
                        // Screenshot Tampilan Desktop (Wajib ada form_factor: wide)
                        src: "/screenshots/desktop-dashboard.png", // Pastikan file ini ada
                        sizes: "1897x922", // Sesuaikan dengan ukuran asli gambar
                        type: "image/png",
                        form_factor: "wide", // INI KUNCINYA untuk menghilangkan warning desktop
                        label: "Dashboard Desktop",
                    },
                ],
            },
            workbox: {
                // Pola file yang akan dicache (offline support dasar)
                navigateFallback: null, // Penting untuk SPA/Inertia agar tidak 404 saat refresh
                cleanupOutdatedCaches: true,
                globPatterns: ["**/*.{js,css,html,ico,png,svg,woff2}"],
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
