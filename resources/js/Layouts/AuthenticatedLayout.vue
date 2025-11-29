<script setup>
import Sidebar from "@/Components/sidebar.vue";
import Header from "@/Components/header.vue";
import { useSidebar } from "@/Composable/useSidebar";
import { useExtended } from "@/Composable/useExtended";
import { useDarkMode } from "@/Composable/useDarkMode";
import { usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { watch } from "vue";
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
    headerTitle: { type: String, default: "Inventra" },
});
</script>

<template>
    <ActionLoader />
    <div class="flex min-h-screen">
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
                class="flex-1 p-6 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
            >
                <slot />
            </main>
        </div>
    </div>
</template>
