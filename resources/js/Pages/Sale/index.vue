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
    min_date: props.filters.min_date || getDateRange('today').min, // Default filter hari ini? User request "Fokus ke hari ini"
    max_date: props.filters.max_date || getDateRange('today').max,
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

    if (min === today.min && max === today.max) activeTab.value = 'today';
    else if (min === week.min && max === week.max) activeTab.value = 'week';
    else if (min === month.min && max === month.max) activeTab.value = 'month';
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

    if (activeTab.value === 'today') {
        revenue = props.summary.best_selling_revenue_today;
        qty = props.summary.best_selling_qty_today;
        chart = props.summary.chart_today;
        label = "Per Jam";
    } else if (activeTab.value === 'week') {
        revenue = props.summary.best_selling_revenue_week;
        qty = props.summary.best_selling_qty_week;
        chart = props.summary.chart_week;
        label = "7 Hari Terakhir";
    } else if (activeTab.value === 'month') {
        revenue = props.summary.best_selling_revenue_month;
        qty = props.summary.best_selling_qty_month;
        chart = props.summary.chart_month;
        label = "Mingguan";
    }
    
    return { revenue, qty, chart, label };
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

    <AuthenticatedLayout headerTitle="Dashboard Penjualan">
        <div class="w-full min-h-screen space-y-6 pb-20">
            <!-- 1. Stats Grid -->
            <SalesStatsGrid :summary="summary" />

            <!-- 2. Controls & Tabs -->
            <div class="flex flex-col gap-4">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <!-- Tabs -->
                    <div class="flex p-1 space-x-1 bg-gray-100 dark:bg-gray-800 rounded-xl overflow-x-auto w-full md:w-auto">
                        <button
                        v-if="activeTab !== 'all'"
                            v-for="tab in tabs.filter(t => t.id !== 'all')"
                            :key="tab.id"
                            @click="setTab(tab.id)"
                            class="px-4 py-2 text-sm font-bold rounded-lg transition-all whitespace-nowrap flex-1"
                            :class="
                                activeTab === tab.id
                                    ? 'bg-white dark:bg-gray-700 text-blue-600 shadow-sm'
                                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400'
                            "
                        >
                            {{ tab.label }}
                        </button>
                        <!-- Custom 'All' / Custom Range Indicator -->
                        <button
                             v-if="activeTab === 'all'"
                             @click="setTab('all')"
                             class="px-4 py-2 text-sm font-bold rounded-lg bg-white dark:bg-gray-700 text-blue-600 shadow-sm whitespace-nowrap"
                        >
                            Custom / Semua
                        </button>
                    </div>

                     <!-- Toggle Deleted -->
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 dark:text-gray-300">
                        <input 
                            type="checkbox" 
                            v-model="params.show_deleted" 
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span>Tampilkan Terhapus</span>
                    </label>
                </div>
                
                <!-- Insight Cards Grid -->
                <!-- Show only if activeTab is one of the standard periods AND data exists -->
                <div v-if="['today', 'week', 'month'].includes(activeTab) && (activePeriodInsight.revenue?.length > 0 || activePeriodInsight.qty?.length > 0)" class="space-y-4">
                     
                    <!-- GRAFIK PENJUALAN -->
                     <SalesChart 
                        :data="activePeriodInsight.chart" 
                        :title="`Grafik Omset (${activePeriodInsight.label})`"
                        color="#84cc16"
                    />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Card 1: Top Omset -->
                        <div class="bg-gradient-to-br from-lime-600 to-green-700 rounded-xl p-4 text-white shadow-lg relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3 relative z-10">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <span class="text-sm">💎</span>
                                    </div>
                                    <h3 class="font-bold text-base leading-tight">Top Omset</h3>
                                </div>
                                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full backdrop-blur-sm">Most Valuable</span>
                            </div>

                            <div class="space-y-2 relative z-10">
                                <Link 
                                    v-for="(product, idx) in activePeriodInsight.revenue" 
                                    :key="'rev-'+product.id"
                                    :href="route('products.show', product.slug)" 
                                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white/10 transition group"
                                >
                                    <div class="w-5 h-5 flex items-center justify-center font-bold text-lime-700 bg-white rounded-full text-xs shadow-sm">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold truncate group-hover:text-lime-200 transition">{{ product.name }}</p>
                                        <p class="text-[10px] text-lime-100/80">{{ product.qty }} items</p>
                                    </div>
                                    <div class="font-bold text-xs">
                                        {{ new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(product.revenue) }}
                                    </div>
                                </Link>
                            </div>
                            
                            <!-- Decoration -->
                            <div class="absolute -right-6 -bottom-6 text-9xl opacity-5 pointer-events-none select-none">💎</div>
                        </div>

                        <!-- Card 2: Top Qty -->
                        <div class="bg-gradient-to-br from-teal-500 to-emerald-600 rounded-xl p-4 text-white shadow-lg relative overflow-hidden">
                            <div class="flex items-center justify-between mb-3 relative z-10">
                                <div class="flex items-center gap-2">
                                    <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <span class="text-sm">📦</span>
                                    </div>
                                    <h3 class="font-bold text-base leading-tight">Terlaris (Qty)</h3>
                                </div>
                                <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full backdrop-blur-sm">Most Popular</span>
                            </div>

                            <div class="space-y-2 relative z-10">
                                <Link 
                                    v-for="(product, idx) in activePeriodInsight.qty" 
                                    :key="'qty-'+product.id"
                                    :href="route('products.show', product.slug)" 
                                    class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-white/10 transition group"
                                >
                                    <div class="w-5 h-5 flex items-center justify-center font-bold text-teal-700 bg-white rounded-full text-xs shadow-sm">
                                        {{ idx + 1 }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold truncate group-hover:text-teal-200 transition">{{ product.name }}</p>
                                    </div>
                                    <div class="font-bold text-xs bg-white/20 px-1.5 py-0.5 rounded">
                                        {{ product.qty }} items
                                    </div>
                                </Link>
                            </div>

                            <!-- Decoration -->
                            <div class="absolute -right-6 -bottom-6 text-9xl opacity-5 pointer-events-none select-none">🔥</div>
                        </div>
                    </div>
                </div>

                 <!-- Search -->
                <Filter
                    class="w-full"
                    :filters="filters"
                    v-model="search"
                    @showFilter="showFilterModal = true"
                    :filterCount="0" 
                    :actions="[
                        { route: route('sales.create'), buttonText: '+ Rekap Manual' },
                    ]"
                />

            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
            />

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
