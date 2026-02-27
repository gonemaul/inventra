<script setup>
import { computed } from "vue";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
        default: () => ({
            sales_today: 0,
            sales_week: 0,
            sales_month: 0,
            count_today: 0,
            period_today: '',
            period_week: '',
            period_month: '',
        }),
    },
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const stats = computed(() => [
    {
        label: "Penjualan Hari Ini",
        period: props.summary.period_today,
        value: formatRupiah(props.summary.sales_today),
        profit: formatRupiah(props.summary.profit_today || 0),
        iconSvg: '<svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
        colorClass: "bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800",
        bgClass: "bg-white border-gray-100 dark:bg-gray-800 dark:border-gray-700",
        textClass: "text-gray-800 dark:text-gray-100 group-hover:text-blue-600",
    },
    {
        label: "7 Hari Terakhir",
        period: props.summary.period_week,
        value: formatRupiah(props.summary.sales_week),
        profit: formatRupiah(props.summary.profit_week || 0),
        iconSvg: '<svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
        colorClass: "bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800",
        bgClass: "bg-white border-gray-100 dark:bg-gray-800 dark:border-gray-700",
        textClass: "text-gray-800 dark:text-gray-100 group-hover:text-indigo-600",
    },
    {
        label: "30 Hari Terakhir",
        period: props.summary.period_month,
        value: formatRupiah(props.summary.sales_month),
        profit: formatRupiah(props.summary.profit_month || 0),
        iconSvg: '<svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
        colorClass: "bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 border border-amber-200 dark:border-amber-800",
        bgClass: "bg-white border-gray-100 dark:bg-gray-800 dark:border-gray-700",
        textClass: "text-gray-800 dark:text-gray-100 group-hover:text-amber-600",
    },
    {
        label: "Transaksi Hari Ini",
        period: props.summary.period_today,
        value: props.summary.count_today + " Nota",
        profit: null,
        iconSvg: '<svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>',
        colorClass: "bg-gray-50 text-gray-600 dark:bg-gray-700/50 dark:text-gray-300 border border-gray-200 dark:border-gray-600",
        bgClass: "bg-white border-gray-100 dark:bg-gray-800 dark:border-gray-700",
        textClass: "text-gray-800 dark:text-gray-100 group-hover:text-gray-600",
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
        <div
            v-for="(stat, idx) in stats"
            :key="idx"
            class="relative p-4 lg:p-5 border rounded-2xl shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] transition hover:shadow-md group overflow-hidden"
            :class="stat.bgClass"
        >
            <div class="flex items-center justify-between mb-3 lg:mb-4">
                <h3 class="text-xs uppercase font-bold tracking-widest text-gray-400 dark:text-gray-500">
                    {{ stat.label }}
                </h3>
                <div
                    class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl flex items-center justify-center transition-colors shadow-sm"
                    :class="stat.colorClass"
                    v-html="stat.iconSvg"
                >
                </div>
            </div>

            <div>
                 <p class="text-xs text-gray-400 dark:text-gray-500 mb-1 lg:hidden">{{ stat.period }}</p>
                <p
                    class="text-xl lg:text-2xl font-black truncate transition-colors"
                    :class="stat.textClass"
                >
                    {{ stat.value }}
                </p>
                <!-- Profit Indicator -->
                <div v-if="stat.profit" class="mt-2 flex items-center gap-1.5 opacity-90">
                    <span class="flex items-center justify-center w-5 h-5 rounded-full bg-emerald-50 dark:bg-emerald-900/30">
                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </span>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Laba: {{ stat.profit }}</span>
                </div>
            </div>
            
             <!-- Decorative -->
             <div class="hidden lg:block mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/50">
                 <p class="text-[11px] text-gray-400 font-medium">{{ stat.period }}</p>
             </div>
        </div>
    </div>
</template>
