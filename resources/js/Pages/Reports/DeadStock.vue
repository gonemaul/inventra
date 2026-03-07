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
                        <span class="hidden sm:inline">Export CSV</span>
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span class="hidden sm:inline">Print</span>
                    </button>
                </div>
            </div>
            <!-- Header Cards -->
            <div
                class="relative flex flex-col items-center justify-between gap-6 p-8 text-white shadow-lg overflow-hidden bg-gradient-to-br from-red-600 to-rose-900 border border-red-500 rounded-2xl md:flex-row shadow-red-900/20 group"
            >
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay pointer-events-none"></div>
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl group-hover:opacity-10 transition-opacity duration-700 pointer-events-none"></div>

                <div class="relative z-10 w-full md:w-auto">
                    <h2
                        class="flex items-center gap-3 text-3xl font-black tracking-tight"
                    >
                        <div class="p-2 bg-white/20 rounded-xl backdrop-blur-md">
                            <svg
                                class="w-8 h-8 drop-shadow-sm text-red-100"
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
                        </div>
                        Uang Mati di Gudang
                    </h2>
                    <p class="max-w-xl mt-3 text-red-100/90 leading-relaxed font-medium">
                        Barang-barang ini terdeteksi tidak mengalami pergerakan penjualan lebih dari
                        <strong class="text-white px-1.5 py-0.5 bg-red-800/50 rounded-md">{{ filters.days }} hari</strong>. 
                        Pertimbangkan untuk melakukan cuci gudang atau diskon agar cashflow usaha Anda berputar kembali.
                    </p>
                </div>
                <div
                    class="relative z-10 text-right bg-black/20 p-6 rounded-2xl border border-white/20 backdrop-blur-md min-w-[240px] w-full md:w-auto group-hover:bg-black/30 transition-colors"
                >
                    <div class="flex items-center justify-end gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span>
                        <p
                            class="text-xs font-bold tracking-widest text-red-200 uppercase"
                        >
                            Total Nilai Aset Macet
                        </p>
                    </div>
                    <p class="text-4xl font-black text-white drop-shadow-md">
                        {{ formatRupiah(total_frozen_asset) }}
                    </p>
                    <div class="mt-3 pt-3 border-t border-red-400/30 flex items-center justify-between text-sm text-red-200 font-medium">
                        <span>Total Antrean:</span>
                        <span class="text-white font-bold bg-white/10 px-2 py-0.5 rounded">{{ products.total }} Item</span>
                    </div>
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
                            class="text-[11px] tracking-wider text-gray-500 uppercase border-b bg-gray-50 dark:bg-gray-700/50 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-4 font-bold">Produk</th>
                                <th class="px-6 py-4 font-bold text-center">
                                    Stok Macet
                                </th>
                                <th class="px-6 py-4 font-bold text-right">
                                    Nilai Aset (HPP)
                                </th>
                                <th class="px-6 py-4 font-bold text-center">
                                    Terakhir Laku
                                </th>
                                <th class="px-6 py-4 font-bold text-center">
                                    Durasi (Hari)
                                </th>
                                <th class="px-6 py-4 font-bold text-center">Saran Tindakan</th>
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
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center p-8 bg-emerald-50/50 border border-emerald-100 rounded-3xl dark:bg-emerald-900/10 dark:border-emerald-800 max-w-lg mx-auto shadow-sm"
                                    >
                                        <div class="w-16 h-16 bg-white dark:bg-emerald-800 rounded-full flex items-center justify-center mb-4 shadow-sm border border-emerald-100 dark:border-emerald-700">
                                            <svg
                                                class="w-8 h-8 text-emerald-500"
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
                                        </div>
                                        <h3
                                            class="text-xl font-black text-emerald-800 dark:text-emerald-400 mb-2"
                                        >
                                            Gudang Sehat!
                                        </h3>
                                        <p class="text-sm font-medium text-emerald-600/70 dark:text-emerald-500 text-center leading-relaxed">
                                            Hebat, saat ini tidak terdeteksi adanya barang fisik yang mengendap atau macet di atas ambang batas <strong class="text-emerald-700 dark:text-emerald-300">{{ filters.days }} hari</strong>. Teruskan perputaran stok Anda!
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
