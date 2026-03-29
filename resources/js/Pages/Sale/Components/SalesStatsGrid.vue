<script setup>
import { computed } from "vue";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
        // DUMMY DATA FOR TESTING
        default: () => ({
            today: { revenue: 15450000, revenue_prev: 12500000, profit: 2540000, profit_prev: 2100000, transactions: 12, transactions_prev: 10, range: "28 Mar 2026" },
            week: { revenue: 84200000, revenue_prev: 78500000, profit: 12400000, profit_prev: 11000000, transactions: 84, transactions_prev: 75, range: "22 - 28 Mar" },
            month: { revenue: 320500000, revenue_prev: 350000000, profit: 45000000, profit_prev: 48000000, transactions: 342, transactions_prev: 360, range: "Maret 2026" },
            year: { revenue: 12400000000, revenue_prev: 10500000000, profit: 1840000000, profit_prev: 1500000000, transactions: 5420, transactions_prev: 4800, range: "Tahun 2026" },
        }),
    },
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const calculateGrowth = (current, previous) => {
    if (!previous || previous === 0) return current > 0 ? 100 : 0;
    return (((current - previous) / previous) * 100).toFixed(1);
};

const stats = computed(() => [
    {
        label: "Penjualan Hari Ini",
        data: props.summary.today,
        icon: "M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z",
        color: "blue",
    },
    {
        label: "7 Hari Terakhir",
        data: props.summary.week,
        icon: "M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z",
        color: "indigo",
    },
    {
        label: "30 Hari Terakhir",
        data: props.summary.month,
        icon: "M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z",
        color: "amber",
    },
    {
        label: "Tahun Ini",
        data: props.summary.year,
        icon: "M13 7h8m0 0v8m0-8l-8 8-4-4-6 6",
        color: "emerald",
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
        <div
            v-for="(stat, idx) in stats"
            :key="idx"
            class="group relative flex flex-col bg-white dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700/50 rounded-2xl lg:rounded-[2rem] shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden"
        >
            <!-- COLOR ACCENT BAR -->
            <div 
                class="absolute left-0 top-0 bottom-0 w-1 lg:w-1.5 transition-all duration-500"
                :class="[
                    stat.color === 'blue' ? 'bg-blue-500 shadow-[0_0_15px_-3px_rgba(59,130,246,0.5)]' :
                    stat.color === 'indigo' ? 'bg-indigo-500 shadow-[0_0_15px_-3px_rgba(99,102,241,0.5)]' :
                    stat.color === 'amber' ? 'bg-amber-500 shadow-[0_0_15px_-3px_rgba(245,158,11,0.5)]' :
                    'bg-emerald-500 shadow-[0_0_15px_-3px_rgba(16,185,129,0.5)]'
                ]"
            ></div>

            <div class="p-3.5 sm:p-4 lg:p-6 pl-4.5 sm:pl-5 lg:pl-8 relative z-10 flex flex-col h-full">
                <!-- Header: Title & Range -->
                <div class="flex items-start justify-between mb-3 lg:mb-5">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[9px] sm:text-[10px] lg:text-xs uppercase font-extrabold tracking-[0.15em] text-gray-500 dark:text-gray-300 mb-0.5 lg:mb-1 truncate">
                            {{ stat.label }}
                        </h3>
                        <p class="text-[9px] sm:text-[10px] lg:text-xs font-semibold text-gray-400 dark:text-gray-600 flex items-center gap-1">
                             <svg class="w-3 h-3opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                             {{ stat.data?.range || '...' }}
                        </p>
                    </div>
                </div>

                <!-- Main Section: Omset (Revenue) -->
                <div class="mb-5 lg:mb-8">
                    <div class="flex items-baseline gap-1 lg:gap-2 mb-1">
                        <span class="text-lg sm:text-xl lg:text-[1.75rem] font-black text-gray-900 dark:text-white tracking-tight leading-none group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                            {{ formatRupiah(stat.data?.revenue || 0).replace('Rp', '') }}
                        </span>
                        <span class="text-[8px] lg:text-[10px] font-black text-gray-400 dark:text-gray-600 uppercase">IDR</span>
                    </div>
                    
                    <!-- Revenue Growth -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <template v-if="stat.data?.revenue_prev !== undefined">
                            <div 
                                class="flex items-center px-2 py-0.5 rounded-md text-[8px] lg:text-[10px] font-black"
                                :class="calculateGrowth(stat.data.revenue, stat.data.revenue_prev) >= 0 
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-500/20' 
                                    : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400 border border-rose-100 dark:border-rose-500/20'"
                            >
                                <svg 
                                    class="w-2.5 h-2.5 mr-0.5" 
                                    :class="calculateGrowth(stat.data.revenue, stat.data.revenue_prev) < 0 ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                                {{ Math.abs(calculateGrowth(stat.data.revenue, stat.data.revenue_prev)) }}%
                            </div>
                            <span class="hidden sm:inline text-[8px] lg:text-[10px] font-bold text-gray-400 dark:text-gray-600 uppercase tracking-tighter self-center">vs periode sebelumnya</span>
                        </template>
                    </div>
                </div>

                <!-- Footer: Extended Metrics (Profit & Transaksi) -->
                <div class="mt-auto flex flex-col gap-3 lg:gap-4 pt-4 lg:pt-6 border-t border-gray-100 dark:border-gray-700/50">
                    <!-- Profit Section -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[8px] lg:text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Estimasi Laba</span>
                            <span class="text-[8px] lg:text-[10px] font-black px-1.5 py-0.5 rounded bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-gray-700">
                                {{ stat.data?.revenue > 0 ? ((stat.data.profit / stat.data.revenue) * 100).toFixed(1) : 0 }}%
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                            <span class="text-[11px] lg:text-sm font-bold text-gray-800 dark:text-gray-200">
                                {{ formatRupiah(stat.data?.profit || 0) }}
                            </span>
                            <div 
                                v-if="stat.data?.profit_prev !== undefined"
                                class="text-[8px] lg:text-[10px] font-black"
                                :class="calculateGrowth(stat.data.profit, stat.data.profit_prev) >= 0 ? 'text-emerald-500' : 'text-rose-500'"
                            >
                                {{ calculateGrowth(stat.data.profit, stat.data.profit_prev) >= 0 ? '▲' : '▼' }} {{ Math.abs(calculateGrowth(stat.data.profit, stat.data.profit_prev)) }}%
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Section -->
                    <div class="flex flex-col gap-1.5 pt-3 border-t border-dotted border-gray-100 dark:border-gray-700/80">
                        <div class="flex items-center justify-between">
                            <span class="text-[8px] lg:text-[10px] font-extrabold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Total Transaksi</span>
                        </div>
                        <div class="flex items-center justify-between gap-1 flex-wrap">
                            <span class="text-[11px] lg:text-sm font-bold text-gray-800 dark:text-gray-200">
                                {{ stat.data?.transactions || 0 }} <span class="text-[9px] font-medium text-gray-400 ml-0.5">Nota</span>
                            </span>
                            <div 
                                v-if="stat.data?.transactions_prev !== undefined"
                                class="text-[8px] lg:text-[10px] font-black"
                                :class="calculateGrowth(stat.data.transactions, stat.data.transactions_prev) >= 0 ? 'text-emerald-500' : 'text-rose-500'"
                            >
                                {{ calculateGrowth(stat.data.transactions, stat.data.transactions_prev) >= 0 ? '▲' : '▼' }} {{ Math.abs(calculateGrowth(stat.data.transactions, stat.data.transactions_prev)) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Mobile adjustments for a more premium & spacious feel */
@media (max-width: 640px) {
    .p-3\.5 { padding: 0.875rem; }
    .pl-4\.5 { padding-left: 1.125rem; }
}

/* Premium shadows & focus effects */
.shadow-sm {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
}

.hover\:shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
}

/* Font Smoothing */
span, p, h3 {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
