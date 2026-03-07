<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Pagination from "@/Components/ReportPagination.vue"; // Pastikan path sesuai

import { useExport } from "@/Composable/useExport";

const props = defineProps({
    products: Object, // Changed from Array to Object (Paginated)
    summary: Object,
    categories: Array,
    filters: Object,
});

const { exportToCsv } = useExport();

const doExport = () => {
    // Note: Exporting only current page data for now as full data requires backend export or fetch all.
    // Simplifying to export current view logic.
    const dataToExport = props.products.data.map(p => ({
        Nama: p.name,
        Kategori: p.category?.name,
        Stok: p.stock,
        Unit: p.unit?.name,
        'Harga Beli': p.purchase_price,
        'Total Aset': p.stock * p.purchase_price
    }));
    exportToCsv('Laporan_Stok_Value', dataToExport);
};

// ... existing code ...


const form = useForm({
    category_id: props.filters.category_id || "",
    sort: props.filters.sort || "stock",
    direction: props.filters.direction || "desc",
});

const applyFilter = () => {
    // Reset page to 1 on filter change
    router.get(route("reports.stock-value"), { 
        category_id: form.category_id,
        sort: form.sort,
        direction: form.direction
    }, { preserveScroll: true, preserveState: true });
};

// Formatter Rupiah
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Server Side Sorting
const sortKey = ref(props.filters.sort || "stock");
const sortOrder = ref(props.filters.direction || "desc");

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === "asc" ? "desc" : "asc";
    } else {
        sortKey.value = key;
        sortOrder.value = "desc";
    }
    
    // Update Form & Trigger Request
    form.sort = sortKey.value;
    form.direction = sortOrder.value;
    applyFilter();
};

// Watch for category change immediately if preferred, or rely on change event
// watch(() => form.category_id, applyFilter); 
// Note: We use @change on select explicitly.
</script>

