<script setup>
import { router } from "@inertiajs/vue3";

// Menerima data insights dari parent
defineProps({
    insights: {
        type: Array,
        default: () => [],
    },
});

// Action Handler
const handleInsight = (url) => {
    if (url) router.visit(url);
};
</script>

<template>
    <div
        class="flex flex-col h-full overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl"
    >
        <div
            class="flex items-center justify-between p-5 border-b-2 border-gray-200 shadow-sm dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
        >
            <h3
                class="flex items-center gap-2 font-bold text-gray-800 dark:text-white"
            >
                <span class="relative flex w-2 h-2">
                    <span
                        class="absolute inline-flex w-full h-full bg-red-400 rounded-full opacity-75 animate-ping"
                    ></span>
                    <span
                        class="relative inline-flex w-2 h-2 bg-red-500 rounded-full"
                    ></span>
                </span>
                Smart Assistant
            </h3>
            <span
                class="px-2 py-1 text-xs font-bold text-gray-600 bg-gray-200 rounded-full dark:text-gray-300 dark:bg-gray-700"
            >
                {{ insights.length }}
            </span>
        </div>

        <div
            class="p-4 flex-1 overflow-y-auto max-h-[350px] space-y-3 custom-scrollbar"
        >
            <div
                v-for="insight in insights"
                :key="insight.id"
                @click="handleInsight(insight.action_url)"
                class="relative p-3 overflow-hidden transition-all border cursor-pointer group rounded-xl hover:shadow-md dark:hover:shadow-none"
                :class="{
                    'bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800 hover:border-red-300 dark:hover:border-red-700':
                        insight.severity === 'critical',
                    'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-100 dark:border-yellow-800 hover:border-yellow-300 dark:hover:border-yellow-700':
                        insight.severity === 'warning',
                    'bg-blue-50 dark:bg-blue-900/20 border-blue-100 dark:border-blue-800 hover:border-blue-300 dark:hover:border-blue-700':
                        insight.severity === 'info',
                }"
            >
                <div class="flex gap-3">
                    <div class="flex-shrink-0 mt-1">
                        <span
                            v-if="insight.severity === 'critical'"
                            class="text-xl"
                            >🚨</span
                        >
                        <span
                            v-else-if="insight.severity === 'warning'"
                            class="text-xl"
                            >⚡</span
                        >
                        <span v-else class="text-xl">💡</span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h4
                            class="text-sm font-bold truncate"
                            :class="{
                                'text-red-800 dark:text-red-300':
                                    insight.severity === 'critical',
                                'text-yellow-800 dark:text-yellow-300':
                                    insight.severity === 'warning',
                                'text-blue-800 dark:text-blue-300':
                                    insight.severity === 'info',
                            }"
                        >
                            {{ insight.title }}
                        </h4>
                        <p
                            class="mt-1 text-xs leading-relaxed text-gray-600 dark:text-gray-400 line-clamp-2"
                        >
                            {{ insight.message }}
                        </p>

                        <div
                            class="mt-2 text-[10px] font-bold uppercase tracking-wider opacity-60 group-hover:opacity-100 transition text-gray-500 dark:text-gray-400"
                        >
                            Klik untuk proses &rarr;
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="insights.length === 0"
                class="flex flex-col items-center justify-center h-full py-12 text-center opacity-50"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 mb-2 text-gray-300 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-500"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Semua aman terkendali
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scrollbar Halus */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
/* Dark mode scrollbar */
:is(.dark .custom-scrollbar)::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
