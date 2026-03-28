<script setup>
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { throttle } from "lodash";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import ModalInvoice from "./Components/ModalInvoice.vue";
import BottomSheet from "@/Components/BottomSheet.vue";
import SalesStatsGrid from "./Components/SalesStatsGrid.vue";
import SalesTransactionList from "./Components/SalesTransactionList.vue";

const props = defineProps({
    filters: Object,
    sales: Object,
    summary: Object,
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const showInvoice = ref(false);
const isLoading = ref(false);
const invoiceHtml = ref("");
const selectedId = ref(null);
const selectReference_no = ref("-");

// --- TABS CONFIG ---
const activeTab = ref("today"); // default
const tabs = [
    { id: "today", label: "Hari Ini" },
    { id: "week", label: "Minggu Ini" },
    { id: "month", label: "Bulan Ini" },
    { id: "year", label: "Tahun Ini" },
    { id: "all", label: "Semua Data" },
];

// Helper Date Range
const getDateRange = (tab) => {
    const today = new Date();
    const formatDate = (date) => date.toISOString().split("T")[0];
    
    if (tab === "today") {
        return { min: formatDate(today), max: formatDate(today) };
    }
    if (tab === "week") {
        const last7Days = new Date(today);
        last7Days.setDate(today.getDate() - 6); // 7 Hari Terakhir (termasuk hari ini)
        return { min: formatDate(last7Days), max: formatDate(today) };
    }
    if (tab === "month") {
        const last30Days = new Date(today);
        last30Days.setDate(today.getDate() - 29); // 30 Hari Terakhir
        return { min: formatDate(last30Days), max: formatDate(today) };
    }
    if (tab === "year") {
        const startOfYear = new Date(today.getFullYear(), 0, 1); // 1 Jan Tahun Ini
        return { min: formatDate(startOfYear), max: formatDate(today) };
    }
    return { min: null, max: null }; // All
};

// Handle Tab Change
const setTab = (tabId) => {
    console.log(tabId, activeTab.value)
    if(tabId === 'all' && activeTab.value === 'all'){
        activeTab.value = 'today';
        params.value.min_date = getDateRange('today').min;
        params.value.max_date = getDateRange('today').max;
    }
    else if(activeTab.value == tabId){
        activeTab.value = 'all';
        params.value.min_date = null;
        params.value.max_date = null;
    }else{
        activeTab.value = tabId;
        const { min, max } = getDateRange(tabId);
        params.value.min_date = min;
        params.value.max_date = max;
    }
    // Update params
    params.value.page = 1; // Reset page
};

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.sales.current_page || 1,
    min_date: props.filters.min_date || getDateRange('today').min,
    max_date: props.filters.max_date || getDateRange('today').max,
    min_revenue: props.filters.min_revenue || "",
    max_revenue: props.filters.max_revenue || "",
    show_deleted: props.filters.show_deleted === 'true' || false,
});

// Update activeTab based on filters from URL when mounted
// Cek filter existing untuk set Active Tab
const syncTabWithDate = (min, max) => {
    if (!min && !max) {
         // Fix: If no date filter, usually means 'all' OR default. 
         // But in this logic, clearing filter = 'all'.
         activeTab.value = 'all';
         return; 
    }

    const today = getDateRange('today');
    const week = getDateRange('week');
    const month = getDateRange('month');
    const year = getDateRange('year');

    if (min === today.min && max === today.max) activeTab.value = 'today';
    else if (min === week.min && max === week.max) activeTab.value = 'week';
    else if (min === month.min && max === month.max) activeTab.value = 'month';
    else if (min === year.min && max === year.max) activeTab.value = 'year';
    else activeTab.value = 'all'; // Custom range or All
};

const initTab = () => {
    // Use params.value as it's the reactive state initialized from props.filters
    syncTabWithDate(params.value.min_date, params.value.max_date);
};
initTab();

// Trigger saat URL/Params berubah (Manual Filter -> Tab Update)
watch(() => [params.value.min_date, params.value.max_date], ([min, max]) => {
    syncTabWithDate(min, max);
});


const performSearch = throttle(() => {
    // ... disable pre-loading of summary to avoid heavy queries on search if needed, but for now keep it
    const query = Object.fromEntries(
        Object.entries(params.value).filter(([_, v]) => v != null && v !== "")
    );
    router.get(route("sales.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["sales", "filters", "summary"],
    });
}, 300);

watch(params, performSearch, { deep: true });

