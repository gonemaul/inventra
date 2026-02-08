<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
import { useExport } from "@/Composable/useExport";

const props = defineProps({
    filters: Object,
    data: Object,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});
const applyFilter = () =>
    form.get(route("reports.cash-flow"), { preserveScroll: true });
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const { exportToCsv } = useExport();

const doExport = () => {
    const rows = [
        { Item: 'Total Uang Masuk', Nilai: props.data.cash_in },
        { Item: ' - Dari Penjualan', Nilai: props.data.breakdown.sales },
        { Item: 'Total Uang Keluar', Nilai: props.data.cash_out },
        { Item: ' - Bayar Supplier', Nilai: props.data.breakdown.supplier_payment },
        { Item: ' - Biaya Operasional', Nilai: props.data.breakdown.expenses },
        { Item: 'Net Cashflow', Nilai: props.data.net_flow },
    ];
    exportToCsv('Laporan_Arus_Kas', rows);
};

const chartOptions = {
    chart: { type: "bar", height: 350, toolbar: { show: false } },
    colors: ["#10b981", "#ef4444"],
    plotOptions: { bar: { borderRadius: 8, distributed: true } },
    xaxis: { categories: ["Uang Masuk", "Uang Keluar"] },
    dataLabels: { formatter: (val) => formatRupiah(val) },
    legend: { show: false },
};

const chartSeries = computed(() => [
    {
        name: "Total",
        data: [props.data.cash_in, props.data.cash_out],
    },
]);
</script>

<template>
    <Head title="Laporan Arus Kas" />
    <AuthenticatedLayout headerTitle="Arus Kas (Cashflow)">
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
            <div
                class="flex justify-end gap-2 p-3 bg-white shadow-sm dark:bg-gray-800 rounded-xl"
            >
                <input
                    type="date"
                    v-model="form.start_date"
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700"
                />
                <input
                    type="date"
                    v-model="form.end_date"
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700"
                />
                <button
                    @click="applyFilter"
                    class="px-4 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700"
                >
                    Cek
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="p-6 bg-white border-l-4 border-green-500 shadow-sm dark:bg-gray-800 rounded-2xl"
                >
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Total Uang Masuk
                    </p>
                    <h3 class="text-2xl font-black text-green-600">
                        {{ formatRupiah(data.cash_in) }}
                    </h3>
                </div>
                <div
                    class="p-6 bg-white border-l-4 border-red-500 shadow-sm dark:bg-gray-800 rounded-2xl"
                >
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Total Uang Keluar
                    </p>
                    <h3 class="text-2xl font-black text-red-600">
                        {{ formatRupiah(data.cash_out) }}
                    </h3>
                </div>
                <div
                    class="p-6 text-white shadow-lg rounded-2xl"
                    :class="
                        data.net_flow >= 0 ? 'bg-blue-600' : 'bg-orange-500'
                    "
                >
                    <p class="text-xs font-bold uppercase opacity-80">
                        Net Cashflow
                    </p>
                    <h3 class="text-3xl font-black">
                        {{ formatRupiah(data.net_flow) }}
                    </h3>
                    <p class="mt-1 text-xs">
                        {{
                            data.net_flow >= 0
                                ? "Surplus (Positif)"
                                : "Defisit (Negatif)"
                        }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <h3 class="mb-4 font-bold dark:text-white">
                        Visualisasi Arus Kas
                    </h3>
                    <VueApexCharts
                        type="bar"
                        height="300"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></VueApexCharts>
                </div>
                <div
                    class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <h3 class="mb-4 font-bold dark:text-white">Rincian</h3>
                    <ul class="space-y-4 text-sm">
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Penjualan Tunai/DP</span
                            >
                            <span class="font-bold text-green-600"
                                >+
                                {{ formatRupiah(data.breakdown.sales) }}</span
                            >
                        </li>
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Bayar Supplier</span
                            >
                            <span class="font-bold text-red-500"
                                >-
                                {{
                                    formatRupiah(
                                        data.breakdown.supplier_payment
                                    )
                                }}</span
                            >
                        </li>
                        <li
                            class="flex justify-between pb-2 border-b border-gray-100 dark:border-gray-700"
                        >
                            <span class="text-gray-600 dark:text-gray-400"
                                >Biaya Operasional</span
                            >
                            <span class="font-bold text-red-500"
                                >-
                                {{
                                    formatRupiah(data.breakdown.expenses)
                                }}</span
                            >
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