<template>
    <Head title="Laporan Nilai Aset" />

    <AuthenticatedLayout headerTitle="Valuasi Aset Gudang">
        <div class="space-y-6 pb-20">
            <!-- Toolbar -->
            <div class="flex flex-col gap-4 mb-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('reports.index')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-blue-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali
                    </Link>
                </div>
                <div class="flex gap-2">
                    <button @click="doExport" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-green-600 rounded-lg shadow hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="hidden sm:inline">Export CSV</span>
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span class="hidden sm:inline">Print</span>
                    </button>
                </div>
            </div>
            <!-- SUMMARY CARDS -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="relative p-6 overflow-hidden text-white shadow-lg md:col-span-2 bg-gradient-to-br from-indigo-900 via-gray-800 to-black dark:from-lime-800 dark:via-lime-900 dark:to-black rounded-2xl group"
                >
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay"></div>
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-white opacity-5 rounded-full blur-3xl group-hover:opacity-10 transition-opacity duration-700"></div>
                    
                    <div class="relative z-10 flex flex-col h-full justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="p-2 bg-white/10 rounded-lg backdrop-blur-sm">
                                    <svg class="w-5 h-5 text-indigo-300 dark:text-lime-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </div>
                                <p class="text-xs font-bold tracking-widest text-indigo-200 dark:text-lime-200 uppercase">
                                    Total Nilai Aset (HPP)
                                </p>
                            </div>
                            <h2 class="text-4xl font-black tracking-tight text-white drop-shadow-md">
                                {{ formatRupiah(summary.total_asset_value) }}
                            </h2>
                        </div>
                        <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4">
                            <p class="text-sm font-medium text-indigo-100/70 dark:text-lime-100/70">
                                Mengendap dalam <span class="text-white font-bold">{{ summary.total_items }}</span> unit barang aktif.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] rounded-2xl dark:bg-gray-800 dark:border-gray-700 relative overflow-hidden group"
                >
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 dark:bg-blue-900/20 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </div>
                            <p class="text-[11px] font-bold tracking-widest text-gray-500 uppercase">
                                Potensi Omzet
                            </p>
                        </div>
                        <h2 class="text-2xl font-black text-gray-800 dark:text-white tracking-tight">
                            {{ formatRupiah(summary.potential_revenue) }}
                        </h2>
                        <div class="mt-2 flex items-center text-[11px] text-gray-400 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mr-1.5"></span>
                            Jika semua terjual habis
                        </div>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-100 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] rounded-2xl dark:bg-gray-800 dark:border-gray-700 relative overflow-hidden group"
                >
                    <div class="absolute right-0 top-0 w-24 h-24 bg-lime-50 dark:bg-lime-900/20 rounded-bl-full -z-0 transition-transform group-hover:scale-110"></div>
                    <div class="relative z-10">
                         <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 rounded-full bg-lime-100 dark:bg-lime-900/50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-lime-600 dark:text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-[11px] font-bold tracking-widest text-gray-500 uppercase">
                                Estimasi Profit
                            </p>
                        </div>
                        <h2 class="text-2xl font-black text-gray-800 dark:text-white tracking-tight">
                            {{ formatRupiah(summary.potential_profit) }}
                        </h2>
                        <div class="mt-2 flex items-center text-[11px] text-gray-400 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-400 mr-1.5"></span>
                            Estimasi Margin Kotor Global
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div
                class="bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 relative"
            >
                <div
                    class="flex flex-col md:flex-row items-center justify-between gap-4 p-4 border-b border-gray-100 dark:border-gray-700"
                >
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                        Rincian Per Produk ({{ products.total }} Items)
                    </h3>

                    <div class="w-full md:w-64">
                        <select
                            v-model="form.category_id"
                            @change="applyFilter"
                            class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 focus:ring-lime-500"
                        >
                            <option value="">Semua Kategori</option>
                            <option
                                v-for="c in categories"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-[11px] tracking-wider text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700"
                        >
                            <tr>
                                <th
                                    class="px-6 py-4 font-bold cursor-pointer hover:text-lime-600 transition-colors group"
                                    @click="toggleSort('name')"
                                >
                                    <div class="flex items-center gap-1">
                                        Produk
                                        <span class="text-gray-300 group-hover:text-lime-500 transition-colors" :class="{'text-lime-600': sortKey === 'name'}">
                                            <span v-if="sortKey === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 font-bold text-center cursor-pointer hover:text-lime-600 transition-colors group"
                                    @click="toggleSort('stock')"
                                >
                                    <div class="flex items-center justify-center gap-1">
                                        Stok
                                         <span class="text-gray-300 group-hover:text-lime-500 transition-colors" :class="{'text-lime-600': sortKey === 'stock'}">
                                            <span v-if="sortKey === 'stock'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 font-bold text-right cursor-pointer hover:text-lime-600 transition-colors group"
                                    @click="toggleSort('purchase_price')"
                                >
                                    <div class="flex items-center justify-end gap-1">
                                        HPP Satuan
                                        <span class="text-gray-300 group-hover:text-lime-500 transition-colors" :class="{'text-lime-600': sortKey === 'purchase_price'}">
                                            <span v-if="sortKey === 'purchase_price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 font-extrabold tracking-widest text-right bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900/50 cursor-pointer hover:text-lime-600 transition-colors group"
                                    @click="toggleSort('asset_value')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Total Aset (HPP)
                                         <span class="text-gray-300 group-hover:text-lime-500 transition-colors" :class="{'text-lime-600': sortKey === 'asset_value'}">
                                            <span v-if="sortKey === 'asset_value'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-4 font-bold text-right cursor-pointer hover:text-lime-600 transition-colors group"
                                    @click="toggleSort('potential_revenue')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Potensi Jual
                                         <span class="text-gray-300 group-hover:text-lime-500 transition-colors" :class="{'text-lime-600': sortKey === 'potential_revenue'}">
                                            <span v-if="sortKey === 'potential_revenue'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="item in products.data" 
                                :key="item.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <td class="px-6 py-3">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ item.code }} •
                                        {{ item.category?.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="px-2 py-1 font-mono font-bold text-gray-700 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ item.stock }} {{ item.unit?.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right text-gray-500">
                                    {{ formatRupiah(item.purchase_price) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-black text-right text-gray-800 bg-gray-50/50 dark:text-white dark:bg-gray-800/50"
                                >
                                    {{ formatRupiah(item.asset_value) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-medium text-right text-blue-600"
                                >
                                    {{ formatRupiah(item.potential_sale) }}
                                </td>
                            </tr>
                            <tr v-if="products.data.length === 0">
                                <td colspan="5" class="py-12">
                                     <div class="flex flex-col items-center justify-center text-center">
                                        <div class="w-16 h-16 mb-4 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center border border-gray-100 dark:border-gray-700 shadow-sm">
                                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                        </div>
                                        <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200 mb-1">Data Kosong</h3>
                                        <p class="text-xs text-gray-500">Tidak ada produk yang sesuai dengan kategori pencarian.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination -->
                <div
                    class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-center md:justify-end"
                >
                     <Pagination :links="products.links" />
                </div>
            </div>
            
             <div
                class="text-xs text-center text-gray-400 mt-4"
            >
                * HPP dihitung berdasarkan harga beli yang tersimpan di master produk.
            </div>
        </div>
    </AuthenticatedLayout>
</template>