// GLOBAL SEARCH LOGIC
watch(search, (val) => {
    params.value.search = val;
    params.value.page = 1;
    
    // Jika user ngetik search, otomatis reset filter tanggal & pindah tab ke All
    if (val && val.length > 0) {
        activeTab.value = 'all';
        delete params.value.min_date;
        delete params.value.max_date;
    }
});

import SalesChart from "./Components/SalesChart.vue";

// Insight Data based on Active Tab
const activePeriodInsight = computed(() => {
    let revenue = [];
    let qty = [];
    let chart = { labels: [], values: [] };
    let label = '';
    let range = '';

    if (activeTab.value === 'today') {
        revenue = props.summary.best_selling_revenue_today;
        qty = props.summary.best_selling_qty_today;
        chart = props.summary.chart_today;
        label = "Per Jam";
        range = props.summary.today.range;
    } else if (activeTab.value === 'week') {
        revenue = props.summary.best_selling_revenue_week;
        qty = props.summary.best_selling_qty_week;
        chart = props.summary.chart_week;
        label = "7 Hari Terakhir";
        range = props.summary.week.range;
    } else if (activeTab.value === 'month') {
        revenue = props.summary.best_selling_revenue_month;
        qty = props.summary.best_selling_qty_month;
        chart = props.summary.chart_month;
        label = "Mingguan";
        range = props.summary.month.range;
    } else if (activeTab.value === 'year') {
        chart = props.summary.chart_year;
        label = "Bulanan";
        range = props.summary.year.range;
    }
    
    return { revenue, qty, chart, label, range };
});

const openInvoice = async (row) => {
    selectedId.value = row.id;
    selectReference_no.value = row.reference_no;
    showInvoice.value = true;
    isLoading.value = true;
    invoiceHtml.value = ""; 

    try {
        const response = await axios.get(`/sales/${row.id}/print`, {
            responseType: "text",
        });

        invoiceHtml.value = response.data; 
    } catch (error) {
        console.error("Gagal load invoice:", error);
        invoiceHtml.value = '<p class="text-center text-red-500 mt-10">Gagal memuat invoice.</p>';
    } finally {
        isLoading.value = false; 
    }
};

// ... (handlePrint sama)
const handlePrint = () => {
    if (!invoiceHtml.value) return;
    const printWindow = window.open("", "", "height=600,width=400");
    printWindow.document.write("<html><head><title>Print</title><style>body { margin: 0; padding: 0; }</style></head><body>");
    printWindow.document.write(invoiceHtml.value);
    printWindow.document.write("</body></html>");
    printWindow.document.close();
    printWindow.focus();
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
};

// Computed for Pagination
const salesData = computed(() => props.sales.data);
const pagination = computed(() => props.sales);
</script>

