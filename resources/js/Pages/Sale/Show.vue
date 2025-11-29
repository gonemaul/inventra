<script setup>
import { ref, computed } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    sale: Object,
});

const printPage = () => window.print();
const showConfirmDelete = ref(null);
// --- HELPERS ---
const formatCurrency = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatNumber = (val) => new Intl.NumberFormat("id-ID").format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });

// Helper Nama Produk
const getProductName = (item) =>
    item.product_snapshot?.name || item.product?.name || "Unknown";
const getProductCode = (item) =>
    item.product_snapshot?.code || item.product?.code || "-";
const currentDate = new Date().toLocaleString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
});
const deleteRecap = () => {
    const config = {
        title: `Hapus rekap penjualan?`,
        message: `Yakin ingin menghapus rekap penjualan ini? Stok produk akan dikembalikan otomatis.`,
        itemName: props.sale.reference_no,
        url: route("sales.destroy", props.sale.id),
    };
    showConfirmDelete.value.open(config);
};

// --- LOGIC ANALISA (COMPUTED) ---
// Ini yang membuat halaman ini pintar. Kita hitung statistik langsung di sini.

const analysis = computed(() => {
    const items = props.sale.items;

    // 1. Top 5 Produk Terlaris (Berdasarkan Qty)
    // Kita copy array dulu ([...items]) biar data asli gak ke-sortir permanen
    const topSelling = [...items]
        .sort((a, b) => parseFloat(b.quantity) - parseFloat(a.quantity))
        .slice(0, 5);

    // 2. Top 5 Penyumbang Profit (Berdasarkan Total Profit)
    const topProfit = [...items]
        .sort((a, b) => parseFloat(b.profit) - parseFloat(a.profit))
        .slice(0, 5);

    // 3. Margin Rata-rata Hari Ini
    const margin =
        props.sale.total_revenue > 0
            ? (
                  (props.sale.total_profit / props.sale.total_revenue) *
                  100
              ).toFixed(1)
            : 0;

    return { topSelling, topProfit, margin };
});
</script>

