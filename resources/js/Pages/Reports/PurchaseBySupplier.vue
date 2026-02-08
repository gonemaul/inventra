<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
import { useExport } from "@/Composable/useExport";

const props = defineProps({
    filters: Object,
    data: Array,
    total_all_spend: Number,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    form.get(route("reports.purchase-supplier"), { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.data.map(d => ({
        Supplier: d.name,
        Frequency: d.total_trx,
        'Total Belanja': d.total_spend
    }));
    exportToCsv('Laporan_Pembelian_Supplier', dataToExport);
};

// Chart Options (Bar Chart Horizontal)
const chartOptions = computed(() => ({
    chart: { type: "bar", height: 350, toolbar: { show: false } },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: "50%",
            distributed: true,
        },
    },
    colors: ["#f97316", "#eab308", "#84cc16", "#22c55e", "#06b6d4"], // Warna Oranye ke Hijau
    dataLabels: {
        enabled: true,
        textAnchor: "start",
        formatter: (val, opt) => formatRupiah(val),
        offsetX: 0,
    },
    xaxis: {
        categories: props.data.map((d) => d.name),
        labels: { formatter: (val) => (val / 1000000).toFixed(0) + "jt" },
    },
    grid: { show: false },
    tooltip: { y: { formatter: (val) => formatRupiah(val) } },
}));

const chartSeries = computed(() => [
    {
        name: "Total Belanja",
        data: props.data.map((d) => d.total_spend),
    },
]);
</script>

<template>
    <Head title="Laporan Pembelian Supplier" />

    <AuthenticatedLayout headerTitle="Analisa Supplier">
        <div class="space-y-6">
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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div
                    class="flex items-center gap-4 p-4 bg-white border border-gray-200 shadow-sm md:col-span-2 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <span class="text-sm font-bold text-gray-500"
                        >Periode:</span
                    >
                    <input
                        type="date"
                        v-model="form.start_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <span class="text-gray-400">-</span>
                    <input
                        type="date"
                        v-model="form.end_date"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                    <button
                        @click="applyFilter"
                        class="px-4 py-2 text-sm font-bold text-white transition bg-orange-500 rounded-lg shadow hover:bg-orange-600"
                    >
                        Lihat
                    </button>
                </div>

                <div
                    class="p-4 text-white shadow-lg bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-orange-100 uppercase"
                    >
                        Total Belanja (Procurement)
                    </p>
                    <h3 class="mt-1 text-2xl font-black">
                        {{ formatRupiah(total_all_spend) }}
                    </h3>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    class="p-6 bg-white border border-gray-200 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <h3
                        class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                    >
                        Top Vendor by Value
                    </h3>
                    <VueApexCharts
                        type="bar"
                        height="350"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></VueApexCharts>
                </div>

                <div
                    class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div
                        class="p-4 border-b bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <h3 class="font-bold text-gray-800 dark:text-white">
                            Rincian Vendor
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead
                                class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700"
                            >
                                <tr>
                                    <th class="px-6 py-3">Nama Supplier</th>
                                    <th class="px-6 py-3 text-center">
                                        Frekuensi
                                    </th>
                                    <th class="px-6 py-3 text-right">
                                        Total Belanja
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-700"
                            >
                                <tr
                                    v-for="(row, idx) in data"
                                    :key="idx"
                                    class="transition hover:bg-orange-50/30 dark:hover:bg-gray-700/50"
                                >
                                    <td
                                        class="px-6 py-3 font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ row.name }}
                                    </td>
                                    <td
                                        class="px-6 py-3 text-center text-gray-500"
                                    >
                                        {{ row.total_trx }}x Transaksi
                                    </td>
                                    <td
                                        class="px-6 py-3 font-black text-right text-orange-600"
                                    >
                                        {{ formatRupiah(row.total_spend) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