<template>
    <Head title="Riwayat Penjualan" />

    <FilterModal
        :show="showFilterModal"
        @close="showFilterModal = false"
        :filters="filters"
        @applyFilter="applyFilter"
        @resetFilter="resetFilter"
    />
    <AuthenticatedLayout headerTitle="Dashboard Penjualan">
        <div class="w-full min-h-screen space-y-6 pb-20">
            <!-- 1. Stats Grid -->
            <SalesStatsGrid :summary="summary" />

            <!-- 2. Smart Controls: Tabs & Filters -->
            <div class="flex flex-col justify-between gap-4">
                <!-- Premium Segmented Tabs -->
                <div class="flex items-center p-1 bg-gray-100/80 dark:bg-gray-800/80 rounded-xl w-full lg:w-auto overflow-x-auto no-scrollbar relative shadow-inner">
                    <button
                        v-for="tab in tabs.filter(t => t.id !== 'all')"
                        :key="tab.id"
                        @click="setTab(tab.id)"
                        class="px-5 py-2 text-xs lg:text-sm font-bold rounded-lg transition-all duration-300 whitespace-nowrap flex-1 lg:flex-none relative z-10"
                        :class="
                            activeTab === tab.id
                                ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-md transform scale-[1.02]'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        {{ tab.label }}
                    </button>
                    <!-- Custom / Search Active Indicator -->
                    <button
                        v-if="activeTab === 'all'"
                        @click="setTab('all')"
                        class="px-5 py-2 text-xs lg:text-sm font-bold rounded-lg bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-md whitespace-nowrap flex-1 lg:flex-none mx-1"
                    >
                        {{ params.search ? 'Hasil Pencarian' : 'Custom / Semua' }}
                    </button>
                    <div class="hidden flex-1 lg:flex flex-col md:flex-row items-center gap-3 w-full lg:justify-end lg:w-auto">
                    <!-- Toggle Deleted (Minimalist) -->
                    <label class="hidden md:flex items-center gap-2 cursor-pointer group px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <input 
                            type="checkbox" 
                            v-model="params.show_deleted" 
                            class="w-4 h-4 rounded-md border-gray-300 dark:border-gray-600 text-lime-600 focus:ring-lime-500 dark:bg-gray-700 transition-all cursor-pointer"
                        >
                        <span class="text-[11px] font-bold text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 uppercase tracking-wider transition-colors">Terhapus</span>
                    </label>

                    <!-- Main Filter Component (Integrated) -->
                    <Filter
                        class="w-full lg:w-[450px]"
                        :filters="filters"
                        v-model="search"
                        @showFilter="showFilterModal = true"
                        :filterCount="0"
                    />
                </div>
                </div>

                <!-- Action Controls (Search & Filter) -->
                <div class="lg:hidden flex-1 flex flex-col md:flex-row items-center gap-3 w-full lg:justify-end lg:w-auto">
                    <!-- Toggle Deleted (Minimalist) -->
                    <label class="hidden md:flex items-center gap-2 cursor-pointer group px-3 py-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <input 
                            type="checkbox" 
                            v-model="params.show_deleted" 
                            class="w-4 h-4 rounded-md border-gray-300 dark:border-gray-600 text-lime-600 focus:ring-lime-500 dark:bg-gray-700 transition-all cursor-pointer"
                        >
                        <span class="text-[11px] font-bold text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 uppercase tracking-wider transition-colors">Terhapus</span>
                    </label>

                    <!-- Main Filter Component (Integrated) -->
                    <Filter
                        class="w-full lg:w-[450px]"
                        :filters="filters"
                        v-model="search"
                        @showFilter="showFilterModal = true"
                        :filterCount="0"
                    />
                </div>
                
                <!-- Insight Cards Grid -->
                <!-- Show only if activeTab is one of the standard periods AND data exists -->
                <div v-if="['today', 'week', 'month', 'year'].includes(activeTab) && (activePeriodInsight.revenue?.length > 0 || activePeriodInsight.qty?.length > 0 || activePeriodInsight.chart?.revenues?.length > 0)" class="space-y-4">
                     
                    <!-- GRAFIK PENJUALAN -->
                     <SalesChart 
                        :data="activePeriodInsight.chart" 
                        :title="`Grafik Omset & Laba Bersih (${activePeriodInsight.label})`"
                        :range="activePeriodInsight.range"
                    />

                    <div v-if="activeTab !== 'year'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Card 1: Top Omset -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-0 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                            <!-- Gradient header bar -->
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
                            
                            <div class="p-4 lg:p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner">
                                            <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm">Top Omset</h3>
                                            <p class="text-[10px] text-gray-400">Penyumbang nominal tertinggi</p>
                                        </div>
                                    </div>
                                    <span class="text-[9px] uppercase tracking-widest font-bold bg-gray-100 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 px-2 py-1 rounded-md">
                                        Revenue
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <Link 
                                        v-for="(product, idx) in activePeriodInsight.revenue" 
                                        :key="'rev-'+product.id"
                                        :href="route('products.show', product.slug)" 
                                        class="flex items-center gap-3 group/item border-b border-gray-50 dark:border-gray-700/50 last:border-0 pb-2 last:pb-0"
                                    >
                                        <div class="w-6 h-6 flex-shrink-0 flex items-center justify-center font-bold rounded-lg text-[10px]" :class="idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-500' : idx === 1 ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : idx === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-500' : 'bg-gray-50 text-gray-400 dark:bg-gray-800/50 dark:text-gray-500'">
                                            {{ idx + 1 }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-200 truncate group-hover/item:text-blue-600 dark:group-hover/item:text-blue-400 transition-colors">{{ product.name }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0 flex flex-col items-end">
                                            <span class="font-black text-xs sm:text-sm text-gray-900 dark:text-gray-100">
                                                {{ new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(product.revenue) }}
                                            </span>
                                            <span class="text-[9px] font-medium text-gray-400 bg-gray-50 dark:bg-gray-800 px-1 py-0.5 mt-0.5 rounded">{{ product.qty }} terjual</span>
                                        </div>
                                    </Link>
                                    
                                    <div v-if="!activePeriodInsight.revenue || activePeriodInsight.revenue.length === 0" class="py-6 text-center text-xs text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                        Belum ada data penjualan
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Top Qty -->
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-0 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] border border-gray-100 dark:border-gray-700 relative overflow-hidden group">
                            <!-- Gradient header bar -->
                            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
                            
                            <div class="p-4 lg:p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-inner">
                                            <svg class="w-5 h-5 lg:w-6 lg:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm">Terlaris (Qty)</h3>
                                            <p class="text-[10px] text-gray-400">Paling banyak terjual</p>
                                        </div>
                                    </div>
                                    <span class="text-[9px] uppercase tracking-widest font-bold bg-gray-100 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 px-2 py-1 rounded-md">
                                        Volume
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <Link 
                                        v-for="(product, idx) in activePeriodInsight.qty" 
                                        :key="'qty-'+product.id"
                                        :href="route('products.show', product.slug)" 
                                        class="flex items-center gap-3 group/item border-b border-gray-50 dark:border-gray-700/50 last:border-0 pb-2 last:pb-0"
                                    >
                                        <div class="w-6 h-6 flex-shrink-0 flex items-center justify-center font-bold rounded-lg text-[10px]" :class="idx === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-500' : idx === 1 ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' : idx === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-500' : 'bg-gray-50 text-gray-400 dark:bg-gray-800/50 dark:text-gray-500'">
                                            {{ idx + 1 }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-200 truncate group-hover/item:text-emerald-600 dark:group-hover/item:text-emerald-400 transition-colors">{{ product.name }}</p>
                                        </div>
                                        <div class="text-right flex-shrink-0">
                                            <span class="font-black text-xs sm:text-sm text-gray-900 dark:text-gray-100">
                                                {{ product.qty }} <span class="text-[9px] text-gray-400 font-medium">unit</span>
                                            </span>
                                        </div>
                                    </Link>
                                    
                                    <div v-if="!activePeriodInsight.qty || activePeriodInsight.qty.length === 0" class="py-6 text-center text-xs text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                                        Belum ada data penjualan
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            <!-- 3. Transaction List (The New 'Table') -->
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        Riwayat Transaksi
                    </h2>
                    <span class="text-xs text-gray-500">
                        Total {{ pagination.total }} Data
                    </span>
                </div>

                <SalesTransactionList 
                    :sales="salesData" 
                    @preview-invoice="openInvoice"
                />

                <!-- Pagination Manual -->
                 <div v-if="pagination.last_page > 1" class="flex justify-center mt-8 gap-2">
                    <button
                        v-bind="pagination.links[0]"
                        :disabled="!pagination.prev_page_url"
                        @click="params.page--"
                        class="px-4 py-2 bg-white border rounded-lg disabled:opacity-50 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        Prev
                    </button>
                    <span class="px-4 py-2 text-gray-500 text-sm flex items-center">
                        Hal {{ pagination.current_page }} / {{ pagination.last_page }}
                    </span>
                    <button
                        :disabled="!pagination.next_page_url"
                        @click="params.page++"
                        class="px-4 py-2 bg-white border rounded-lg disabled:opacity-50 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
    </AuthenticatedLayout>
    
    <!-- Invoice Preview Bottom Sheet -->
    <BottomSheet
        :show="showInvoice"
        @close="showInvoice = false"
        :title="`Preview Invoice #` + selectReference_no"
    >
        <div
            v-if="isLoading"
            class="flex-1 flex flex-col items-center justify-center min-h-[300px]"
        >
            <svg
                class="w-10 h-10 mb-3 text-blue-500 animate-spin"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <p class="text-lime-500">Memuat Invoice...</p>
        </div>

        <div v-else class="overflow-hidden">
            <div class="flex-1 mb-4 border rounded overflow-hidden bg-gray-100 h-[300px]">
                <iframe :srcdoc="invoiceHtml" class="w-full h-full bg-white border-0" style="display: block"></iframe>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2 mt-auto border-t">
                <a :href="`/sales/${selectedId}/print`" target="_blank" class="flex items-center justify-center gap-2 py-3 font-bold text-white transition bg-red-500 rounded-lg hover:bg-red-600">
                    📥 Download PDF
                </a>
                <button @click="handlePrint" class="flex items-center justify-center gap-2 py-3 font-bold text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">
                    🖨 Print Struk
                </button>
            </div>
        </div>
    </BottomSheet>
</template>

<style scoped>
/* Reuse styles from original file */
.receipt-preview-wrapper :deep(table) { width: 100% !important; max-width: 100% !important; }
.receipt-preview-wrapper :deep(img) { max-width: 100% !important; height: auto !important; }
.receipt-preview-wrapper :deep(div), .receipt-preview-wrapper :deep(p) { word-wrap: break-word; white-space: normal !important; font-family: "Courier New", Courier, monospace; font-size: 12px; }
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-thumb { background: #ccc; border-radius: 4px; }
</style>
