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
    server: {
        host: "0.0.0.0", // biar bisa diakses dari luar (hp)
        port: 5173,
        // strictPort: true, // Jika port 5173 dipakai, jangan ganti ke 5174 (error aja)
        hmr: {
            host: ["192.168.0.23"], // ganti dengan IP lokal PC kamu
            // host: "localhost",
        },
        // allowedHosts: "all",
    },
});
