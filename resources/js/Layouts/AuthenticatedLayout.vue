<script setup>
import Sidebar from "@/Components/sidebar.vue";
import Header from "@/Components/header.vue";
import { ref } from "vue";

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

const sidebarOpen = ref(false);
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            v-if="showSidebar"
            id="sidebar"
            :class="
                sidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0'
            "
            class="fixed z-50 flex flex-col justify-between w-64 h-full min-h-screen p-6 transition-transform duration-200 ease-in-out transform -translate-x-full shadow-2xl bg-gradient-to-b from-blue-500 to-teal-400 lg:translate-x-0"
        >
            <Sidebar v-if="showSidebar" />
        </aside>

        <!-- Main -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
            @click="sidebarOpen = false"
        ></div>
        <div
            class="flex flex-col flex-1 transition-transform duration-200 lg:ml-64"
        >
            <Header
                v-if="showHeader"
                :title="headerTitle"
                @toggle-sidebar="toggleSidebar"
            />
            <main class="flex-1 p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
