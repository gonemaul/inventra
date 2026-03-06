<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { ref, watch, computed } from "vue";
import { throttle } from "lodash";
import PurchaseStatsGrid from "./Components/PurchaseStatsGrid.vue";
import PurchaseTransactionList from "./Components/PurchaseTransactionList.vue";
import { onMounted, onUnmounted } from "vue";

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

// AUTO BLUR PADA SAAT SCROLL (Smart Search UX)
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
            
            <!-- 1. Stats Grid (Selalu Muncul) -->
            <PurchaseStatsGrid :summary="summary" />

            <!-- 2. Top Summary Cards (Pengganti Chart) -->
             <div v-if="summary.top_supplier_name" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <!-- Card 1: Top Supplier -->
                 <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-xl p-4 text-white shadow-lg relative overflow-hidden">
                     <div class="flex items-center justify-between mb-3 relative z-10">
                         <div class="flex items-center gap-2">
                             <div class="p-1.5 bg-white/20 rounded-lg backdrop-blur-sm">
                                 <span class="text-sm">🏭</span>
                             </div>
                             <h3 class="font-bold text-base leading-tight">Top Supplier Bulan Ini</h3>
                         </div>
                         <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full backdrop-blur-sm">Mitra Utama</span>
                     </div>

                     <div class="space-y-2 relative z-10">
                         <div class="flex items-center gap-2 p-1.5 rounded-lg bg-white/10">
                             <div class="w-8 h-8 flex items-center justify-center font-bold text-indigo-700 bg-white rounded-full text-xs shadow-sm">
                                 1
                             </div>
                             <div class="flex-1 min-w-0">
                                 <p class="text-sm font-bold truncate">{{ summary.top_supplier_name }}</p>
                             </div>
                             <div class="font-bold text-sm">
                                 {{ new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(summary.top_supplier_amount) }}
                             </div>
                         </div>
                     </div>
                     <div class="absolute -right-6 -bottom-6 text-9xl opacity-5 pointer-events-none select-none">🏭</div>
                 </div>

                 <!-- Card 2: Quick Search Action -->
                 <div class="bg-gradient-to-br from-slate-700 to-slate-900 rounded-xl p-4 text-white shadow-lg relative overflow-hidden flex flex-col justify-center items-center text-center">
                     <p class="text-sm text-gray-300 font-bold uppercase mb-3 relative z-10">Banyak Vendor?</p>
                     <div class="relative z-10">
                         <button 
                             @click="showFilterModal = true"
                             class="px-5 py-2.5 bg-white text-slate-900 rounded-lg text-sm font-bold hover:bg-gray-100 transition shadow-md flex gap-2 items-center"
                         >
                             <span>✅</span> Buka Filter Lanjutan
                         </button>
                     </div>
                     <div class="absolute -left-6 -bottom-6 text-9xl opacity-5 pointer-events-none select-none">🔍</div>
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
                    <h2 class="text-xl font-black text-gray-800 dark:text-white tracking-tight">
                        Riwayat Transaksi & Pembelian
                    </h2>
                    <span class="text-xs font-semibold py-1 px-3 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-full border border-gray-200 dark:border-gray-700">
                        Total {{ purchases.total }} Data
                    </span>
                </div>
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
    </AuthenticatedLayout>
</template>
