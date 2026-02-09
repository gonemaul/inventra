<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { ref, watch, computed } from "vue";
import { throttle } from "lodash";
import PurchaseStatsGrid from "./Components/PurchaseStatsGrid.vue";
import PurchaseTransactionList from "./Components/PurchaseTransactionList.vue";
import SalesChart from "../Sale/Components/SalesChart.vue"; // Reuse Chart

const props = defineProps({
    dropdowns: Object, 
    filters: Object,
    purchases: Object,
    summary: Object
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);

// --- TABS LOGIC ---
const activeTab = ref("all");
const tabs = [
    { id: "all", label: "Semua" },
    { id: "process", label: "Dalam Proses", status: ['draft','dipesan','dikirim','checking'] },
    { id: "completed", label: "Selesai", status: ['selesai'] },
];

// Helper to update params based on tab
const setTab = (tabId) => {
    activeTab.value = tabId;
    // Reset page on tab switch
    params.value.page = 1;

    // Set filter status based on Tab
    if(tabId === 'all') {
        delete params.value.status_in;
    } else {
        const tab = tabs.find(t => t.id === tabId);
        if(tab && tab.status) {
            params.value.status_in = tab.status; // Pass array, backend must handle whereIn or we use loop?
            // Laravel Request params string? better pass comma separated or array.
            // Inertia handles arrays usually. Check standard handling.
            // If backend `PurchaseService` uses `whereIn` for `status_in` key...
            // Need to check backend `PurchaseService` later. 
            // For now assume standard filter or I might need to adjust backend.
            // Let's assume I need to pass specific statuses.
        }
    }
};

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.purchases.current_page || 1,
    // Add tab specific filters if needed
});

// WATCHERS
const performSearch = throttle(() => {
    const query = { ...params.value };
    
    // Clean up
    if(!query.search) delete query.search;

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
});

// COMPUTED
const chartData = computed(() => {
    // Format for SalesChart: { labels: [], values: [] }
    return props.summary?.chart || { labels: [], values: [] };
});
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Dashboard Procurement">
        <div class="w-full min-h-screen space-y-6 pb-20">
            
            <!-- 1. Stats Grid (Selalu Muncul) -->
            <PurchaseStatsGrid :summary="summary" />

            <!-- 2. Chart (Ringkasan Tahunan/Bulanan) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                     <SalesChart 
                        :data="chartData" 
                        title="Grafik Belanja 12 Bulan Terakhir"
                        color="#3b82f6" 
                    />
                </div>
                <!-- Mini Panel: Quick Actions / Supplier Shortcuts? -->
                 <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col justify-center items-center text-center">
                    <p class="text-xs text-gray-500 font-bold uppercase mb-2">Shortcut Filter</p>
                    <div class="flex flex-wrap gap-2 justify-center">
                         <button 
                            @click="showFilterModal = true"
                            class="px-4 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-bold hover:bg-indigo-100 transition"
                        >
                            🔍 Cari per Supplier
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Controls & Tabs -->
             <div class="flex flex-col gap-4 mt-4">
                 <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                     <!-- Tabs -->
                    <div class="flex p-1 space-x-1 bg-gray-100 dark:bg-gray-800 rounded-xl overflow-x-auto w-full md:w-auto">
                        <button
                            v-for="tab in tabs"
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
                    </div>

                    <!-- Filter Bar -->
                     <div class="w-full md:w-auto flex gap-2">
                         <Filter
                            class="w-full"
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
            </div>

            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
                :dropdowns="dropdowns"
            />

            <!-- 4. Content (List) -->
            <div>
                 <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800 dark:text-white">
                        Daftar Transaksi
                    </h2>
                    <span class="text-xs text-gray-500">
                        Total {{ purchases.total }} Data
                    </span>
                </div>

                <PurchaseTransactionList :purchases="purchases.data" />
                
                 <!-- Pagination -->
                 <div v-if="purchases.last_page > 1" class="flex justify-center mt-8 gap-2">
                    <button
                        v-bind="purchases.links[0]"
                        :disabled="!purchases.prev_page_url"
                        @click="params.page--"
                        class="px-4 py-2 bg-white border rounded-lg disabled:opacity-50 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        Prev
                    </button>
                    <span class="px-4 py-2 text-gray-500 text-sm flex items-center">
                        Hal {{ purchases.current_page }} / {{ purchases.last_page }}
                    </span>
                    <button
                        :disabled="!purchases.next_page_url"
                        @click="params.page++"
                        class="px-4 py-2 bg-white border rounded-lg disabled:opacity-50 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        Next
                    </button>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
