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
                        Export CSV
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                </div>
            </div>
            <!-- SUMMARY CARDS (Tetap Sama) -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="relative p-6 overflow-hidden text-white shadow-lg md:col-span-2 bg-gradient-to-br from-gray-800 to-gray-900 dark:from-lime-700 dark:to-lime-950 rounded-2xl"
                >
                    <div class="relative z-10">
                        <p
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Total Nilai Aset (HPP)
                        </p>
                        <h2 class="mt-2 text-3xl font-black">
                            {{ formatRupiah(summary.total_asset_value) }}
                        </h2>
                        <p class="mt-2 text-xs text-gray-400">
                            Uang yang mengendap dalam bentuk
                            {{ summary.total_items }} unit barang.
                        </p>
                    </div>
                    <div class="absolute bottom-0 right-0 p-4 opacity-10">
                        <svg
                            class="w-24 h-24"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Potensi Omzet
                    </p>
                    <h2 class="mt-1 text-2xl font-bold text-blue-600">
                        {{ formatRupiah(summary.potential_revenue) }}
                    </h2>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Jika semua terjual habis
                    </p>
                </div>

                <div
                    class="p-6 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Estimasi Profit
                    </p>
                    <h2 class="mt-1 text-2xl font-bold text-lime-600">
                        {{ formatRupiah(summary.potential_profit) }}
                    </h2>
                    <p class="text-[10px] text-gray-400 mt-1">Margin Kotor</p>
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
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                        >
                            <tr>
                                <th
                                    class="px-6 py-3 cursor-pointer hover:text-lime-600 group"
                                    @click="toggleSort('name')"
                                >
                                    <div class="flex items-center gap-1">
                                        Produk
                                        <span class="text-gray-300 group-hover:text-lime-500" :class="{'text-lime-600': sortKey === 'name'}">
                                            <span v-if="sortKey === 'name'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-center cursor-pointer hover:text-lime-600 group"
                                    @click="toggleSort('stock')"
                                >
                                    <div class="flex items-center justify-center gap-1">
                                        Stok
                                         <span class="text-gray-300 group-hover:text-lime-500" :class="{'text-lime-600': sortKey === 'stock'}">
                                            <span v-if="sortKey === 'stock'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-lime-600 group"
                                    @click="toggleSort('purchase_price')"
                                >
                                    <div class="flex items-center justify-end gap-1">
                                        HPP Satuan
                                        <span class="text-gray-300 group-hover:text-lime-500" :class="{'text-lime-600': sortKey === 'purchase_price'}">
                                            <span v-if="sortKey === 'purchase_price'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 font-bold text-right bg-gray-100 cursor-pointer hover:text-lime-600 dark:bg-gray-900/50 group"
                                    @click="toggleSort('asset_value')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Total Aset (HPP)
                                         <span class="text-gray-300 group-hover:text-lime-500" :class="{'text-lime-600': sortKey === 'asset_value'}">
                                            <span v-if="sortKey === 'asset_value'">{{ sortOrder === 'asc' ? '↑' : '↓' }}</span>
                                            <span v-else>↕</span>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-right cursor-pointer hover:text-lime-600 group"
                                    @click="toggleSort('potential_revenue')"
                                >
                                     <div class="flex items-center justify-end gap-1">
                                        Potensi Jual
                                         <span class="text-gray-300 group-hover:text-lime-500" :class="{'text-lime-600': sortKey === 'potential_revenue'}">
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
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Data tidak ditemukan.
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
