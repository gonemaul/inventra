<script setup>
import Sidebar from "@/Components/sidebar.vue";
import Header from "@/Components/header.vue";
import { useSidebar } from "@/Composable/useSidebar";
import { useExtended } from "@/Composable/useExtended";
import { useDarkMode } from "@/Composable/useDarkMode";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { watch } from "vue";
import { Link } from "@inertiajs/vue3";
import ActionLoader from "@/Components/ActionLoader.vue";

const page = usePage();
const toast = useToast();
const { isSidebarOpen, closeSidebar } = useSidebar();
const { isExtended } = useExtended();
const { isDarkMode } = useDarkMode();

// Watcher Global
watch(
    () => page.props.flash,
    (flash) => {
        // Debugging: Cek apakah console log ini muncul saat redirect?
        console.log("Flash Message Diterima:", flash);

        if (flash?.success) {
            toast.success(flash.success);
            page.props.flash.success = null;
        }
        if (flash?.error) {
            toast.error(flash.error);
            page.props.flash.error = null;
        }
    },
    { deep: true }
);
// watch(
//     () => page.props.flash.success,
//     (message) => {
//         if (message) {
//             toast.success(message);
//         }
//     }
// );

// watch(
//     () => page.props.flash.error,
//     (message) => {
//         if (message) {
//             toast.error(message);
//         }
//     }
// );

defineProps({
    showHeader: {
        type: Boolean,
        default: true, // default aktif
    },
    showSidebar: {
        type: Boolean,
        default: true, // default aktif
    },
    showBottomBar: {
        type: Boolean,
        default: true,
    },
    headerTitle: { type: String, default: "Inventra" },
});
</script>

<template>
    <ActionLoader />
    <div class="flex min-h-screen pb-12 lg:pb-0">
        <!-- Sidebar -->
        <Sidebar v-if="showSidebar" />
        <!-- </aside> -->

        <!-- Main -->
        <div
            v-if="isSidebarOpen"
            class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
            @click="closeSidebar"
        ></div>
        <div
            :class="showSidebar ? (isExtended ? 'lg:ml-64' : 'lg:ml-20') : ''"
            class="flex flex-col flex-1 w-full transition-transform duration-200"
        >
            <Header
                v-if="showHeader"
                :showSidebar="showSidebar"
                :title="headerTitle"
            />
            <main
                class="flex-1 p-2 lg:p-6 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
            >
                <slot />
            </main>
        </div>
    </div>
    <div
        v-if="showBottomBar"
        class="fixed print:hidden bottom-0 left-0 z-50 w-full h-16 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 lg:hidden shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]"
    >
        <div class="grid h-full max-w-lg grid-cols-3 mx-auto font-medium">
            <Link
                :href="route('dashboard')"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-700 group"
                :class="
                    route().current('dashboard')
                        ? 'text-lime-600 dark:text-lime-400'
                        : 'text-gray-500 dark:text-gray-400'
                "
            >
                <svg
                    class="w-6 h-6 mb-1 transition-colors group-hover:text-lime-600 dark:group-hover:text-lime-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                    ></path>
                </svg>
                <span class="text-[10px]">Home</span>
            </Link>

            <div class="relative flex items-center justify-center">
                <Link
                    :href="route('sales.pos')"
                    class="absolute flex items-center justify-center w-16 h-16 text-white transition-transform border-4 border-gray-100 rounded-full shadow-lg -top-5 bg-lime-500 dark:border-gray-900 hover:bg-lime-600 hover:scale-105"
                >
                    <svg
                        class="w-8 h-8"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                        ></path>
                    </svg>
                </Link>
                <span
                    class="text-[10px] text-lime-600 dark:text-lime-400 font-bold mt-8"
                    >POS</span
                >
            </div>

            <Link
                :href="route('sales.create')"
                class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-700 group"
                :class="
                    route().current('sales.recap')
                        ? 'text-lime-600 dark:text-lime-400'
                        : 'text-gray-500 dark:text-gray-400'
                "
            >
                <svg
                    class="w-6 h-6 mb-1 transition-colors group-hover:text-lime-600 dark:group-hover:text-lime-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                    ></path>
                </svg>
                <span class="text-[10px]">Rekap</span>
            </Link>
        </div>
    </div>
</template>
