<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    summary: Object, // Data Real dari Backend
});
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatK = (val) => {
    if(val >= 1000000){
        return (val / 1000000).toFixed(1) + " Juta";
    }
    else{
        return formatRupiah(val);
    }
};
// Data Menu (Ditambah fitur Export)
const reportGroups = [
    {
        title: "INVENTORY",
        subtitle: "Stok & Aset",
        color: "text-emerald-600 bg-emerald-50 border-emerald-200", // Icon Box Style
        borderColor: "hover:border-emerald-400",
        items: [
            {
                title: "Kartu Stok (Stock Card)",
                desc: "Audit keluar-masuk barang per item.",
                route: "reports.stock-card",
                canExport: true,
                icon: "📦",
            },
            {
                title: "Valuasi Aset",
                desc: "Total nilai uang dalam bentuk barang.",
                route: "reports.stock-value",
                canExport: true,
                icon: "💰",
            },
            {
                title: "Dead Stock Analysis",
                desc: "Barang macet > 90 hari.",
                route: "reports.dead-stock",
                canExport: false,
                icon: "🕸️",
            },
        ],
    },
    {
        title: "SALES",
        subtitle: "Omzet & Laba",
        color: "text-blue-600 bg-blue-50 border-blue-200",
        borderColor: "hover:border-blue-400",
        items: [
            {
                title: "Laporan Omzet",
                desc: "Rekap pendapatan harian/bulanan.",
                route: "reports.sales-revenue",
                canExport: true,
                icon: "📈",
            },
            {
                title: "Produk Terlaris (Pareto)",
                desc: "20% Barang penyumbang 80% profit.",
                route: "reports.top-products",
                canExport: true,
                icon: "🏆",
            },
            {
                title: "Laba Kotor (Margin)",
                desc: "Analisa keuntungan per transaksi.",
                route: "reports.gross-profit",
                canExport: true,
                icon: "💵",
            },
            {
                title: "Pelanggan Setia",
                desc: "Top customers by revenue.",
                route: "reports.top-customers",
                canExport: true,
                icon: "👑",
            },
        ],
    },
    {
        title: "PROCUREMENT",
        subtitle: "Beli & Hutang",
        color: "text-orange-600 bg-orange-50 border-orange-200",
        borderColor: "hover:border-orange-400",
        items: [
            {
                title: "Pembelian Supplier",
                desc: "Volume belanja per vendor.",
                route: "reports.purchase-supplier",
                canExport: true,
                icon: "🚚",
            },
            {
                title: "Buku Hutang (AP)",
                desc: "Jadwal jatuh tempo tagihan.",
                route: "reports.accounts-payable",
                canExport: true,
                icon: "📜",
            },
            {
                title: "Price Watch",
                desc: "Tren kenaikan harga modal.",
                route: "reports.price-watch",
                canExport: false,
                icon: "⚠️",
            },
        ],
    },
    {
        title: "FINANCE",
        subtitle: "Keuangan Utama",
        color: "text-purple-600 bg-purple-50 border-purple-200",
        borderColor: "hover:border-purple-400",
        items: [
            {
                title: "Laba Rugi (P&L)",
                desc: "Net Profit (Omzet - HPP - Beban).",
                route: "reports.profit-loss",
                canExport: true,
                icon: "📊",
            },
            {
                title: "Arus Kas (Cashflow)",
                desc: "Uang masuk vs keluar riil.",
                route: "reports.cash-flow",
                canExport: true,
                icon: "💸",
            },
            {
                title: "Rincian Pengeluaran",
                desc: "Breakdown biaya operasional.",
                route: "reports.expense-summary",
                canExport: true,
                icon: "🧾",
            },
        ],
    },
];

// Placeholder aksi export (Nanti diintegrasikan ke Backend)
const quickExport = (reportTitle, format) => {
    alert(`Mengekspor ${reportTitle} ke format ${format}...`);
};
</script>

