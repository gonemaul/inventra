<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    products: Array,
    filters: Object,
    data: Array,
    product: Object,
    summary: Object,
});

const form = useForm({
    product_id: props.filters.product_id || "",
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    form.get(route("reports.price-watch"), { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "2-digit",
    });

// --- KONFIGURASI CHART ---
const chartOptions = computed(() => ({
    chart: {
        type: "line",
        height: 350,
        toolbar: { show: false },
        animations: { enabled: true },
    },
    stroke: {
        curve: "step", // Garis patah-patah (step) cocok untuk perubahan harga
        width: 3,
    },
    colors: ["#8b5cf6"], // Violet
    markers: { size: 5, hover: { size: 7 } }, // Titik pada setiap pembelian
    xaxis: {
        categories: props.data
            ? props.data.map((d) => formatDate(d.transaction_date))
            : [],
        tooltip: { enabled: false },
    },
    yaxis: {
        labels: { formatter: (val) => formatRupiah(val) },
    },
    tooltip: {
        y: { formatter: (val) => formatRupiah(val) },
    },
    grid: { borderColor: "#f3f4f6" },
}));

const chartSeries = computed(() => [
    {
        name: "Harga Beli",
        data: props.data ? props.data.map((d) => d.purchase_price) : [],
    },
]);
</script>

<template>
    <Head title="Price Watch" />

    <AuthenticatedLayout headerTitle="Analisa Harga Beli (Price Watch)">
        <div class="space-y-6">
            <div
                class="p-6 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <h3
                    class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                >
                    🔍 Pilih Produk untuk Dianalisa
                </h3>
                <div class="grid items-end grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <select
                            v-model="form.product_id"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700 focus:ring-purple-500"
                        >
                            <option value="" disabled>
                                -- Pilih Barang --
                            </option>
                            <option
                                v-for="p in products"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.code }} - {{ p.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2 md:col-span-2">
                        <input
                            type="date"
                            v-model="form.start_date"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700"
                        />
                        <input
                            type="date"
                            v-model="form.end_date"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700"
                        />
                        <button
                            @click="applyFilter"
                            class="px-4 font-bold text-white transition bg-purple-600 rounded-lg shadow hover:bg-purple-700"
                        >
                            Cek
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="product && data.length > 0" class="space-y-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div
                        class="relative flex flex-col justify-between p-5 overflow-hidden text-white shadow-lg rounded-2xl"
                        :class="
                            summary.trend > 0
                                ? 'bg-red-500'
                                : summary.trend < 0
                                ? 'bg-green-500'
                                : 'bg-gray-500'
                        "
                    >
                        <div>
                            <p
                                class="text-xs font-bold tracking-widest uppercase opacity-80"
                            >
                                Tren Harga
                            </p>
                            <h3 class="mt-1 text-2xl font-black">
                                {{
                                    summary.trend > 0
                                        ? "NAIK"
                                        : summary.trend < 0
                                        ? "TURUN"
                                        : "STABIL"
                                }}
                            </h3>
                            <p class="mt-1 text-sm font-medium">
                                {{ summary.trend > 0 ? "+" : ""
                                }}{{ formatRupiah(summary.trend) }}
                            </p>
                        </div>
                        <div class="absolute right-2 bottom-2 opacity-20">
                            <svg
                                v-if="summary.trend > 0"
                                class="w-16 h-16"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                />
                            </svg>
                            <svg
                                v-else-if="summary.trend < 0"
                                class="w-16 h-16"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-16 h-16"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 12h14"
                                />
                            </svg>
                        </div>
                    </div>

                    <div
                        class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                    >
                        <p class="text-xs font-bold text-gray-400 uppercase">
                            Harga Terendah
                        </p>
                        <p class="text-xl font-bold text-green-600">
                            {{ formatRupiah(summary.min) }}
                        </p>
                    </div>
                    <div
                        class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                    >
                        <p class="text-xs font-bold text-gray-400 uppercase">
                            Harga Tertinggi
                        </p>
                        <p class="text-xl font-bold text-red-600">
                            {{ formatRupiah(summary.max) }}
                        </p>
                    </div>
                    <div
                        class="p-5 bg-white border border-gray-200 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                    >
                        <p class="text-xs font-bold text-gray-400 uppercase">
                            Harga Rata-rata
                        </p>
                        <p class="text-xl font-bold text-blue-600">
                            {{ formatRupiah(summary.avg) }}
                        </p>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <h3
                        class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                    >
                        Grafik Fluktuasi Harga Beli
                    </h3>
                    <VueApexCharts
                        type="line"
                        height="350"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></VueApexCharts>
                </div>

                <div
                    class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div
                        class="p-4 border-b dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
                    >
                        <h3 class="font-bold text-gray-800 dark:text-white">
                            Riwayat Transaksi Pembelian
                        </h3>
                    </div>
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3">Supplier</th>
                                <th class="px-6 py-3">No. Invoice</th>
                                <th class="px-6 py-3 text-center">Qty Beli</th>
                                <th class="px-6 py-3 text-right">
                                    Harga Satuan
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="(row, idx) in data"
                                :key="idx"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-3 text-gray-500">
                                    {{ formatDate(row.transaction_date) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-medium text-gray-800 dark:text-white"
                                >
                                    {{ row.supplier_name }}
                                </td>
                                <td class="px-6 py-3 text-blue-500">
                                    {{ row.reference_no }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    {{ row.quantity }}
                                </td>
                                <td
                                    class="px-6 py-3 font-bold text-right text-purple-600"
                                >
                                    {{ formatRupiah(row.purchase_price) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-else-if="product && data.length === 0"
                class="flex flex-col items-center justify-center h-64 border-2 border-gray-200 border-dashed bg-gray-50 rounded-2xl"
            >
                <p class="text-gray-500">
                    Belum ada riwayat pembelian untuk produk ini dalam periode
                    tersebut.
                </p>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center h-64 border-2 border-gray-200 border-dashed bg-gray-50 rounded-2xl"
            >
                <div
                    class="flex items-center justify-center w-16 h-16 mb-4 bg-gray-200 rounded-full"
                >
                    <svg
                        class="w-8 h-8 text-gray-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        ></path>
                    </svg>
                </div>
                <p class="text-gray-500">
                    Pilih produk di atas untuk melihat analisa harga.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
