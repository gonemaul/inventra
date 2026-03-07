<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import ProductSearchSelect from "@/Components/ProductSearchSelect.vue";

const props = defineProps({
    filters: Object,
    products: Array, // Empty now
    reportData: Object, // { product, opening_stock, movements }
});
const { isActionLoading } = useActionLoading();
const form = useForm({
    product_id: props.filters.product_id || "",
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    if (!form.product_id) return; // Prevent empty search
    isActionLoading.value = true;
    form.get(route("reports.stock-card"), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isActionLoading.value = false;
        },
    });
};

// --- LOGIC SALDO BERJALAN (CLIENT SIDE) ---
const tableRows = computed(() => {
    if (!props.reportData) return [];

    let currentBalance = props.reportData.opening_stock;

    // Map mutasi menjadi baris tabel dengan saldo berjalan
    return props.reportData.movements.map((mov) => {
        currentBalance += mov.quantity; // Tambah/Kurang stok
        return {
            ...mov,
            balance_after: currentBalance,
        };
    });
});

// Format Tanggal & Jam
const formatDateTime = (dateStr) => {
    return new Date(dateStr).toLocaleString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Helper Warna Badge Tipe
const getTypeBadge = (type) => {
    const map = {
        initial: "bg-gray-100 text-gray-600",
        purchase: "bg-green-100 text-green-700",
        sale: "bg-blue-100 text-blue-700",
        adjustment_in: "bg-yellow-100 text-yellow-700",
        adjustment_out: "bg-red-100 text-red-700",
        return_in: "bg-purple-100 text-purple-700",
    };
    return map[type] || "bg-gray-100";
};
</script>

<template>
    <Head title="Kartu Stok" />

    <AuthenticatedLayout headerTitle="Laporan & Audit">
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('reports.index')"
                    class="p-2 transition rounded-full hover:bg-gray-200 dark:hover:bg-gray-700"
                >
                    <svg
                        class="w-6 h-6 text-gray-600 dark:text-gray-300"
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
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Kartu Stok
                </h2>
            </div>
            <div class="flex gap-2">
                <button @click="doExport" v-if="product" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-green-600 rounded-lg shadow hover:bg-green-700" title="Export CSV">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span class="hidden sm:inline">Export CSV</span>
                </button>
                <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700" title="Print">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    <span class="hidden sm:inline">Print</span>
                </button>
            </div>
        </template>
        
        <div class="space-y-6">
            <div
                class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <h3
                    class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                >
                    🔍 Filter Kartu Stok
                </h3>

                <div class="grid items-end grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Pilih Produk</label
                        >
                        <!-- Unified Search Component -->
                        <ProductSearchSelect 
                            v-model="form.product_id"
                            :initialProduct="reportData?.product"
                            placeholder="Ketik Kode / Nama Produk..."
                            @change="applyFilter" 
                        />
                    </div>

                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Dari Tanggal</label
                        >
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                        />
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label
                                class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                                >Sampai</label
                            >
                            <input
                                type="date"
                                v-model="form.end_date"
                                class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <button
                            @click="applyFilter"
                            :disabled="form.processing || !form.product_id"
                            class="flex items-center justify-center px-4 py-2 mt-auto font-bold text-white transition rounded-lg shadow-md bg-lime-500 hover:bg-lime-600 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Tampilkan
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="reportData"
                class="overflow-hidden bg-white border border-gray-100 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-6 gap-4 border-b border-gray-100 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 relative overflow-hidden"
                >
                    <!-- Background Decoration -->
                    <div class="absolute -right-4 -top-4 w-32 h-32 bg-blue-500/10 dark:bg-blue-400/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10">
                        <h2
                            class="text-2xl font-black text-gray-800 dark:text-white"
                        >
                            {{ reportData.product.name }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ reportData.product.code }} •
                            {{ reportData.product.category?.name }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Saldo Akhir Periode
                        </p>
                        <p
                            class="text-3xl font-black text-lime-600 dark:text-lime-400"
                        >
                            {{
                                tableRows.length > 0
                                    ? tableRows[tableRows.length - 1]
                                          .balance_after
                                    : reportData.opening_stock
                            }}
                            <span class="text-sm font-normal text-gray-400">{{
                                reportData.product.unit?.name
                            }}</span>
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-[11px] tracking-wider text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 border-b border-gray-100 dark:border-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-4 font-bold">Waktu</th>
                                <th class="px-6 py-4 font-bold">Tipe & Ref</th>
                                <th class="px-6 py-4 font-bold">Keterangan</th>
                                <th
                                    class="px-6 py-4 font-bold text-center text-emerald-600"
                                >
                                    Masuk
                                </th>
                                <th class="px-6 py-4 font-bold text-center text-red-600">
                                    Keluar
                                </th>
                                <th class="px-6 py-4 font-black text-right">
                                    Saldo
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                class="font-medium bg-yellow-50 dark:bg-yellow-900/10"
                            >
                                <td
                                    class="px-6 py-3 text-yellow-700"
                                    colspan="3"
                                >
                                    ➡️ Saldo Awal (Sebelum
                                    {{
                                        formatDateTime(form.start_date).split(
                                            ","
                                        )[0]
                                    }})
                                </td>
                                <td colspan="2"></td>
                                <td
                                    class="px-6 py-3 font-bold text-right text-yellow-700"
                                >
                                    {{ reportData.opening_stock }}
                                </td>
                            </tr>

                            <tr
                                v-for="row in tableRows"
                                :key="row.id"
                                class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-3 text-xs text-gray-500 whitespace-nowrap"
                                >
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-bold uppercase w-fit border"
                                            :class="getTypeBadge(row.type)"
                                        >
                                            {{ row.type }}
                                        </span>
                                        <span
                                            class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300"
                                        >
                                            {{ row.reference_number || "-" }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-3 italic text-gray-600 dark:text-gray-400"
                                >
                                    {{ row.description }}
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <span
                                        v-if="
                                            row.stock_after > row.stock_before
                                        "
                                        class="px-2 py-1 font-bold text-green-600 rounded bg-green-50"
                                    >
                                        +{{ row.quantity }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <span
                                        v-if="
                                            row.stock_after < row.stock_before
                                        "
                                        class="px-2 py-1 font-bold text-red-600 rounded bg-red-50"
                                    >
                                        -{{ row.quantity }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>

                                <td
                                    class="px-6 py-3 font-bold text-right text-gray-900 dark:text-white"
                                >
                                    {{ row.stock_after }}
                                </td>
                            </tr>

                            <tr v-if="tableRows.length === 0">
                                <td
                                    colspan="6"
                                    class="px-6 py-10 italic text-center text-gray-400"
                                >
                                    Tidak ada aktivitas transaksi pada rentang
                                    tanggal ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Premium Empty State -->
            <div
                v-else
                class="flex flex-col items-center justify-center min-h-[400px] bg-gradient-to-b from-white to-gray-50/50 dark:from-gray-800 dark:to-gray-800/50 border border-gray-100 dark:border-gray-700 shadow-[0_2px_10px_-3px_rgba(0,0,0,0.05)] rounded-2xl relative overflow-hidden"
            >
                <!-- Decorative Background Element -->
                <div class="absolute inset-0 z-0">
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-lime-400/5 dark:bg-lime-900/10 rounded-full blur-3xl"></div>
                </div>

                <div class="relative z-10 flex flex-col items-center text-center p-8 max-w-md">
                    <div
                        class="flex items-center justify-center w-20 h-20 mb-6 bg-white dark:bg-gray-700 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-600"
                    >
                         <svg class="w-10 h-10 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                        Analisis Pergerakan Stok
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-6">
                        Pilih produk pada panel filter di atas untuk melihat riwayat lengkap perputaran barang, dari saldo awal, mutasi masuk/keluar, hingga sisa stok saat ini.
                    </p>
                    
                    <div class="flex items-center gap-2 text-xs font-semibold text-lime-600 dark:text-lime-400 bg-lime-50 dark:bg-lime-900/20 px-3 py-1.5 rounded-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Siap menampilkan rincian
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
