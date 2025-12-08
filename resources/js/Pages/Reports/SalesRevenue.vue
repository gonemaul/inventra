<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts"; // Import ApexCharts

const props = defineProps({
    filters: Object,
    data: Array,
    summary: Object,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    form.get(route("reports.sales-revenue"), { preserveScroll: true });
};

// Formatter
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatK = (val) => {
    if (val >= 1000000) return (val / 1000000).toFixed(1) + "jt";
    if (val >= 1000) return (val / 1000).toFixed(0) + "rb";
    return val;
};

// --- KONFIGURASI APEXCHARTS ---

// 1. Data Series (Sumbu Y)
const series = computed(() => [
    {
        name: "Omzet",
        data: props.data.map((d) => parseInt(d.revenue)),
    },
]);

// 2. Opsi Grafik (Visual)
const chartOptions = computed(() => ({
    chart: {
        type: "area", // Area chart biar ada warna di bawah garis
        height: 350,
        fontFamily: "Inter, sans-serif",
        toolbar: { show: false }, // Hilangkan menu download bawaan biar bersih
        zoom: { enabled: false },
    },
    colors: ["#84cc16"], // Warna Lime-500 (Sesuai tema Inventra)
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7, // Atas pekat
            opacityTo: 0.1, // Bawah transparan
            stops: [0, 90, 100],
        },
    },
    dataLabels: { enabled: false }, // Jangan tampilkan angka di setiap titik (biar rapi)
    stroke: {
        curve: "smooth", // Garis melengkung
        width: 3,
    },
    xaxis: {
        categories: props.data.map((d) => {
            // Format Tanggal Sumbu X (misal: 01 Okt)
            return new Date(d.date).toLocaleDateString("id-ID", {
                day: "numeric",
                month: "short",
            });
        }),
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: "#9ca3af", fontSize: "11px" }, // Warna abu-abu
        },
    },
    yaxis: {
        labels: {
            style: { colors: "#9ca3af", fontSize: "11px" },
            formatter: (val) => formatK(val), // Format sumbu Y jadi "1.5jt", "500rb"
        },
    },
    grid: {
        borderColor: "#f3f4f6", // Garis grid tipis
        strokeDashArray: 4, // Garis putus-putus
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        theme: "light",
        y: {
            formatter: (val) => formatRupiah(val), // Tooltip tetap format Rupiah lengkap
        },
    },
}));
</script>

<template>
    <Head title="Laporan Omzet Penjualan" />

    <AuthenticatedLayout headerTitle="Laporan Penjualan (Sales)">
        <div class="space-y-6">
            <div
                class="flex flex-col items-center justify-between p-4 bg-white border border-gray-200 shadow-sm md:flex-row dark:bg-gray-800 rounded-2xl dark:border-gray-700"
            >
                <h3
                    class="mb-3 font-bold text-gray-700 dark:text-gray-200 md:mb-0"
                >
                    📅 Periode Laporan
                </h3>
                <div class="flex w-full gap-2 md:w-auto">
                    <input
                        type="date"
                        v-model="form.start_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <span class="self-center text-gray-400">-</span>
                    <input
                        type="date"
                        v-model="form.end_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <button
                        @click="applyFilter"
                        class="px-4 py-2 font-bold text-white transition rounded-lg shadow-md bg-lime-500 hover:bg-lime-600"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="relative p-5 overflow-hidden text-white shadow-lg bg-gradient-to-r from-blue-600 to-blue-500 rounded-2xl"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-blue-100 uppercase"
                    >
                        Total Omzet
                    </p>
                    <h2 class="mt-1 text-3xl font-black">
                        {{ formatRupiah(summary.total_revenue) }}
                    </h2>
                    <div class="absolute bottom-0 right-0 p-3 opacity-20">
                        <svg
                            class="w-16 h-16"
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
                    class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Total Transaksi
                    </p>
                    <h2
                        class="mt-1 text-3xl font-black text-gray-800 dark:text-white"
                    >
                        {{ summary.total_transactions }}
                        <span class="text-sm font-medium text-gray-400"
                            >Nota</span
                        >
                    </h2>
                </div>

                <div
                    class="p-5 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Rata-rata per Nota
                    </p>
                    <h2 class="mt-1 text-3xl font-black text-lime-600">
                        {{ formatRupiah(summary.average_basket_size) }}
                    </h2>
                    <p class="mt-1 text-xs text-gray-400">Basket Size</p>
                </div>
            </div>

            <div
                class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                        Grafik Tren Penjualan
                    </h3>
                    <div
                        class="px-2 py-1 text-xs font-bold border rounded text-lime-600 bg-lime-50 border-lime-100"
                    >
                        Daily Revenue
                    </div>
                </div>

                <div class="w-full">
                    <VueApexCharts
                        type="area"
                        height="350"
                        :options="chartOptions"
                        :series="series"
                    ></VueApexCharts>
                </div>
            </div>

            <div
                class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Rincian Per Hari
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                        >
                            <tr>
                                <th class="px-6 py-3">Tanggal</th>
                                <th class="px-6 py-3 text-center">
                                    Jumlah Transaksi
                                </th>
                                <th class="px-6 py-3 text-right">
                                    Omzet Harian
                                </th>
                                <th class="px-6 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="(row, idx) in data"
                                :key="idx"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/30"
                            >
                                <td
                                    class="px-6 py-3 font-medium text-gray-900 dark:text-white"
                                >
                                    {{
                                        new Date(row.date).toLocaleDateString(
                                            "id-ID",
                                            {
                                                weekday: "long",
                                                day: "numeric",
                                                month: "long",
                                                year: "numeric",
                                            }
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="px-2 py-1 text-xs font-bold text-blue-700 bg-blue-100 border border-blue-200 rounded"
                                        >{{ row.transaction_count }} Nota</span
                                    >
                                </td>
                                <td
                                    class="px-6 py-3 font-black text-right text-lime-600"
                                >
                                    {{ formatRupiah(row.revenue) }}
                                </td>
                                <td class="px-6 py-3 text-center">
                                    <button
                                        class="text-xs font-bold text-gray-400 hover:text-blue-600 hover:underline"
                                    >
                                        Lihat Detail
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-8 text-center text-gray-400"
                                >
                                    Tidak ada penjualan pada periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
