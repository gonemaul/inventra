<script setup>
import { router, Link } from "@inertiajs/vue3";
import { format } from "date-fns";
import { id } from "date-fns/locale";

// Menerima data insights dari parent
defineProps({
    insights: {
        type: Array,
        default: () => [],
    },
});

const severityMap = {
    critical: { label: "Kritis", class: "bg-red-100 text-red-800 border-red-200 dark:bg-red-900/50 dark:text-red-300 dark:border-red-800" },
    warning: { label: "Peringatan", class: "bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/50 dark:text-amber-300 dark:border-amber-800" },
    info: { label: "Info", class: "bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/50 dark:text-blue-300 dark:border-blue-800" },
};

const formatTypeLabel = (type) => {
    // Basic mapping, could be expanded if needed or imported from constants
    const labels = {
        'dead_stock': 'Stok Mati',
        'margin_alert': 'Peringatan Margin',
        'restock': 'Perlu Restock',
        'trend': 'Sedang Tren',
        'high_margin': 'Margin Tinggi',
        'new_product': 'Produk Baru',
    };
    return labels[type] || (type ? type.replace(/_/g, " ").toUpperCase() : 'UNKNOWN');
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(date);
};

// Action Handler
const handleInsight = (url) => {
    if (url) router.visit(url);
};
</script>

<template>
    <div
        class="flex flex-col h-full overflow-hidden bg-white border border-gray-200 shadow-sm shadow-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl"
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
                class="relative p-4 overflow-hidden transition-all bg-white border cursor-pointer group rounded-2xl hover:shadow-md dark:bg-gray-800 dark:border-gray-700"
            >
                <!-- Severity Line Indicator on Left -->
                <div 
                    class="absolute inset-y-0 left-0 w-1"
                    :class="{
                        'bg-red-500': insight.severity === 'critical',
                        'bg-amber-500': insight.severity === 'warning',
                        'bg-blue-500': insight.severity === 'info'
                    }"
                ></div>

                <div class="flex gap-4">
                    <!-- Image / Icon Fallback -->
                    <div v-if="insight.product" class="flex-shrink-0 w-16 h-16 bg-gray-50 border border-gray-100 rounded-xl overflow-hidden shadow-sm dark:bg-gray-700 dark:border-gray-600 flex items-center justify-center relative">
                        <img v-if="insight.product.image_url" :src="insight.product.image_url" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div v-else class="text-gray-300 dark:text-gray-500">
                            <!-- SVG Icons Matching Severity -->
                            <svg v-if="insight.severity === 'critical'" class="w-8 h-8 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <svg v-else-if="insight.severity === 'warning'" class="w-8 h-8 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <svg v-else class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <!-- Top Row: Type Label & Date -->
                        <div class="flex items-center justify-between mb-1">
                            <span 
                                class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold border rounded-md uppercase tracking-wider"
                                :class="(severityMap[insight.severity] || severityMap.info).class">
                                {{ formatTypeLabel(insight.type) }}
                            </span>
                            <span class="text-[10px] font-bold text-gray-400">
                                {{ formatDate(insight.created_at) }}
                            </span>
                        </div>
                        
                        <!-- Header & Product Name -->
                        <div class="mb-1">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 line-clamp-1 group-hover:text-blue-600 transition truncate">
                                {{ insight.product ? insight.product.name : insight.title }}
                            </h4>
                        </div>

                        <!-- Message Muted -->
                        <p class="text-xs font-medium leading-relaxed text-gray-500 dark:text-gray-400 line-clamp-2">
                            {{ insight.message }}
                        </p>

                        <!-- Hover Action Text -->
                        <div class="mt-2 flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0"
                             :class="{
                                'text-red-600 dark:text-red-400': insight.severity === 'critical',
                                'text-amber-600 dark:text-amber-400': insight.severity === 'warning',
                                'text-blue-600 dark:text-blue-400': insight.severity === 'info',
                             }">
                            <span>Ambil Tindakan</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
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
