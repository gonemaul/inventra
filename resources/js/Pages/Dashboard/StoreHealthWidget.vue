<script setup>
import { computed } from "vue";

const props = defineProps({
    health: {
        type: Object,
        default: () => ({
            score: 0,
            status: "Unknown",
            color: "",
            details: { stock_score: 0, finance_score: 0 },
        }),
    },
});

// Logic Warna Dinamis berdasarkan Skor
const statusColor = computed(() => {
    if (props.health.score >= 80) return "text-lime-500";
    if (props.health.score >= 50) return "text-yellow-500";
    return "text-red-500";
});

const statusBg = computed(() => {
    if (props.health.score >= 80) return "bg-lime-500";
    if (props.health.score >= 50) return "bg-yellow-500";
    return "bg-red-500";
});

// Hitung lingkaran SVG
const radius = 56;
const circumference = 2 * Math.PI * radius; // Sekitar 351
const strokeDashoffset = computed(() => {
    return circumference - (props.health.score / 100) * circumference;
});
</script>

<template>
    <div
        class="relative flex flex-col items-center justify-between h-full p-6 overflow-hidden text-center bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 md:col-span-4 rounded-2xl group"
    >
        <div
            class="absolute w-32 h-32 transition-colors duration-700 -translate-x-1/2 -translate-y-1/2 rounded-full top-1/2 left-1/2 blur-3xl opacity-20"
            :class="statusBg"
        ></div>

        <div class="relative z-10 flex items-center justify-between mb-4">
            <p
                class="text-xs font-bold tracking-widest text-gray-400 uppercase"
            >
                Kesehatan Toko
            </p>
            <span
                class="px-2 py-0.5 text-[10px] font-bold rounded-full border bg-white/50 dark:bg-gray-700/50 backdrop-blur-sm"
                :class="[
                    statusColor,
                    health.score >= 50
                        ? 'border-gray-100 dark:border-gray-600'
                        : 'border-red-100 dark:border-red-900',
                ]"
            >
                {{ health.status }}
            </span>
        </div>

        <div class="relative z-10 flex items-center justify-center flex-1">
            <div class="relative w-36 h-36">
                <svg class="w-full h-full transform -rotate-90">
                    <circle
                        cx="72"
                        cy="72"
                        :r="radius"
                        stroke="currentColor"
                        stroke-width="12"
                        fill="transparent"
                        class="text-gray-100 dark:text-gray-700"
                    />
                    <circle
                        cx="72"
                        cy="72"
                        :r="radius"
                        stroke="currentColor"
                        stroke-width="12"
                        fill="transparent"
                        :stroke-dasharray="circumference"
                        :stroke-dashoffset="strokeDashoffset"
                        stroke-linecap="round"
                        class="transition-all duration-1000 ease-out"
                        :class="statusColor"
                    />
                </svg>

                <div
                    class="absolute inset-0 flex flex-col items-center justify-center"
                >
                    <span
                        class="text-5xl font-black tracking-tighter text-gray-800 dark:text-white"
                    >
                        {{ health.score }}
                    </span>
                    <span
                        class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1"
                        >Poin</span
                    >
                </div>
            </div>
        </div>

        <div class="relative z-10 grid grid-cols-2 gap-3 mt-6">
            <div
                class="flex flex-col items-center p-2 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-700/30 dark:border-gray-600"
            >
                <span class="text-[10px] font-bold text-gray-400 uppercase mb-1"
                    >Stok</span
                >
                <div class="flex items-end gap-1">
                    <span
                        class="text-sm font-bold text-gray-700 dark:text-gray-200"
                        >{{ health.details?.stock_score || 0 }}</span
                    >
                    <span class="text-[9px] text-gray-400 mb-0.5">/100</span>
                </div>
                <div
                    class="w-full h-1 mt-1 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-600"
                >
                    <div
                        class="h-full bg-blue-500 rounded-full"
                        :style="{
                            width: (health.details?.stock_score || 0) + '%',
                        }"
                    ></div>
                </div>
            </div>

            <div
                class="flex flex-col items-center p-2 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-700/30 dark:border-gray-600"
            >
                <span class="text-[10px] font-bold text-gray-400 uppercase mb-1"
                    >Keuangan</span
                >
                <div class="flex items-end gap-1">
                    <span
                        class="text-sm font-bold text-gray-700 dark:text-gray-200"
                        >{{ health.details?.finance_score || 0 }}</span
                    >
                    <span class="text-[9px] text-gray-400 mb-0.5">/100</span>
                </div>
                <div
                    class="w-full h-1 mt-1 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-600"
                >
                    <div
                        class="h-full bg-purple-500 rounded-full"
                        :style="{
                            width: (health.details?.finance_score || 0) + '%',
                        }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
