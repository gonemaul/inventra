<script setup>
import { ref, watch, computed } from "vue";
import { Head, router, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import debounce from "lodash/debounce";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    insights: Object,
    metrics: Object,
    filters: Object,
});

const toast = usePremiumAlert();

// State for filtering
const search = ref(props.filters.search || "");
const severityFilter = ref(props.filters.severity || "");
const typeFilter = ref(props.filters.type || "");
const sortFilter = ref(props.filters.sort || "date_desc");

// UI State
const isAnalyzing = ref(false);
const showConfirmModal = ref(false);
const showDetailModal = ref(false);
const selectedInsight = ref(null);

// Watch for filter changes and debounce request
watch(
    [search, severityFilter, typeFilter, sortFilter],
    debounce(([searchVal, severityVal, typeVal, sortVal]) => {
        router.get(
            route("reports.smart-insights"),
            {
                search: searchVal,
                severity: severityVal,
                type: typeVal,
                sort: sortVal,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    }, 500)
);

// Constants for UI rendering
const severityMap = {
    critical: { label: "Kritis", class: "bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800" },
    warning: { label: "Waspada", class: "bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800" },
    info: { label: "Info", class: "bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800" },
    safe: { label: "Aman", class: "bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-800" },
};

const typeMap = {
    restock: "Rencana Belanja",
    dead_stock: "Aset Mandek",
    high_margin: "Profit Tinggi",
    margin_alert: "Margin Kritis",
    trend: "Produk Terlaris",
    new: "Produk Baru",
    daily_strategy: "Strategi Harian",
    daily_restock_plan: "Rencana Restock Harian",
    weekly_dss_deadstock: "Mingguan: Deadstock",
    weekly_dss_trending: "Mingguan: Trending",
};

const formatTypeLabel = (type) => {
    return typeMap[type] || type.split("_").map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(" ");
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
};

const getFormattedPayloadData = (type, payload) => {
    if (!payload) return [];
    
    let items = [];
    if (type === 'restock') {
        items.push({ label: 'Sisa Stok', value: `${payload.current_stock || 0} pcs` });
        if (payload.suggested_qty) items.push({ label: 'Saran Beli', value: `${payload.suggested_qty} pcs` });
        if (payload.avg_daily) items.push({ label: 'Rata-rata Terjual', value: `${payload.avg_daily}/hari` });
        if (payload.estimasi_biaya) items.push({ label: 'Estimasi Biaya', value: formatCurrency(payload.estimasi_biaya) });
        if (payload.days_left) items.push({ label: 'Sisa Hari', value: `${payload.days_left} hari lagi` });
    } else if (type === 'dead_stock') {
        items.push({ label: 'Mandek Selama', value: `${payload.days_inactive || 0} hari` });
        if (payload.frozen_asset) items.push({ label: 'Uang Tertahan', value: formatCurrency(payload.frozen_asset) });
    } else if (type === 'margin_alert' || type === 'high_margin') {
        if (payload.percent) items.push({ label: 'Persentase Profit', value: `${payload.percent}%` });
        if (payload.rp) items.push({ label: 'Margin (Rp)', value: formatCurrency(payload.rp) });
    } else if (type === 'trend') {
        items.push({ label: 'Laku Bulan Ini', value: `${payload.qty_this_month || 0} pcs` });
        items.push({ label: 'Laku Bulan Lalu', value: `${payload.qty_last_month || 0} pcs` });
        if (payload.growth_percent) items.push({ label: 'Lonjakan', value: `${payload.growth_percent}%` });
    } else if (type === 'new') {
        items.push({ label: 'Umur Produk', value: `${payload.days_active || 0} hari` });
    } else {
        // Fallback generic
        Object.entries(payload).forEach(([key, val]) => {
            if (typeof val !== 'object') {
                items.push({ label: key.replace(/_/g, ' '), value: val });
            }
        });
    }
    return items;
};

// Handlers

const preTriggerAnalysis = () => {
    showConfirmModal.value = true;
};

const triggerAnalysis = () => {
    showConfirmModal.value = false;
    isAnalyzing.value = true;
    router.post(route('reports.smart-insights.analyze'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Analisa Selesai', 'Data rekomendasi DSS telah diperbarui.');
        },
        onError: () => {
            toast.error('Gagal', 'Terjadi kesalahan saat menjalankan analisa.');
        },
        onFinish: () => {
            isAnalyzing.value = false;
        }
    });
};

const openDetail = (insight) => {
    selectedInsight.value = insight;
    showDetailModal.value = true;
};

const exportData = () => {
    const url = new URL(route("reports.smart-insights.export"));
    if (search.value) url.searchParams.append("search", search.value);
    if (severityFilter.value) url.searchParams.append("severity", severityFilter.value);
    if (typeFilter.value) url.searchParams.append("type", typeFilter.value);
    if (sortFilter.value) url.searchParams.append("sort", sortFilter.value);
    window.location.href = url.toString();
    toast.info('Mengekspor Data', 'File CSV sedang diunduh...');
};

const formatDate = (dateStr) => {
    if (!dateStr) return "-";
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
        minute: "numeric",
        hour: "numeric",
        second: "numeric",
        timeZoneName: "short",
        weekday: "long",
    }).format(date);
};

