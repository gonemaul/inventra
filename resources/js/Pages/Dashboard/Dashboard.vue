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
import StoreHealthWidget from "./StoreHealthWidget.vue";

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
                    <ProfitTodayWidget :stats="stats" />
                    <CashflowWidget :cashflow="cashflow" />
                </div>
                <StoreHealthWidget :health="health" />
            </div>

            <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-3">
                <div
                    class="p-6 bg-white border border-gray-200 shadow-sm shadow-gray-200 dark:bg-gray-800 dark:border-gray-700 lg:col-span-2 rounded-2xl"
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
