<script setup>
import { computed } from "vue";

const props = defineProps({
    cashflow: {
        type: Object,
        default: () => ({
            in: 0,
            out: 0,
            balance: 0,
            message: "",
            status: "safe",
        }),
    },
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Logic visualisasi persentase bar
const healthPercent = computed(() => {
    if (props.cashflow.out === 0) return 100;
    const ratio = (props.cashflow.in / props.cashflow.out) * 100;
    return Math.min(100, Math.max(0, ratio));
});
</script>

<template>
    <div
        class="relative flex flex-col justify-between h-full p-6 overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl group"
    >
        <div
            class="absolute top-0 right-0 w-32 h-32 -mt-16 -mr-16 transition-all duration-700 rounded-full blur-3xl opacity-20"
            :class="cashflow.balance >= 0 ? 'bg-lime-400' : 'bg-red-500'"
        ></div>

        <div class="relative z-10 flex items-start justify-between mb-2">
            <div>
                <p
                    class="mb-1 text-xs font-bold tracking-widest text-gray-400 uppercase"
                >
                    Proyeksi Cashflow
                </p>
                <span
                    class="inline-block px-2 py-0.5 rounded text-[10px] font-bold transition-colors"
                    :class="
                        cashflow.balance >= 0
                            ? 'bg-lime-50 text-lime-700 dark:bg-lime-900/30 dark:text-lime-300'
                            : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                    "
                >
                    {{ cashflow.balance >= 0 ? "SURPLUS" : "DEFISIT" }}
                </span>
            </div>

            <div
                class="flex items-center justify-center w-10 h-10 transition-colors border rounded-xl"
                :class="
                    cashflow.balance >= 0
                        ? 'bg-lime-50 text-lime-600 border-lime-100 dark:bg-lime-900/20 dark:text-lime-400 dark:border-lime-800'
                        : 'bg-red-50 text-red-600 border-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-800'
                "
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
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </div>
        </div>

        <div class="relative z-10 mt-2 mb-6">
            <h2
                class="text-3xl font-black tracking-tight"
                :class="
                    cashflow.balance >= 0
                        ? 'text-gray-800 dark:text-white'
                        : 'text-red-600 dark:text-red-400'
                "
            >
                {{ formatRupiah(cashflow.balance) }}
            </h2>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                Estimasi saldo 7 hari ke depan
            </p>
        </div>

        <div class="relative z-10 grid grid-cols-2 gap-3 mb-4">
            <div
                class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-600"
            >
                <div class="flex items-center gap-1.5 mb-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                    <span class="text-[10px] uppercase font-bold text-gray-400"
                        >Masuk</span
                    >
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">
                    {{ formatRupiah(cashflow.in) }}
                </p>
            </div>

            <div
                class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-700/30 border border-gray-100 dark:border-gray-600"
            >
                <div class="flex items-center gap-1.5 mb-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                    <span class="text-[10px] uppercase font-bold text-gray-400"
                        >Keluar</span
                    >
                </div>
                <p class="text-sm font-bold text-gray-700 dark:text-gray-200">
                    {{ formatRupiah(cashflow.out) }}
                </p>
            </div>
        </div>

        <div class="relative z-10 mt-auto">
            <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                <span>Rasio Kesehatan</span>
                <span>{{ healthPercent.toFixed(0) }}%</span>
            </div>

            <div
                class="w-full h-2 mb-2 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-700"
            >
                <div
                    class="h-full transition-all duration-1000 ease-out rounded-full shadow-sm"
                    :class="
                        cashflow.balance >= 0 ? 'bg-lime-500' : 'bg-red-500'
                    "
                    :style="{ width: healthPercent + '%' }"
                ></div>
            </div>

            <p
                class="text-[10px] font-medium leading-snug text-gray-500 dark:text-gray-400"
            >
                {{ cashflow.message }}
            </p>
        </div>
    </div>
</template>
