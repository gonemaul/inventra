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
    console.log(items)

    // 1. Top 3 Produk Terlaris (Berdasarkan Omset / Revenue)
    const topRevenue = [...items]
        .sort((a, b) => parseFloat(b.subtotal) - parseFloat(a.subtotal))
        .slice(0, 3);

    // 2. Top 3 Produk Terlaris (Berdasarkan Qty) -- DIKEMBALIKAN SESUAI REQUEST
    const topQty = [...items]
        .sort((a, b) => parseFloat(b.quantity) - parseFloat(a.quantity))
        .slice(0, 3);

    // 3. Top 3 Penyumbang Profit (Berdasarkan Total Profit)
    const topProfit = [...items]
        .sort((a, b) => parseFloat(b.profit) - parseFloat(a.profit))
        .slice(0, 3);

    // 4. Margin Rata-rata Hari Ini
    const margin =
        props.sale.total_revenue > 0
            ? (
                  (props.sale.total_profit / props.sale.total_revenue) *
                  100
              ).toFixed(1)
            : 0;

    return { topRevenue, topQty, topProfit, margin };
});
</script>

<template>
    <Head :title="`Penjualan - ${sale.reference_no}`" />
    <DeleteConfirm ref="showConfirmDelete" @success="" />
    <AuthenticatedLayout :showSidebar="false" :showHeader="false">
        <div
            class="w-full min-h-screen px-4 py-2 print:h-fit print:bg-white print:p-0 sm:px-6 lg:px-8 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
        >
            <!-- Header -->
            <div
                class="flex flex-col items-start justify-between gap-4 mx-auto mb-8 print:hidden md:flex-row"
            >
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <Link
                            :href="route('sales.index')"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                />
                            </svg>
                        </Link>
                        <h1
                            class="text-2xl font-bold text-gray-800 dark:text-white"
                        >
                            Detail Penjualan
                        </h1>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span class="font-mono">{{ sale.reference_no }}</span>
                        <span>&bull;</span>
                        <span>{{ formatDate(sale.transaction_date) }}</span>
                         <span>&bull;</span>
                        <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-green-100 text-green-700"
                            v-if="sale.input_type === 'realtime'"
                        >POS</span>
                         <span class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider bg-blue-100 text-blue-700"
                            v-else
                        >REKAP</span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="deleteRecap"
                        class="px-4 py-2 text-sm font-bold text-red-600 transition bg-red-100 rounded-lg hover:bg-red-200"
                    >
                        Hapus
                    </button>
                    <a
                        :href="`/sales/${sale.id}/print`"
                        target="_blank"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-indigo-600 rounded-lg hover:bg-indigo-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Cetak Struk
                    </a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div
                class="grid grid-cols-1 gap-6 mx-auto max-w-[87rem] lg:grid-cols-3 print:block"
            >
                <div class="space-y-6 lg:col-span-2">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <div class="text-xs font-bold text-gray-500 uppercase">Total Item</div>
                            <div class="text-xl font-black text-gray-800">
                                {{ parseFloat(sale.financial_summary?.total_qty || 0) }}
                                <span class="text-sm font-normal text-gray-400">Unit</span>
                            </div>
                        </div>
                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <div class="text-xs font-bold text-gray-500 uppercase">Input</div>
                            <div class="text-xl font-black text-gray-800 uppercase">
                                {{ sale.payment_method }}
                            </div>
                        </div>
                         <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <div class="text-xs font-bold text-gray-500 uppercase">Profit</div>
                            <div class="text-xl font-black text-green-600">
                                {{ formatCurrency(sale.total_profit) }}
                            </div>
                        </div>
                        <div class="p-4 bg-white border border-gray-200 shadow-sm rounded-xl">
                            <div class="text-xs font-bold text-gray-500 uppercase">Total Bayar</div>
                            <div class="text-xl font-black text-indigo-600">
                                {{ formatCurrency(sale.total_revenue) }}
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-xl">
                        <div class="p-4 border-b border-gray-100 bg-gray-50">
                            <h3 class="font-bold text-gray-700">Rincian Barang</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="text-xs text-gray-500 uppercase bg-gray-50 border-b">
                                        <th class="px-4 py-3">#</th>
                                        <th class="px-4 py-3">Produk</th>
                                        <th class="px-4 py-3 text-right">Harga</th>
                                        <th class="px-4 py-3 text-center">Qty</th>
                                        <th class="px-4 py-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <tr v-for="(item, index) in sale.items" :key="item.id" class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-gray-500 w-10">{{ index + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="font-bold text-gray-800">{{ getProductName(item) }}</div>
                                            <div class="text-xs text-gray-500 font-mono">{{ getProductCode(item) }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono text-sm text-gray-600">
                                            {{ formatCurrency(item.selling_price) }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold text-gray-800">
                                            {{ parseFloat(item.quantity) }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-900">
                                            {{ formatCurrency(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50/50 border-t-2 border-gray-100">
                                     <!-- Diskon Row if Exists -->
                                    <tr v-if="sale.discount_total > 0">
                                        <td colspan="4" class="px-4 py-2 text-right text-sm font-bold text-gray-500 uppercase">
                                            Diskon {{ sale.discount_type == 'percent' ? `(${sale.discount_value}%)` : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-right font-bold text-red-500">
                                            -{{ formatCurrency(sale.discount_total) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="px-4 py-3 text-right text-sm font-black text-gray-700 uppercase">Grand Total</td>
                                        <td class="px-4 py-3 text-right text-lg font-black text-indigo-600">
                                            {{ formatCurrency(sale.total_revenue) }}
                                        </td>
                                    </tr>
                                     <tr v-if="sale.financial_summary?.payment_amount > 0">
                                        <td colspan="4" class="px-4 py-1 text-right text-xs font-bold text-gray-400 uppercase">Tunai / Diterima</td>
                                        <td class="px-4 py-1 text-right text-sm font-bold text-gray-600">
                                            {{ formatCurrency(sale.financial_summary.payment_amount) }}
                                        </td>
                                    </tr>
                                     <tr v-if="sale.financial_summary?.change_amount > 0">
                                        <td colspan="4" class="px-4 py-1 text-right text-xs font-bold text-gray-400 uppercase">Kembalian</td>
                                        <td class="px-4 py-1 text-right text-sm font-bold text-gray-600">
                                            {{ formatCurrency(sale.financial_summary.change_amount) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="space-y-6 print:space-y-3">
                    <!-- 1. Top Omset -->
                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm print:mt-3 rounded-xl"
                    >
                        <h3
                            class="flex items-center gap-2 mb-4 text-sm font-bold tracking-wide text-gray-700 uppercase"
                        >
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            Top 3 Omset Tertinggi
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in analysis.topRevenue"
                                :key="index"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div
                                        class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-bold text-indigo-600 bg-indigo-100 rounded"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div class="truncate">
                                        <Link 
                                            :href="route('products.show', item.product.slug)"
                                            class="text-sm font-bold text-gray-800 hover:text-indigo-600 transition truncate block"
                                            :title="getProductName(item)"
                                        >
                                            {{ getProductName(item) }}
                                        </Link>
                                         <div class="text-xs text-gray-500">
                                            {{ parseFloat(item.quantity) }} {{ item.product_snapshot?.unit }}
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="flex-shrink-0 text-sm font-black text-gray-900"
                                >{{ formatCurrency(item.subtotal) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Top Qty -->
                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                    >
                        <h3
                            class="flex items-center gap-2 mb-4 text-sm font-bold tracking-wide text-gray-700 uppercase"
                        >
                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Top 3 Paling Laris (Qty)
                        </h3>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in analysis.topQty"
                                :key="index"
                                class="flex items-center justify-between"
                            >
                                <div class="flex items-center gap-3 overflow-hidden">
                                    <div
                                        class="flex items-center justify-center flex-shrink-0 w-6 h-6 text-xs font-bold text-orange-600 bg-orange-100 rounded"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div class="truncate">
                                        <Link 
                                            :href="route('products.show', item.product.slug)"
                                            class="text-sm font-bold text-gray-800 hover:text-orange-600 transition truncate block"
                                            :title="getProductName(item)"
                                        >
                                            {{ getProductName(item) }}
                                        </Link>
                                    </div>
                                </div>
                                <div
                                    class="flex-shrink-0 text-sm font-bold text-gray-900"
                                >
                                    {{ parseFloat(item.quantity) }} {{ item.product_snapshot?.unit }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Profit -->
                    <div
                        class="p-5 bg-white border border-gray-200 shadow-sm rounded-xl"
                    >
                        <h3
                            class="flex items-center gap-2 mb-4 text-sm font-bold tracking-wide text-gray-700 uppercase"
                        >
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
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
                                        class="truncate"
                                    >
                                    <Link 
                                            :href="route('products.show', item.product.slug)"
                                            class="text-sm font-bold text-gray-800 hover:text-green-600 transition truncate block"
                                            :title="getProductName(item)"
                                        >
                                            {{ getProductName(item) }}
                                        </Link>
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