</script>

<template>
    <Head title="Smart Insights DSS" />

    <AuthenticatedLayout headerTitle="Smart Insights (DSS)">
        <div class="space-y-6">
            
            <!-- HEADER ACTIONS -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center justify-between">
                 <div>
                    <h2 class="text-2xl font-black tracking-tight text-gray-800 dark:text-white">Pusat Rekomendasi AI</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Analisa cerdas sistem untuk stok dan keuangan.</p>
                </div>
                <!-- Buttons -->
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('reports.index')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-blue-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali
                    </Link>
                    <button @click="exportData" class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Ekspor CSV
                    </button>

                    <button @click="preTriggerAnalysis" :disabled="isAnalyzing" class="relative flex items-center justify-center gap-2 px-5 py-2 text-sm font-bold text-white transition-all bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg shadow-md hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed">
                        <template v-if="!isAnalyzing">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Jalankan Analisa Manual
                        </template>
                        <template v-else>
                            <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menganalisa...
                        </template>
                    </button>
                </div>
            </div>

            <!-- OVERVIEW METRICS -->
            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                 <!-- Total Alert -->
                 <div class="p-5 bg-white border border-gray-100 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700">
                    <p class="text-xs font-bold tracking-widest text-gray-400 border-b border-gray-100 dark:border-gray-700 pb-2 mb-2 uppercase">Total Insight</p>
                    <div class="flex items-end justify-between">
                         <h3 class="text-3xl font-black text-gray-800 dark:text-white">{{ metrics.total }}</h3>
                         <div class="p-2 bg-gray-50 rounded-lg dark:bg-gray-700 text-gray-500">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                         </div>
                    </div>
                 </div>

                 <!-- Critical -->
                 <div class="p-5 bg-red-50 border border-red-100 shadow-sm rounded-2xl dark:bg-red-900/10 dark:border-red-800/50">
                    <p class="text-xs font-bold tracking-widest text-red-500 border-b border-red-100 dark:border-red-900/30 pb-2 mb-2 uppercase">Kritis</p>
                    <div class="flex items-end justify-between">
                         <h3 class="text-3xl font-black text-red-700 dark:text-red-400">{{ metrics.critical }}</h3>
                         <div class="p-2 bg-red-100 rounded-lg dark:bg-red-900/50 text-red-600">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                         </div>
                    </div>
                 </div>

                 <!-- Warning -->
                 <div class="p-5 bg-amber-50 border border-amber-100 shadow-sm rounded-2xl dark:bg-amber-900/10 dark:border-amber-800/50">
                    <p class="text-xs font-bold tracking-widest text-amber-600 border-b border-amber-100 dark:border-amber-900/30 pb-2 mb-2 uppercase">Waspada</p>
                    <div class="flex items-end justify-between">
                         <h3 class="text-3xl font-black text-amber-700 dark:text-amber-400">{{ metrics.warning }}</h3>
                         <div class="p-2 bg-amber-100 rounded-lg dark:bg-amber-900/50 text-amber-600">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                         </div>
                    </div>
                 </div>

                 <!-- Info -->
                 <div class="p-5 bg-blue-50 border border-blue-100 shadow-sm rounded-2xl dark:bg-blue-900/10 dark:border-blue-800/50">
                    <p class="text-xs font-bold tracking-widest text-blue-600 border-b border-blue-100 dark:border-blue-900/30 pb-2 mb-2 uppercase">Info Bisnis</p>
                    <div class="flex items-end justify-between">
                         <h3 class="text-3xl font-black text-blue-700 dark:text-blue-400">{{ metrics.info }}</h3>
                         <div class="p-2 bg-blue-100 rounded-lg dark:bg-blue-900/50 text-blue-600">
                             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                         </div>
                    </div>
                 </div>
            </div>

            <!-- FILTERS & DATA TABLE -->
            <div class="bg-white border border-gray-200 shadow-sm rounded-3xl dark:bg-gray-800 dark:border-gray-700 overflow-hidden">
                <!-- Toolbar -->
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col md:flex-row gap-4 justify-between bg-gray-50/50 dark:bg-gray-800/50">
                    
                    <div class="flex-1 max-w-md relative">
                         <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input v-model="search" type="text" placeholder="Cari pesan atau nama produk..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-xl leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition duration-150 ease-in-out dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                    </div>

                    <div class="flex items-center gap-3">
                        <select v-model="severityFilter" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                            <option value="">Semua Status</option>
                            <option value="critical">Kritis (Merah)</option>
                            <option value="warning">Waspada (Kuning)</option>
                            <option value="info">Info (Biru)</option>
                        </select>

                        <select v-model="typeFilter" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                            <option value="">Semua Tipe</option>
                            <option value="restock">Rencana Belanja & Stok Habis</option>
                            <option value="dead_stock">Aset Mandek (Dead Stock)</option>
                            <option value="high_margin">Produk Profit Tinggi</option>
                            <option value="margin_alert">Peringatan Margin Kritis</option>
                            <option value="trend">Laporan Produk Terlaris</option>
                            <option value="new">Laporan Produk Baru</option>
                            <option value="daily_strategy">Laporan Strategi Harian</option>
                            <option value="daily_restock_plan">Rencana Restock Harian</option>
                            <option value="weekly_dss_deadstock">Mingguan: Rekap Deadstock</option>
                            <option value="weekly_dss_trending">Mingguan: Rekap Trending</option>
                        </select>

                        <select v-model="sortFilter" class="block w-full py-2 pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-xl dark:bg-gray-900 dark:border-gray-700 dark:text-white">
                            <option value="date_desc">Terbaru</option>
                            <option value="date_asc">Terlama</option>
                            <option value="severity">Tingkat Urgensi</option>
                        </select>
                    </div>

                </div>

                <!-- Cards Container -->
                <div class="p-4 sm:p-6 bg-gray-50/50 dark:bg-gray-800/50">
                    <div v-if="insights.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div v-for="insight in insights.data" :key="insight.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col gap-4">
                            <!-- Header: Severity, Type, Date -->
                            <div class="flex justify-between items-start">
                                <div class="flex flex-col gap-1">
                                    <span 
                                        class="inline-flex w-fit items-center px-2 py-0.5 rounded-full text-[10px] font-bold border uppercase tracking-wider"
                                        :class="(severityMap[insight.severity] || severityMap.info).class">
                                        {{ (severityMap[insight.severity] || severityMap.info).label }}
                                    </span>
                                    <span class="text-[10px] font-bold tracking-wider text-gray-500 uppercase">{{ formatTypeLabel(insight.type) }}</span>
                                </div>
                                <span class="text-[10px] font-medium text-gray-400 capitalize text-right">{{ formatDate(insight.created_at) }}</span>
                            </div>
                            
                            <!-- Product & Info -->
                            <div class="flex gap-4 items-start">
                                <!-- Image -->
                                <div v-if="insight.product" class="flex-shrink-0 w-16 h-16 bg-gray-50 border border-gray-100 rounded-xl overflow-hidden shadow-sm dark:bg-gray-700 dark:border-gray-600 flex items-center justify-center">
                                    <img v-if="insight.product.image_url" :src="insight.product.image_url" class="w-full h-full object-cover">
                                    <div v-else class="text-gray-300 dark:text-gray-500">
                                        <svg v-if="insight.severity === 'critical'" class="w-8 h-8 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <svg v-else-if="insight.severity === 'warning'" class="w-8 h-8 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                        <svg v-else class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                </div>

                                <!-- Text -->
                                <div class="flex-1 min-w-0">
                                    <Link v-if="insight.product" :href="`/products/${insight.product.slug}/edit`" class="inline-block hover:opacity-80 transition mb-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
                                        <span class="px-2 py-0.5 text-[10px] font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 rounded dark:bg-indigo-900/40 dark:text-indigo-300 dark:border-indigo-800 flex items-center gap-1 w-fit">
                                            📦 {{ insight.product.name }}
                                        </span>
                                    </Link>
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-snug mb-1" :title="insight.title">
                                        {{ insight.title }}
                                    </h4>
                                </div>
                            </div>
                            
                            <!-- Message -->
                            <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 leading-relaxed bg-blue-50/50 dark:bg-gray-900/50 p-2.5 rounded-xl border border-blue-100/50 dark:border-gray-700 line-clamp-3">
                                💡 {{ insight.message }}
                            </p>
                            
                            <!-- Short Payload Info -->
                            <div v-if="insight.payload && Object.keys(insight.payload).length > 0" class="flex flex-wrap gap-1.5 mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
                                <span v-for="(item, idx) in getFormattedPayloadData(insight.type, insight.payload).slice(0, 2)" :key="idx" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                    <span class="text-gray-400 dark:text-gray-500 mr-1">{{ item.label }}:</span> {{ item.value }}
                                </span>
                                <span v-if="getFormattedPayloadData(insight.type, insight.payload).length > 2" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-400 border border-gray-200 dark:bg-gray-700 dark:border-gray-600">...</span>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                                <button @click="openDetail(insight)" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition flex items-center gap-1">
                                    Lihat Detail &rarr;
                                </button>
                                <Link v-if="insight.action_url" :href="insight.action_url" class="px-3 py-1.5 text-[11px] font-bold text-white bg-gray-900 hover:bg-black dark:bg-lime-600 dark:hover:bg-lime-500 rounded-lg shadow-sm transition">
                                    Tindak Lanjuti
                                </Link>
                            </div>
                        </div>
                    </div>
                    
                    <div v-else class="py-20 text-center rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50">
                        <div class="flex flex-col items-center justify-center">
                            <svg class="w-16 h-16 mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            <p class="text-base font-bold text-gray-900 dark:text-white">Tidak Ada Insight Ditemukan</p>
                            <p class="text-sm mt-1 text-gray-500">Sesuaikan filter pencarian atau jalankan analisa ulang.</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="insights.links && insights.links.length > 3" class="px-6 py-4 bg-gray-50 dark:bg-gray-800/80 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-center gap-1">
                        <template v-for="(link, key) in insights.links" :key="key">
                            <div v-if="link.url === null" class="px-3 py-1 text-sm text-gray-400 bg-white border border-gray-200 rounded-md cursor-not-allowed dark:bg-gray-800 dark:border-gray-700" v-html="link.label"></div>
                            <Link v-else :href="link.url" class="px-3 py-1 text-sm transition-colors border rounded-md" :class="link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600 font-bold dark:bg-blue-900/50 dark:border-blue-500 dark:text-blue-300' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-600 dark:hover:bg-gray-700'" v-html="link.label"></Link>
                        </template>
                    </div>
                </div>

            </div>
        </div>

        <!-- Confirm Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-[60] flex items-center justify-center px-4 overflow-y-auto bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="relative w-full max-w-md p-6 bg-white rounded-3xl shadow-xl dark:bg-gray-800">
                <div class="flex flex-col items-center justify-center text-center">
                    <div class="flex items-center justify-center w-16 h-16 mb-4 text-white bg-blue-600 rounded-full shadow-lg shadow-blue-200 dark:shadow-blue-900/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">Jalankan Analisa DSS?</h3>
                    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
                        Sistem akan memindai riwayat penjualan, sisa stok, serta tren harga seluruh produk untuk menghasilkan rekomendasi cerdas terbaru. Ini mungkin memakan waktu beberapa detik.
                    </p>
                    <div class="flex flex-col-reverse w-full gap-3 sm:flex-row">
                        <button @click="showConfirmModal = false" class="w-full px-5 py-2.5 text-sm font-bold text-gray-700 transition-colors bg-white border border-gray-300 rounded-xl hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                            Batal
                        </button>
                        <button @click="triggerAnalysis" class="w-full px-5 py-2.5 text-sm font-bold text-white transition-all bg-blue-600 border border-transparent rounded-xl hover:bg-blue-700 shadow-md">
                            Ya, Analisa Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Bottom Sheet (Standardizado) -->
        <BottomSheet 
            :show="showDetailModal" 
            :title="selectedInsight ? formatTypeLabel(selectedInsight.type) : ''"
            @close="showDetailModal = false"
        >
            <div v-if="selectedInsight" class="flex flex-col h-full"> 
                
                <!-- Body (Scrollable Content already handled by BottomSheet but we add spacing) -->
                <div class="space-y-6">
                    <!-- Top Info (Product & Message) -->
                    <div class="flex flex-col sm:flex-row gap-5">
                        <div v-if="selectedInsight.product" class="flex-shrink-0 w-24 h-24 sm:w-32 sm:h-32 bg-gray-50 border border-gray-100 rounded-2xl overflow-hidden shadow-sm dark:bg-gray-700 dark:border-gray-600 flex items-center justify-center">
                            <img v-if="selectedInsight.product.image_url" :src="selectedInsight.product.image_url" class="w-full h-full object-cover">
                            <div v-else class="text-gray-300 dark:text-gray-500">
                                <svg v-if="selectedInsight.severity === 'critical'" class="w-12 h-12 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <svg v-else-if="selectedInsight.severity === 'warning'" class="w-12 h-12 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <svg v-else class="w-12 h-12 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <template v-if="selectedInsight.product">
                                <p class="text-xs font-bold text-gray-400 mb-1">Target Produk:</p>
                                <Link :href="`/products/${selectedInsight.product.slug}/edit`" class="group inline-flex items-center gap-2 mb-2 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded p-1 -ml-1">
                                    <h4 class="text-xl sm:text-2xl font-black text-gray-900 group-hover:text-blue-600 dark:text-white transition">{{ selectedInsight.product.name }}</h4>
                                    <svg class="w-5 h-5 text-gray-400 opacity-0 group-hover:opacity-100 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </Link>
                            </template>
                            <div class="flex items-center gap-2 mt-1 sm:mt-0">
                                <span 
                                    class="inline-flex items-center px-2 py-0.5 text-[10px] font-bold border rounded-md uppercase tracking-wider"
                                    :class="(severityMap[selectedInsight.severity] || severityMap.info).class">
                                    {{ (severityMap[selectedInsight.severity] || severityMap.info).label }}
                                </span>
                                <h4 class="text-base sm:text-lg font-bold text-gray-800 dark:text-gray-200 leading-tight">{{ selectedInsight.title }}</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Highlight Message -->
                    <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-100 dark:bg-gray-700/50 dark:border-gray-600 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-3 opacity-[0.03] dark:opacity-[0.05]">
                            <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24"><path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7z"/></svg>
                        </div>
                        <p class="text-sm font-medium leading-relaxed text-gray-700 dark:text-gray-300 relative z-10">
                            {{ selectedInsight.message }}
                        </p>
                    </div>

                    <!-- Processed Data Table -->
                    <div v-if="getFormattedPayloadData(selectedInsight.type, selectedInsight.payload).length > 0">
                        <h5 class="mb-3 text-xs font-black tracking-widest text-gray-400 uppercase flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Detail Payload
                        </h5>
                        <div class="grid grid-cols-2 gap-3 pb-2">
                            <div v-for="(item, idx) in getFormattedPayloadData(selectedInsight.type, selectedInsight.payload)" :key="idx" class="flex flex-col p-3 bg-gray-50 border border-gray-100 rounded-xl dark:bg-gray-800/80 dark:border-gray-700">
                                <span class="text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 mb-1">{{ item.label }}</span>
                                <span class="text-sm font-black text-gray-800 dark:text-gray-200">{{ item.value }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer injected into BottomSheet's slot -->
            <template #footer v-if="selectedInsight">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
                    <div class="text-xs text-gray-500 font-medium text-center sm:text-left w-full sm:w-auto">
                       Direkam: <br class="sm:hidden"> {{ formatDate(selectedInsight.created_at) }}
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <button @click="showDetailModal = false" class="w-full sm:w-auto px-5 py-3 sm:py-2.5 text-sm font-bold text-gray-700 bg-gray-100 border border-transparent dark:bg-gray-800 dark:text-gray-300 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-sm">
                            Tutup
                        </button>
                        <Link v-if="selectedInsight.action_url" :href="selectedInsight.action_url" class="flex-1 sm:w-auto px-6 py-3 sm:py-2.5 text-sm font-bold text-white bg-gray-900 hover:bg-black dark:bg-lime-600 dark:hover:bg-lime-500 rounded-xl shadow-md transition whitespace-nowrap text-center inline-flex justify-center items-center">
                            Ambil Tindakan
                        </Link>
                    </div>
                </div>
            </template>
        </BottomSheet>

    </AuthenticatedLayout>
</template>
