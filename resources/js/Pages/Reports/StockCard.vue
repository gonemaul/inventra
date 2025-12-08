<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    filters: Object,
    products: Array,
    reportData: Object, // { product, opening_stock, movements }
});
const { isActionLoading } = useActionLoading();
const form = useForm({
    product_id: props.filters.product_id || "",
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
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
                        <select
                            v-model="form.product_id"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700 focus:ring-lime-500 dark:text-white"
                        >
                            <option value="" disabled>-- Cari Barang --</option>
                            <option
                                v-for="p in products"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.code }} - {{ p.name }}
                            </option>
                        </select>
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
                            :disabled="form.processing"
                            class="flex items-center justify-center px-4 py-2 mt-auto font-bold text-white transition rounded-lg shadow-md bg-lime-500 hover:bg-lime-600"
                        >
                            Tampilkan
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="reportData"
                class="overflow-hidden bg-white border border-gray-200 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-700/50"
                >
                    <div>
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
                            class="text-xs text-gray-500 uppercase bg-gray-100 dark:bg-gray-900/50"
                        >
                            <tr>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Tipe & Ref</th>
                                <th class="px-6 py-3">Keterangan</th>
                                <th
                                    class="px-6 py-3 text-center text-green-600"
                                >
                                    Masuk
                                </th>
                                <th class="px-6 py-3 text-center text-red-600">
                                    Keluar
                                </th>
                                <th class="px-6 py-3 font-black text-right">
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
                                        v-if="row.quantity > 0"
                                        class="px-2 py-1 font-bold text-green-600 rounded bg-green-50"
                                    >
                                        +{{ row.quantity }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <span
                                        v-if="row.quantity < 0"
                                        class="px-2 py-1 font-bold text-red-600 rounded bg-red-50"
                                    >
                                        {{ row.quantity }}
                                    </span>
                                    <span v-else class="text-gray-300">-</span>
                                </td>

                                <td
                                    class="px-6 py-3 font-bold text-right text-gray-900 dark:text-white"
                                >
                                    {{ row.balance_after }}
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

            <div
                v-else
                class="flex flex-col items-center justify-center border-2 border-gray-300 border-dashed h-96 bg-gray-50 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex items-center justify-center w-20 h-20 mb-4 bg-gray-200 rounded-full dark:bg-gray-700 animate-pulse"
                >
                    <svg
                        class="w-10 h-10 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                        ></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300">
                    Pilih Produk Terlebih Dahulu
                </h3>
                <p class="text-sm text-gray-500">
                    Silakan pilih produk di atas untuk melihat mutasi stok.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
