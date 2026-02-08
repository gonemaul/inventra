<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import Pagination from "@/Components/ReportPagination.vue"; 

import { useExport } from "@/Composable/useExport";

const props = defineProps({
    products: Object, // Changed to Object (Paginated)
    filters: Object,
    total_frozen_asset: Number,
});

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.products.data.map(p => ({
        Nama: p.name,
        Stok: p.stock,
        Unit: p.unit?.name,
        'Harga Beli': p.purchase_price,
        'Nilai Aset': p.stock * p.purchase_price,
        'Terakhir Terjual': p.last_sold_at || 'Belum Pernah'
    }));
    exportToCsv('Laporan_Dead_Stock', dataToExport);
};


const form = useForm({
    days: props.filters.days || 90,
});

const applyFilter = () => {
    form.get(route("reports.dead-stock"), { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          })
        : "Belum Pernah";

// Sort Table (Client side sorting removed as we do Server Side now, but logic below was client side only)
// Now we rely on server side default sort order.
</script>

<template>
    <Head title="Dead Stock Analysis" />

    <AuthenticatedLayout headerTitle="Analisa Dead Stock">
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
            <!-- Header Cards -->
            <div
                class="flex flex-col items-center justify-between gap-6 p-6 text-white shadow-lg bg-gradient-to-r from-red-800 to-red-600 rounded-2xl md:flex-row"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 text-2xl font-black tracking-tight uppercase"
                    >
                        <svg
                            class="w-8 h-8"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            ></path>
                        </svg>
                        Uang Mati di Gudang
                    </h2>
                    <p class="max-w-xl mt-1 text-red-100">
                        Barang-barang ini tidak terjual lebih dari
                        <strong>{{ filters.days }} hari</strong>. Segera lakukan
                        tindakan (Diskon/Bundle) agar cashflow berputar kembali.
                    </p>
                </div>
                <div
                    class="text-right bg-white/10 p-4 rounded-xl border border-white/20 backdrop-blur-sm min-w-[200px]"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-red-100 uppercase"
                    >
                        Total Nilai Aset Macet
                    </p>
                    <p class="mt-1 text-3xl font-black">
                        {{ formatRupiah(total_frozen_asset) }}
                    </p>
                     <!-- Total items logic need adjustment if paginated, better usage of products.total -->
                    <p class="mt-1 text-xs text-red-200">
                        {{ products.total }} Jenis Barang
                    </p>
                </div>
            </div>

            <!-- Filter Section -->
            <div
                class="flex flex-col items-center justify-between gap-4 p-4 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 md:flex-row"
            >
                <div class="flex items-center w-full gap-2 md:w-auto">
                    <span class="text-sm font-bold text-gray-500"
                        >Tampilkan barang yang tidak laku selama:</span
                    >
                    <select
                        v-model="form.days"
                        @change="applyFilter"
                        class="text-sm font-bold border-gray-300 rounded-lg focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="30">30 Hari</option>
                        <option value="60">60 Hari</option>
                        <option value="90">90 Hari (Standard)</option>
                        <option value="180">180 Hari (Parah)</option>
                        <option value="365">1 Tahun (Akut)</option>
                    </select>
                </div>
                <div class="text-xs text-gray-400">
                    *Data dihitung berdasarkan transaksi penjualan terakhir.
                </div>
            </div>

            <!-- Table -->
            <div
                class="bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 relative"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase border-b bg-gray-50 dark:bg-gray-700/50 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-4">Produk</th>
                                <th class="px-6 py-4 text-center">
                                    Stok Macet
                                </th>
                                <th class="px-6 py-4 text-right">
                                    Nilai Aset (HPP)
                                </th>
                                <th class="px-6 py-4 text-center">
                                    Terakhir Laku
                                </th>
                                <th class="px-6 py-4 text-center">
                                    Durasi (Hari)
                                </th>
                                <th class="px-6 py-4 text-center">Saran AI</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="item in products.data"
                                :key="item.id"
                                class="transition hover:bg-red-50/30 dark:hover:bg-red-900/10"
                            >
                                <td class="px-6 py-4">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ item.code }} • {{ item.category }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-2 py-1 font-bold text-gray-700 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        {{ item.stock }} {{ item.unit }}
                                    </span>
                                </td>

                                <td
                                    class="px-6 py-4 font-medium text-right text-gray-600 dark:text-gray-300"
                                >
                                    {{ formatRupiah(item.asset_value) }}
                                </td>

                                <td
                                    class="px-6 py-4 text-xs text-center text-gray-500"
                                >
                                    {{ formatDate(item.last_sale_date) }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div
                                        class="inline-flex flex-col items-center"
                                    >
                                        <span
                                            class="text-lg font-black"
                                            :class="
                                                item.days_silent > 180
                                                    ? 'text-red-600'
                                                    : 'text-orange-500'
                                            "
                                        >
                                            {{ item.days_silent }}
                                        </span>
                                        <span
                                            class="text-[10px] uppercase font-bold text-gray-400"
                                            >Hari</span
                                        >
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="px-3 py-1 rounded-full text-[10px] font-bold uppercase border shadow-sm whitespace-nowrap"
                                        :class="{
                                            'bg-red-100 text-red-700 border-red-200':
                                                item.days_silent > 180,
                                            'bg-orange-100 text-orange-700 border-orange-200':
                                                item.days_silent <= 180,
                                            'bg-gray-100 text-gray-600 border-gray-200':
                                                !item.last_sale_date,
                                        }"
                                    >
                                        {{ item.suggestion }}
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center text-green-500"
                                    >
                                        <svg
                                            class="w-12 h-12 mb-2"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                        <h3
                                            class="text-lg font-bold text-gray-800 dark:text-white"
                                        >
                                            Gudang Sehat!
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Tidak ada barang yang macet lebih
                                            dari {{ filters.days }} hari.
                                        </p>
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
        </div>
    </AuthenticatedLayout>
</template>
