<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { ref, watch, computed, onMounted, onUnmounted } from "vue";
import { throttle } from "lodash";
import PurchaseStatsGrid from "./Components/PurchaseStatsGrid.vue";
import PurchaseTransactionList from "./Components/PurchaseTransactionList.vue";
import PurchaseChart from "./Components/PurchaseChart.vue";

const props = defineProps({
    dropdowns: Object, 
    filters: Object,
    purchases: Object,
    summary: Object,
    chartData: Object
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);

// --- TABS LOGIC ---
const activeTab = ref("all"); 
const tabs = [
    { id: "month", label: "Bulan Ini" },
    { id: "year", label: "Tahun Ini" },
    { id: "all", label: "Semua Data" },
];

// Helper Date Range
const getDateRange = (tab) => {
    const today = new Date();
    const formatDate = (date) => date.toISOString().split("T")[0];
    
    if (tab === "month") {
        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
        return { min: formatDate(startOfMonth), max: formatDate(today) };
    }
    if (tab === "year") {
        const startOfYear = new Date(today.getFullYear(), 0, 1);
        return { min: formatDate(startOfYear), max: formatDate(today) };
    }
    return { min: null, max: null }; // All
};

// Handle Tab Change
const setTab = (tabId) => {
    if(activeTab.value == tabId && tabId !== 'all'){
        activeTab.value = 'all';
        params.value.min_date = null;
        params.value.max_date = null;
    } else {
        activeTab.value = tabId;
        const { min, max } = getDateRange(tabId);
        params.value.min_date = min;
        params.value.max_date = max;
    }
    params.value.page = 1;
};

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.purchases.current_page || 1,
    min_date: props.filters.min_date || null,
    max_date: props.filters.max_date || null,
    status: props.filters.status || "",
    supplier_id: props.filters.supplier_id || "",
    user_id: props.filters.user_id || "",
});

// Update activeTab based on filters
const syncTabWithDate = (min, max) => {
    if (!min && !max) {
        activeTab.value = 'all';
        return; 
    }
    const month = getDateRange('month');
    const year = getDateRange('year');

    if (min === month.min && max === month.max) activeTab.value = 'month';
    else if (min === year.min && max === year.max) activeTab.value = 'year';
    else activeTab.value = 'all'; 
};

const initTab = () => {
    syncTabWithDate(params.value.min_date, params.value.max_date);
};
initTab();

watch(() => [params.value.min_date, params.value.max_date], ([min, max]) => {
    syncTabWithDate(min, max);
});

// WATCHERS
const performSearch = throttle(() => {
    const query = Object.fromEntries(
        Object.entries(params.value).filter(([_, v]) => v != null && v !== "")
    );

    router.get(route("purchases.index"), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ["purchases", "filters", "summary"],
    });
}, 300);

watch(params, performSearch, { deep: true });

watch(search, (val) => {
    params.value.search = val;
    params.value.page = 1;

    if (val && val.length > 0) {
        activeTab.value = 'all';
        params.value.min_date = null;
        params.value.max_date = null;
    }
});

// AUTO BLUR PADA SAAT SCROLL
const blurInputs = () => {
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        document.activeElement.blur();
    }
};

onMounted(() => {
    window.addEventListener('scroll', blurInputs, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', blurInputs);
});
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Dashboard Procurement">
        <div class="w-full min-h-screen space-y-6 pb-20">
            
            <!-- 1. Stats Grid -->
            <PurchaseStatsGrid :summary="summary" />

            <!-- 2. Smart Controls: Tabs & Filters -->
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4 shadow-md bg-gray-50/50 dark:bg-gray-900/50 p-2 rounded-2xl border border-gray-100 dark:border-gray-800 backdrop-blur-sm">
                
                <!-- Premium Segmented Tabs -->
                <div class="flex items-center p-1 bg-gray-200/80 dark:bg-gray-800/80 rounded-xl w-full lg:w-auto overflow-x-auto no-scrollbar shadow-inner">
                    <button
                        v-for="tab in tabs.filter(t => t.id !== 'all')"
                        :key="tab.id"
                        @click="setTab(tab.id)"
                        class="px-5 py-2 text-xs lg:text-sm font-bold rounded-lg transition-all duration-300 whitespace-nowrap flex-1 lg:flex-none"
                        :class="
                            activeTab === tab.id
                                ? 'bg-white dark:bg-gray-700 text-lime-600 dark:text-lime-400 shadow-md transform scale-[1.02]'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        {{ tab.label }}
                    </button>
                    <!-- Custom / Search Active Indicator -->
                    <button
                        v-if="activeTab === 'all'"
                        @click="setTab('all')"
                        class="px-5 py-2 text-xs lg:text-sm font-bold rounded-lg bg-white dark:bg-gray-700 text-lime-600 dark:text-lime-400 shadow-md whitespace-nowrap flex-1 lg:flex-none mx-1"
                    >
                        {{ params.search ? 'Hasil Pencarian' : 'Semua Data' }}
                    </button>
                </div>

                <!-- Action Controls (Search & Filter) -->
                <div class="flex items-center gap-3 w-full lg:w-auto">
                    <Filter
                        class="w-full lg:w-[400px]"
                        :filters="filters"
                        v-model="search"
                        @showFilter="showFilterModal = true"
                        :filterCount="0"
                        :actions="[
                            { route: route('purchases.create'), buttonText: '+ RAB Baru' },
                        ]"
                    />
                </div>
            </div>

            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
                :dropdowns="dropdowns"
            />

            <!-- 3. Purchase Trend Chart -->
             <PurchaseChart :data="chartData" :range="chartData?.range" />

            <!-- 4. Content (List) -->
            <div>
                <div class="mb-5 flex items-center justify-between">
                    <div class="flex flex-col">
                        <h2 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">
                            Riwayat Transaksi & Pembelian
                        </h2>
                        <p class="text-xs text-gray-400 font-medium mt-1">Daftar Purchase Order dan Pengadaan Barang</p>
                    </div>
                    <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Total {{ purchases.total }} Data</span>
                    </div>
                </div>

                <PurchaseTransactionList :purchases="purchases.data" />
                
                 <!-- Pagination -->
                 <div v-if="purchases.last_page > 1" class="flex justify-center mt-10 gap-3">
                    <button
                        :disabled="!purchases.prev_page_url"
                        @click="params.page--"
                        class="flex items-center gap-2 px-5 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-bold text-xs text-gray-600 dark:text-gray-300 transition-all hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed group"
                    >
                        <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Prev
                    </button>
                    <div class="flex items-center px-4 py-2 bg-gray-100 dark:bg-gray-800/50 rounded-xl text-[10px] font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest border border-gray-200/50 dark:border-gray-700/50">
                        Hal {{ purchases.current_page }} / {{ purchases.last_page }}
                    </div>
                    <button
                        :disabled="!purchases.next_page_url"
                        @click="params.page++"
                        class="flex items-center gap-2 px-5 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl font-bold text-xs text-gray-600 dark:text-gray-300 transition-all hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed group"
                    >
                        Next
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