<template>
    <Head :title="`Laporan - ${sale.reference_no}`" />
    <DeleteConfirm ref="showConfirmDelete" @success="" />
    <AuthenticatedLayout :showSidebar="false" :showHeader="false">
        <div
            class="w-full min-h-screen px-4 py-8 print:bg-white print:p-0 sm:px-6 lg:px-8 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
        >
            <div
                class="flex flex-col items-start print:hidden justify-between gap-4 mx-auto mb-8 max-w-[87rem] md:flex-row md:items-center"
            >
                <div>
                    <div
                        class="flex items-center gap-2 mb-1 text-sm text-gray-500 dark:text-gray-300"
                    >
                        <Link
                            :href="route('sales.index')"
                            class="flex items-center transition hover:text-indigo-600"
                        >
                            <svg
                                class="w-4 h-4 mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                ></path>
                            </svg>
                            Riwayat
                        </Link>
                        <span>/</span>
                        <span>Detail Transaksi</span>
                    </div>
                    <h1
                        class="flex items-center gap-2 text-2xl font-bold text-gray-800 dark:text-gray-300"
                    >
                        {{ formatDate(sale.transaction_date) }}
                        <span
                            class="px-2 py-1 text-sm font-medium text-gray-500 bg-white border border-gray-200 rounded dark:text-gray-800"
                        >
                            {{ sale.reference_no }}
                        </span>
                    </h1>
                </div>
                <div class="flex flex-col gap-2">
                    <div
                        class="flex items-center gap-3 p-2 pr-4 bg-white border border-gray-100 rounded-full shadow-sm"
                    >
                        <div
                            class="flex items-center justify-center w-8 h-8 text-xs font-bold text-indigo-600 bg-indigo-100 rounded-full"
                        >
                            {{ sale.user.name.substring(0, 2).toUpperCase() }}
                        </div>
                        <div class="text-sm">
                            <div
                                class="text-gray-500 text-[10px] uppercase font-bold leading-none"
                            >
                                Diinput Oleh
                            </div>
                            <div
                                class="font-medium text-gray-800 leading-none mt-0.5"
                            >
                                {{ sale.user.name }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="printPage"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <svg
                                class="w-4 h-4 mr-2 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                ></path>
                            </svg>
                            Cetak Laporan
                        </button>

                        <button
                            @click="deleteRecap"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-red-700 transition border border-red-200 rounded-lg shadow-sm bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
                            </svg>
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
            <div class="hidden pb-4 mb-6 border-b border-gray-300 print:block">
                <h1 class="text-2xl font-bold text-gray-900">
                    Laporan Penjualan Harian
                </h1>
                <div class="flex justify-between mt-2 text-sm text-gray-600">
                    <div>Tanggal: {{ formatDate(sale.transaction_date) }}</div>
                    <div>No. Ref: {{ sale.reference_no }}</div>
                </div>
            </div>
            <div
                class="grid grid-cols-1 gap-6 mx-auto max-w-[87rem] lg:grid-cols-3 print:block"
            >
                <div class="space-y-6 lg:col-span-2">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div
                            class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                        >
                            <div
                                class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Total Omset
                            </div>
                            <div class="text-2xl font-black text-indigo-600">
                                {{ formatCurrency(sale.total_revenue) }}
                            </div>
                        </div>
                        <div
                            class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <div
                                    class="text-xs font-bold tracking-wider text-gray-400 uppercase"
                                >
                                    Laba Bersih
                                </div>
                                <span
                                    class="bg-green-100 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full"
                                >
                                    {{ analysis.margin }}% Margin
                                </span>
                            </div>
                            <div class="text-2xl font-black text-green-600">
                                + {{ formatCurrency(sale.total_profit) }}
                            </div>
                        </div>
                        <div
                            class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                        >
                            <div
                                class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Volume Penjualan
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span
                                    class="text-2xl font-black text-gray-800"
                                    >{{ sale.items.length }}</span
                                >
                                <span class="text-sm font-medium text-gray-500"
                                    >Jenis Barang</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl"
                    >
                        <div
                            class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50"
                        >
                            <h3 class="font-bold text-gray-700">
                                Rincian Barang Keluar
                            </h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead
                                    class="text-xs text-gray-500 uppercase bg-gray-200 border-b border-gray-100"
                                >
                                    <tr>
                                        <th class="px-6 py-3">Produk</th>
                                        <th class="px-4 py-3 text-right">
                                            Harga
                                        </th>
                                        <th class="px-4 py-3 text-center">
                                            Qty
                                        </th>
                                        <th class="px-4 py-3 text-right">
                                            Subtotal
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-green-600"
                                        >
                                            Laba
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr
                                        v-for="item in sale.items"
                                        :key="item.id"
                                        class="hover:bg-gray-50/50"
                                    >
                                        <td
                                            class="px-6 py-3 font-medium text-gray-900"
                                        >
                                            {{ getProductName(item) }}
                                            <div
                                                class="font-mono text-xs font-normal text-gray-400"
                                            >
                                                {{ getProductCode(item) }}
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono text-right text-gray-500"
                                        >
                                            {{
                                                formatCurrency(
                                                    item.selling_price
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 font-bold text-center text-gray-400"
                                        >
                                            {{ parseFloat(item.quantity) }}
                                            <span
                                                class="text-xs font-normal text-gray-400"
                                                >{{
                                                    item.product_snapshot?.unit
                                                }}</span
                                            >
                                        </td>
                                        <td
                                            class="px-4 py-3 font-mono font-bold text-right text-gray-800"
                                        >
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                        <td
                                            class="px-6 py-3 font-mono font-bold text-right text-green-600"
                                        >
                                            {{ formatCurrency(item.profit) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6 print:space-y-3">
                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm print:mt-3 rounded-xl"
                    >
                        <h3
                            class="flex items-center gap-2 mb-4 text-sm font-bold tracking-wide text-gray-700 uppercase"
                        >
                            <svg
                                class="w-4 h-4 text-orange-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                ></path>
                            </svg>
                            Produk Terlaris (Qty)
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in analysis.topSelling"
                                :key="index"
                                class="flex items-center justify-between"
                            >
                                <div
                                    class="flex items-center gap-3 overflow-hidden"
                                >
                                    <div
                                        class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-bold text-orange-600 bg-orange-100 rounded"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div
                                        class="text-sm font-medium text-gray-700 truncate"
                                        :title="getProductName(item)"
                                    >
                                        {{ getProductName(item) }}
                                    </div>
                                </div>
                                <div
                                    class="flex-shrink-0 text-sm font-bold text-gray-900"
                                >
                                    {{ parseFloat(item.quantity) }}
                                    {{ item.product_snapshot?.unit }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                    >
                        <h3
                            class="flex items-center gap-2 mb-4 text-sm font-bold tracking-wide text-gray-700 uppercase"
                        >
                            <svg
                                class="w-4 h-4 text-green-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            Penyumbang Profit
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in analysis.topProfit"
                                :key="index"
                                class="flex items-center justify-between"
                            >
                                <div
                                    class="flex items-center gap-3 overflow-hidden"
                                >
                                    <div
                                        class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-bold text-green-600 bg-green-100 rounded"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div
                                        class="text-sm font-medium text-gray-700 truncate"
                                    >
                                        {{ getProductName(item) }}
                                    </div>
                                </div>
                                <div
                                    class="flex-shrink-0 font-mono text-sm font-bold text-green-600"
                                >
                                    {{ formatCurrency(item.profit) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                    >
                        <h3
                            class="mb-2 text-xs font-bold tracking-wide text-gray-700 uppercase"
                        >
                            Catatan Shift
                        </h3>
                        <div
                            class="bg-gray-100 rounded-lg p-3 text-sm text-gray-600 italic border border-gray-200 min-h-[60px]"
                        >
                            "{{ sale.notes || "Tidak ada catatan khusus." }}"
                        </div>

                        <div
                            class="pt-4 mt-4 text-xs text-gray-400 border-t border-gray-200"
                        >
                            <h4
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-3"
                            >
                                Informasi Dokumen
                            </h4>
                            <div class="flex justify-between">
                                <div>
                                    Dibuat:
                                    {{
                                        new Date(
                                            sale.created_at
                                        ).toLocaleString("id-ID")
                                    }}
                                </div>
                                <div class="hidden print:block">
                                    Dicetak:
                                    {{ currentDate }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
