import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    darkMode: "class",
    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Warna utama (lime theme)
                primary: {
                    DEFAULT: "#84CC16", // light mode utama
                    hover: "#65A30D",
                    light: "#A3E635", // dipakai di dark mode supaya lebih pop
                },

                // Background light & dark
                customBg: {
                    light: "#FFFFFF",
                    dark: "#1E293B",
                    tableLight: "#F9FAFB",
                    tableDark: "#334155",
                },

                // Text
                customText: {
                    // pakai textc supaya ga bentrok dengan text-gray
                    light: "#111827", // teks utama light
                    secondaryLight: "#6B7280",
                    dark: "#F9FAFB", // teks utama dark
                    secondaryDark: "#CBD5E1",
                },

                // Border
                borderc: {
                    light: "#E5E7EB",
                    dark: "#475569",
                },

                // Badge / Status
                success: {
                    bgLight: "#DCFCE7",
                    textLight: "#15803D",
                    bgDark: "#14532D",
                    textDark: "#BBF7D0",
                },
            },
        },
    },

    plugins: [forms],
};
