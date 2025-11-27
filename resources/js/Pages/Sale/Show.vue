<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    sale: Object,
});

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

    <!-- <AuthenticatedLayout :showSidebar="false" :showHeader="false"> -->
    <div
        class="w-full min-h-screen px-4 py-8 sm:px-6 lg:px-8 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
    >
        <div
            class="flex flex-col items-start justify-between gap-4 mx-auto mb-8 max-w-[87rem] md:flex-row md:items-center"
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
                    <div class="font-medium text-gray-800 leading-none mt-0.5">
                        {{ sale.user.name }}
                    </div>
                </div>
            </div>
        </div>

        <div
            class="grid grid-cols-1 gap-6 mx-auto max-w-[87rem] lg:grid-cols-3"
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
                            <span class="text-2xl font-black text-gray-800">{{
                                sale.items.length
                            }}</span>
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
                                    <th class="px-4 py-3 text-right">Harga</th>
                                    <th class="px-4 py-3 text-center">Qty</th>
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
                                        {{ formatCurrency(item.selling_price) }}
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

            <div class="space-y-6">
                <div
                    class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
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
                        <div>
                            Dibuat:
                            {{
                                new Date(sale.created_at).toLocaleString(
                                    "id-ID"
                                )
                            }}
                        </div>
                        <!-- <div class="mt-1">Dibuat otomatis</div> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- </AuthenticatedLayout> -->
</template>