<template>
    <Head title="Executive Dashboard" />

    <AuthenticatedLayout headerTitle="Dashboard & Laporan">
        <div class="min-h-screen pb-20 space-y-10 font-sans">
            
            <!-- SECTION 1: KEY PERFORMANCE INDICATORS (KPI) -->
            <div class="px-4 md:px-0">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-gray-800 dark:text-white">Kinerja Bisnis</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ringkasan performa bulan ini vs bulan lalu</p>
                    </div>
                    <span class="px-3 py-1 text-xs font-bold text-blue-600 bg-blue-50 border border-blue-100 rounded-full dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800">
                        {{ new Date().toLocaleDateString('id-ID', { month: 'long', year: 'numeric' }) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 md:gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- CARD 1: TOTAL ASET (Gradient Blue - Like Before) -->
                    <div class="col-span-2 md:col-span-1 relative p-6 overflow-hidden text-white shadow-xl rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-700 dark:from-blue-800 dark:to-indigo-900 border border-blue-500/30">
                        <div class="relative z-10 flex flex-col justify-between h-full">
                            <div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <svg class="w-5 h-5 text-blue-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    </div>
                                    <span class="text-xs font-bold tracking-widest text-blue-100 uppercase">Total Aset</span>
                                </div>
                                <h3 class="text-2xl md:text-3xl font-black tracking-tight truncate" :title="formatRupiah(summary.total_asset)">
                                    {{ formatK(summary.total_asset) }}
                                </h3>
                            </div>
                            <div class="mt-4 md:mt-6">
                                <p class="text-[10px] md:text-xs text-blue-200">Nilai HPP Stok Gudang</p>
                            </div>
                        </div>
                        <!-- Background decoration -->
                        <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full blur-3xl -mr-10 -mt-10"></div>
                    </div>

                    <!-- CARD 2: REVENUE (With Trend) -->
                    <div class="p-4 md:p-6 transition bg-white border border-gray-200 shadow-sm hover:shadow-md rounded-3xl dark:bg-gray-800 dark:border-gray-700 group">
                        <div class="flex items-start justify-between mb-2 md:mb-4">
                            <div class="hidden md:block p-2 bg-blue-50 rounded-xl dark:bg-blue-900/20">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="md:hidden text-[10px] font-bold text-gray-400 uppercase">Omzet</span>
                            <span class="flex items-center gap-1 text-[10px] md:text-xs font-bold px-2 py-1 rounded-full" 
                                :class="summary.revenue_growth >= 0 ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : 'text-red-600 bg-red-50 dark:bg-red-900/30'">
                                <span v-if="summary.revenue_growth >= 0">▲</span><span v-else>▼</span>
                                {{ Math.abs(summary.revenue_growth) }}%
                            </span>
                        </div>
                        <div>
                            <p class="hidden md:block text-xs font-bold tracking-widest text-gray-400 uppercase">Omzet Bulan Ini</p>
                            <h3 class="mt-1 text-xl md:text-3xl font-black text-gray-800 dark:text-white truncate" :title="formatRupiah(summary.current_revenue)">{{ formatK(summary.current_revenue) }}</h3>
                        </div>
                         <!-- Progress Bar (Compact on mobile) -->
                         <div class="w-full h-1 md:h-1.5 mt-3 md:mt-5 bg-gray-100 rounded-full overflow-hidden dark:bg-gray-700">
                             <div class="h-full bg-blue-600 rounded-full transition-all duration-1000" :style="{ width: Math.min(summary.revenue_progress, 100) + '%' }"></div>
                         </div>
                    </div>

                    <!-- CARD 3: PROFIT (With Trend) -->
                    <div class="p-4 md:p-6 transition bg-white border border-gray-200 shadow-sm hover:shadow-md rounded-3xl dark:bg-gray-800 dark:border-gray-700">
                         <div class="flex items-start justify-between mb-2 md:mb-4">
                            <div class="hidden md:block p-2 bg-purple-50 rounded-xl dark:bg-purple-900/20">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <span class="md:hidden text-[10px] font-bold text-gray-400 uppercase">Laba</span>
                            <span class="flex items-center gap-1 text-[10px] md:text-xs font-bold px-2 py-1 rounded-full" 
                                :class="summary.profit_growth >= 0 ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30' : 'text-red-600 bg-red-50 dark:bg-red-900/30'">
                                <span v-if="summary.profit_growth >= 0">▲</span><span v-else>▼</span>
                                {{ Math.abs(summary.profit_growth) }}%
                            </span>
                        </div>
                         <div>
                            <p class="hidden md:block text-xs font-bold tracking-widest text-gray-400 uppercase">Estimasi Laba Bersih</p>
                            <h3 class="mt-1 text-xl md:text-3xl font-black text-gray-800 dark:text-white truncate" :title="formatRupiah(summary.net_profit)">{{ formatK(summary.net_profit) }}</h3>
                            <p class="mt-1 text-[10px] md:text-sm font-medium text-emerald-600">Margin: {{ summary.net_margin }}%</p>
                        </div>
                    </div>

                     <!-- CARD 4: DEBT (Critical Alert) -->
                     <div class="relative p-4 md:p-6 transition border shadow-sm group rounded-3xl" :class="summary.debt_due_soon > 0 ? 'bg-red-50 border-red-200 dark:bg-red-900/10 dark:border-red-800' : 'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700'">
                        <div class="flex items-start justify-between mb-2 md:mb-4">
                             <div class="hidden md:block p-2 bg-white rounded-xl dark:bg-gray-700 shadow-sm">
                                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="md:hidden text-[10px] font-bold text-gray-400 uppercase">Hutang</span>
                        </div>
                        <div>
                            <p class="hidden md:block text-xs font-bold tracking-widest uppercase" :class="summary.debt_due_soon > 0 ? 'text-red-400' : 'text-gray-400'">Hutang Jatuh Tempo</p>
                            <h3 class="mt-1 text-xl md:text-3xl font-black truncate" :class="summary.debt_due_soon > 0 ? 'text-red-600' : 'text-gray-800 dark:text-white'" :title="formatRupiah(summary.debt_due_soon)">{{ formatK(summary.debt_due_soon) }}</h3>
                            <p v-if="summary.debt_due_soon > 0" class="hidden md:block mt-2 text-xs font-medium text-red-500 animate-pulse">
                                Segera lunasi!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200 dark:border-gray-700 mx-4 md:mx-0 opacity-50">

            <!-- SECTION 2: HEALTH & INSIGHTS (Expandable Lists) -->
            <div class="px-4 md:px-0">
                 <h2 class="mb-6 text-2xl font-black tracking-tight text-gray-800 dark:text-white">Kesehatan Stok</h2>
                 
                 <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                     
                    <!-- Low Stock Alert -->
                    <div class="relative flex flex-col p-6 transition-all duration-300 bg-orange-50 border border-orange-100 shadow-sm rounded-3xl dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-12 h-12 text-orange-600 bg-white rounded-2xl shadow-sm dark:bg-orange-900/30">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">Stok Menipis</h4>
                                    <p class="text-xs text-gray-500">Perlu restock segera</p>
                                </div>
                            </div>
                             <div class="text-right">
                                <span class="text-2xl font-black text-orange-600">{{ summary.low_stock_count }}</span>
                                <span class="block text-[10px] font-bold text-gray-400 uppercase">Item</span>
                            </div>
                        </div>
                        
                        <!-- DETAILS: Expandable on Mobile -->
                        <details class="group/details md:hidden">
                            <summary class="flex items-center justify-between w-full py-2 text-xs font-bold text-orange-600 cursor-pointer select-none border-t border-orange-200/50 mt-2">
                                <span>Lihat Sampel Produk</span>
                                <svg class="w-4 h-4 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            
                            <!-- MOBILE CONTENT -->
                            <div class="pt-2 space-y-2">
                                <div v-for="(item, idx) in summary.low_stock_items" :key="idx" class="flex items-center justify-between p-2 bg-white rounded-xl shadow-sm border border-orange-100">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-1.5 h-1.5 flex-shrink-0 bg-orange-500 rounded-full animate-pulse"></div>
                                        <p class="text-xs font-bold text-gray-800 truncate">{{ item.name }}</p>
                                    </div>
                                    <span class="text-xs font-bold text-red-600 flex-shrink-0 ml-2">{{ item.stock }} {{ item.unit?.name }}</span>
                                </div>
                                <div class="mt-3 text-center">
                                    <Link :href="route('reports.stock-card')" class="text-xs font-bold text-orange-700 underline">Lihat Semua Data</Link>
                                </div>
                            </div>
                        </details>

                        <!-- DESKTOP CONTENT (Always Visible & Cleaner) -->
                         <div class="hidden md:block flex-1 flex flex-col justify-between mt-2">
                             <div class="space-y-3">
                                 <div v-for="(item, idx) in summary.low_stock_items" :key="idx" class="flex items-center justify-between text-sm group/item">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="text-orange-400 opacity-60 group-hover/item:opacity-100">•</span>
                                        <span class="text-gray-600 truncate font-medium group-hover/item:text-orange-700 transition-colors" :title="item.name">{{ item.name }}</span>
                                    </div>
                                    <div class="text-right flex-shrink-0 pl-2">
                                        <span class="font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded">{{ item.stock }} <span class="text-[10px] font-normal text-gray-500">{{ item.unit?.name }}</span></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Link Lihat Semua -->
                             <div class="mt-4 pt-3 border-t border-orange-200/50 text-center">
                                <Link :href="route('reports.stock-card')" class="text-xs font-bold text-orange-600 hover:text-orange-800 hover:underline flex items-center justify-center gap-1 transition-colors">
                                    Lihat Semua Laporan Stok
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Dead Stock Alert -->
                    <div class="relative flex flex-col p-6 transition-all duration-300 bg-gray-50 border border-gray-200 shadow-sm rounded-3xl dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-12 h-12 text-gray-600 bg-white rounded-2xl shadow-sm dark:bg-gray-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                                <div>
                                    <h4 class="text-lg font-bold text-gray-800 dark:text-white">Dead Stock</h4>
                                    <p class="text-sm text-gray-500">> 90 hari mandek</p>
                                </div>
                            </div>
                            <div class="text-right">
                                 <span class="flex items-center justify-end text-lg font-bold text-gray-800 dark:text-white">{{ formatK(summary.dead_stock_value) }}</span>
                                 <span class="block text-[10px] font-bold text-gray-400 uppercase">Potensi Rugi</span>
                            </div>
                        </div>

                         <details class="group/details md:hidden">
                            <summary class="flex items-center justify-between w-full py-2 text-xs font-bold text-gray-500 cursor-pointer select-none border-t border-gray-200 mt-2">
                                <span>Lihat Sampel Produk</span>
                                <svg class="w-4 h-4 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            
                            <div class="pt-2 space-y-2">
                                <div v-for="(item, idx) in summary.dead_stock_items" :key="idx" class="flex items-center justify-between p-2 bg-white rounded-xl shadow-sm border border-gray-100">
                                    <div class="flex items-center gap-3 overflow-hidden">
                                        <div class="w-1.5 h-1.5 flex-shrink-0 bg-gray-400 rounded-full"></div>
                                        <p class="text-xs font-bold text-gray-500 truncate">{{ item.name }}</p>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <span class="block text-xs font-bold text-gray-800">{{ formatK(item.stock * item.purchase_price) }}</span>
                                    </div>
                                </div>
                                 <div class="mt-3 text-center">
                                    <Link :href="route('reports.dead-stock')" class="text-xs font-bold text-gray-600 underline">Lihat Semua Data</Link>
                                </div>
                            </div>
                        </details>

                         <!-- Desktop View -->
                        <div class="hidden md:block flex-1 flex flex-col justify-between mt-2">
                             <div class="space-y-3">
                                <div v-for="(item, idx) in summary.dead_stock_items" :key="idx" class="flex items-center justify-between text-sm group/item">
                                    <div class="flex items-center gap-2 overflow-hidden">
                                        <span class="text-gray-300 group-hover/item:text-gray-500">•</span>
                                        <span class="text-gray-500 truncate font-medium group-hover/item:text-gray-800 transition-colors" :title="item.name">{{ item.name }}</span>
                                    </div>
                                    <div class="text-right leading-none flex-shrink-0 pl-2">
                                        <span class="font-bold text-gray-700 text-xs bg-gray-100 px-2 py-1 rounded">{{ formatK(item.stock * item.purchase_price) }}</span>
                                    </div>
                                </div>
                            </div>
                            <!-- Link Lihat Semua -->
                             <div class="mt-4 pt-3 border-t border-gray-200 text-center">
                                <Link :href="route('reports.dead-stock')" class="text-xs font-bold text-gray-500 hover:text-gray-800 hover:underline flex items-center justify-center gap-1 transition-colors">
                                    Lihat Analisa Dead Stock
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Top Movers (Different Style) -->
                    <div class="p-6 bg-white border border-gray-200 shadow-sm rounded-3xl dark:bg-gray-800 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-800 dark:text-white">🚀 Top Movers</h3>
                            <Link :href="route('reports.top-products')" class="text-xs font-bold text-blue-600 hover:underline">Lihat Semua</Link>
                        </div>
                        
                        <details class="group/details md:hidden">
                            <summary class="flex items-center gap-2 text-xs font-bold text-blue-600 cursor-pointer select-none mb-2">
                                <span class="group-open/details:hidden">Lihat Produk</span>
                                <span class="hidden group-open/details:inline">Tutup</span>
                                <svg class="w-4 h-4 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </summary>
                            <div class="space-y-4 pt-2">
                                 <div v-for="(item, idx) in summary.top_movers" :key="idx" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-blue-500 rounded-full shadow-sm shadow-blue-200">{{ idx + 1 }}</span>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800 truncate dark:text-white w-28 md:w-32" :title="item.name">{{ item.name }}</p>
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-blue-900 bg-blue-50 px-2 py-1 rounded">{{ item.total_qty }}</span>
                                </div>
                            </div>
                        </details>

                        <!-- Desktop View (Always visible) -->
                        <div class="hidden md:block space-y-4">
                            <div v-for="(item, idx) in summary.top_movers" :key="idx" class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg shadow-sm">{{ idx + 1 }}</span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800 truncate dark:text-white w-28 md:w-32" :title="item.name">{{ item.name }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-bold text-gray-800 dark:text-gray-200">{{ item.total_qty }}</span>
                                    <span class="text-[10px] text-gray-400">Terjual</span>
                                </div>
                            </div>
                         </div>
                    </div>
                 </div>
            </div>

             <!-- SECTION 3: NAVIGATION GRID -->
            <div class="px-4 md:px-0">
                <h2 class="mb-6 text-2xl font-black tracking-tight text-gray-800 dark:text-white">Akses Laporan</h2>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="(group, idx) in reportGroups" :key="idx" class="space-y-4">
                        <!-- Group Header -->
                         <div class="flex items-center gap-3 pb-2 border-b border-gray-100 dark:border-gray-800">
                             <div class="w-2 h-2 rounded-full" :class="group.color.split(' ')[0].replace('text-', 'bg-')"></div>
                            <h3 class="text-xs font-black tracking-widest text-gray-500 uppercase">{{ group.title }}</h3>
                         </div>

                         <!-- Items -->
                         <div class="flex flex-col gap-3">
                            <Link v-for="(item, i) in group.items" :key="i" :href="item.route !== '#' ? route(item.route) : '#'" 
                                class="flex items-start gap-4 p-4 transition-all duration-200 bg-white border border-transparent shadow-sm hover:shadow-md rounded-2xl hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700 group ring-1 ring-gray-100 dark:ring-gray-700"
                                :class="[item.route === '#' ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer']">
                                
                                <!-- ICON: Color on Mobile, Grayscale on Desktop (Hover to Color) -->
                                <span class="text-2xl transition-all duration-300 transform md:filter md:grayscale md:group-hover:grayscale-0 group-hover:scale-110">{{ item.icon }}</span>
                                
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-gray-800 transition-colors dark:text-white group-hover:text-blue-600 leading-tight">{{ item.title }}</h4>
                                    <p class="mt-1 text-[10px] text-gray-400 leading-relaxed max-w-[150px]">{{ item.desc }}</p>
                                </div>
                                <div v-if="item.route !== '#'" class="text-gray-300 opacity-0 group-hover:opacity-100 transition-opacity">
                                     <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                </div>
                            </Link>

                         </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
