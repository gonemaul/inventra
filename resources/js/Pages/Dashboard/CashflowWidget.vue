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

// Logic visualisasi persentase bar (Estimasi)
// Jika surplus: Bar Penuh. Jika defisit: Bar sesuai rasio uang masuk vs keluar.
const healthPercent = computed(() => {
    if (props.cashflow.out === 0) return 100;
    const ratio = (props.cashflow.in / props.cashflow.out) * 100;
    return Math.min(100, Math.max(0, ratio));
});
</script>

<template>
    <div
        class="flex flex-col justify-between h-full p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm rounded-2xl relative overflow-hidden"
    >
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-2">
                <p
                    class="text-xs font-bold text-gray-400 uppercase tracking-widest"
                >
                    Proyeksi Cashflow (7 Hari)
                </p>
                <span
                    class="px-2 py-0.5 text-[10px] font-bold rounded uppercase"
                    :class="
                        cashflow.balance >= 0
                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                    "
                >
                    {{ cashflow.balance >= 0 ? "Surplus" : "Defisit" }}
                </span>
            </div>

            <div class="flex items-baseline gap-2 mb-4">
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
            </div>
        </div>

        <div
            class="grid grid-cols-2 gap-4 py-3 border-t border-b border-gray-100 dark:border-gray-700 mb-4 relative z-10"
        >
            <div>
                <p
                    class="text-[10px] text-gray-400 uppercase font-semibold mb-1"
                >
                    Estimasi Masuk
                </p>
                <div
                    class="flex items-center gap-1.5 text-green-600 dark:text-green-400"
                >
                    <svg
                        class="w-4 h-4 bg-green-50 rounded-full p-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                        ></path>
                    </svg>
                    <span class="font-bold text-sm">{{
                        formatRupiah(cashflow.in)
                    }}</span>
                </div>
            </div>

            <div>
                <p
                    class="text-[10px] text-gray-400 uppercase font-semibold mb-1"
                >
                    Tagihan Keluar
                </p>
                <div
                    class="flex items-center gap-1.5 text-red-600 dark:text-red-400"
                >
                    <svg
                        class="w-4 h-4 bg-red-50 rounded-full p-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
                        ></path>
                    </svg>
                    <span class="font-bold text-sm">{{
                        formatRupiah(cashflow.out)
                    }}</span>
                </div>
            </div>
        </div>

        <div class="mt-auto relative z-10">
            <div class="flex justify-between text-[10px] text-gray-400 mb-1">
                <span>Kesehatan Arus Kas</span>
                <span>{{ healthPercent.toFixed(0) }}%</span>
            </div>

            <div
                class="w-full h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden mb-3"
            >
                <div
                    class="h-full rounded-full transition-all duration-1000"
                    :class="
                        cashflow.balance >= 0 ? 'bg-green-500' : 'bg-red-500'
                    "
                    :style="{ width: healthPercent + '%' }"
                ></div>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400 leading-snug">
                <span
                    class="font-bold"
                    :class="
                        cashflow.balance >= 0
                            ? 'text-green-600'
                            : 'text-red-500'
                    "
                >
                    {{ cashflow.balance >= 0 ? "Aman." : "Perhatian!" }}
                </span>
                {{ cashflow.message }}
            </p>
        </div>

        <div
            class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 rounded-full opacity-20 filter blur-xl z-0"
            :class="cashflow.balance >= 0 ? 'bg-green-400' : 'bg-red-400'"
        ></div>
    </div>
</template>
