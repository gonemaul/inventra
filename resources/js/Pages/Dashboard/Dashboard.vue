<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
// Gunakan Layout Utama Anda
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SalesChart from "@/Components/Charts/SalesChart.vue";
import DashboardFinanceWidget from "./DashboardFinanceWidget.vue";
import RecentSalesWidget from "./RecentSalesWidget.vue";
import SmartAssistantWidget from "./SmartAssistantWidget.vue";
import CashflowWidget from "./CashflowWidget.vue";
import ProfitTodayWidget from "./ProfitTodayWidget.vue";

const props = defineProps({
    stats: Object,
    health: Object,
    cashflow: Object,
    insights: Array,
    chart_data: Array,
    recent_sales: Array,
    purchases: Object, // { total_spend_month: 0, count_pending: 0, recent: [] }
    finance: Object, // { total_debt: 0, due_soon_count: 0, recent_bills: [] }
});
console.log(props.health);
// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

// Scale Chart Bar
const maxChartValue = computed(() => {
    const max = Math.max(...props.chart_data.map((d) => d.value));
    return max === 0 ? 1 : max;
});

// Action Handler
const handleInsight = (url) => {
    if (url) router.visit(url);
};
const chartValues = computed(() => props.chart_data.map((d) => d.value));
const chartLabels = computed(() =>
    props.chart_data.map((d) => d.full_date + " | " + d.day)
);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="w-full min-h-screen">
            <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-12">
                <div
                    class="grid grid-cols-1 gap-4 md:col-span-8 sm:grid-cols-2"
                >
                    <!-- <div
                        class="relative p-6 overflow-hidden text-white shadow-lg bg-gradient-to-br from-emerald-600 to-green-500 rounded-2xl group"
                    >
                        <div class="relative z-10">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p
                                        class="mb-1 text-sm font-medium text-green-100"
                                    >
                                        Profit Bersih Hari Ini
                                    </p>
                                    <h2
                                        class="text-3xl font-bold tracking-tight"
                                    >
                                        {{ formatRupiah(stats.profit) }}
                                    </h2>
                                </div>
                                <div
                                    class="p-2 rounded-lg bg-white/20 backdrop-blur-sm"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 mt-4 text-sm text-green-50"
                            >
                                <span
                                    class="bg-white/20 px-2 py-0.5 rounded text-xs font-bold"
                                    >{{ stats.transactions }} Transaksi</span
                                >
                                <span
                                    >Omzet:
                                    {{ formatRupiah(stats.revenue) }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="absolute w-40 h-40 transition duration-700 bg-white rounded-full -right-10 -bottom-10 opacity-10 blur-2xl group-hover:opacity-20"
                        ></div>
                    </div> -->
                    <ProfitTodayWidget :stats="stats" />
                    <CashflowWidget :cashflow="cashflow" />
                </div>
                <div
                    class="relative flex flex-col items-center justify-between h-full p-6 overflow-hidden text-center bg-white border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 md:col-span-4 rounded-2xl"
                >
                    <div class="flex items-start justify-between w-full mb-2">
                        <h3
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Kesehatan Toko
                        </h3>
                        <span
                            v-if="health.score >= 80"
                            class="text-green-500 animate-pulse"
                            >Running Well</span
                        >
                        <span
                            v-else-if="health.score >= 50"
                            class="text-yellow-500"
                            >Need Attention</span
                        >
                        <span v-else class="text-red-500 animate-bounce"
                            >Critical!</span
                        >
                    </div>

                    <div
                        class="relative flex items-center justify-center w-40 h-40 my-2"
                    >
                        <svg
                            class="w-full h-full transform -rotate-90 drop-shadow-md"
                        >
                            <circle
                                cx="80"
                                cy="80"
                                r="70"
                                stroke="currentColor"
                                stroke-width="12"
                                fill="transparent"
                                class="text-gray-100 dark:text-gray-700"
                            />
                            <circle
                                cx="80"
                                cy="80"
                                r="70"
                                stroke="currentColor"
                                stroke-width="12"
                                fill="transparent"
                                :stroke-dasharray="440"
                                :stroke-dashoffset="
                                    440 - (440 * health.score) / 100
                                "
                                stroke-linecap="round"
                                class="transition-all duration-1000 ease-out"
                                :class="
                                    health.score >= 80
                                        ? 'text-green-500'
                                        : health.score >= 50
                                        ? 'text-yellow-500'
                                        : 'text-red-500'
                                "
                            />
                        </svg>

                        <div
                            class="absolute inset-0 flex flex-col items-center justify-center"
                        >
                            <span
                                class="text-5xl font-black tracking-tight"
                                :class="
                                    health.score >= 80
                                        ? 'text-gray-800 dark:text-white'
                                        : health.score >= 50
                                        ? 'text-yellow-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ health.score }}
                            </span>
                            <span class="text-xs font-medium text-gray-400"
                                >POIN</span
                            >
                        </div>
                    </div>

                    <div class="w-full mt-2">
                        <p
                            class="mb-3 text-base font-bold"
                            :class="health.color"
                        >
                            {{ health.status }}
                        </p>

                        <div
                            class="grid grid-cols-2 gap-2 text-[10px] bg-gray-50 dark:bg-gray-700/50 p-2 rounded-lg border border-gray-100 dark:border-gray-600"
                        >
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-400 uppercase"
                                    >Stok</span
                                >
                                <span
                                    class="font-bold text-gray-700 dark:text-gray-200"
                                    >{{
                                        health.details?.stock_score || 0
                                    }}%</span
                                >
                            </div>
                            <div
                                class="flex flex-col border-l border-gray-200 dark:border-gray-600"
                            >
                                <span class="font-bold text-gray-400 uppercase"
                                    >Keuangan</span
                                >
                                <span
                                    class="font-bold text-gray-700 dark:text-gray-200"
                                    >{{
                                        health.details?.finance_score || 0
                                    }}%</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-3">
                <div
                    class="p-6 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 dark:border-gray-700 lg:col-span-2 rounded-2xl"
                >
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 rounded-lg text-lime-600 bg-lime-50 dark:bg-lime-900/20 dark:text-lime-400"
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
                                        d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"
                                    ></path>
                                </svg>
                            </div>

                            <div>
                                <h3
                                    class="text-lg font-bold text-gray-800 dark:text-white"
                                >
                                    Tren Penjualan
                                </h3>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Statistik omzet 7 hari terakhir
                                </p>
                            </div>
                        </div>

                        <div
                            class="px-3 py-1 text-xs font-bold rounded-full text-lime-800 bg-lime-100 dark:bg-lime-900/30 dark:text-lime-300"
                        >
                            Minggu Ini
                        </div>
                    </div>

                    <SalesChart
                        :data="chartValues"
                        :labels="chartLabels"
                        :height="320"
                    />
                </div>
                <SmartAssistantWidget
                    :insights="insights"
                    class="h-full max-h-[500px]"
                />
            </div>
            <RecentSalesWidget :sales="recent_sales" />
            <DashboardFinanceWidget :purchases="purchases" :finance="finance" />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scrollbar Halus untuk Widget Assistant */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
