<script setup>
import Sidebar from "@/Components/sidebar.vue";
import Header from "@/Components/header.vue";
import { useSidebar } from "@/Composable/useSidebar";
import { useDarkMode } from "@/Composable/useDarkMode";

const { isSidebarOpen, closeSidebar } = useSidebar();
const { isDarkMode } = useDarkMode();

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
    <div class="flex min-h-screen bg-gray-100">
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
            class="flex flex-col flex-1 transition-transform duration-200 lg:ml-64"
        >
            <Header v-if="showHeader" :title="headerTitle" />
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
