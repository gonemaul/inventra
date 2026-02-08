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

const { exportToCsv } = useExport();

const doExport = () => {
    const rows = [
        { Item: 'Penjualan (Omzet)', Nilai: props.data.revenue },
        { Item: 'HPP', Nilai: props.data.cogs },
        { Item: 'Laba Kotor', Nilai: props.data.gross_profit },
        { Item: 'Margin Laba Kotor (%)', Nilai: props.data.gross_margin },
        { Item: 'Total Pengeluaran', Nilai: props.data.expenses },
        ...props.data.expense_details.map(e => ({ Item: ` - ${e.category || 'Lainnya'}`, Nilai: e.total })),
        { Item: 'Laba Bersih', Nilai: props.data.net_profit },
        { Item: 'Margin Laba Bersih (%)', Nilai: props.data.net_margin },
    ];
    exportToCsv('Laporan_Laba_Rugi', rows);
};

const applyFilter = () => {
    form.get(route("reports.profit-loss"), { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// --- CHART OPERASIONAL ---
const chartOptions = computed(() => ({
    chart: { type: "donut" },
    labels: props.data.expense_details.map((d) => d.category || "Lain-lain"),
    colors: ["#f43f5e", "#f59e0b", "#8b5cf6", "#3b82f6"],
    dataLabels: { enabled: false },
    legend: { position: "bottom" },
}));
const chartSeries = computed(() =>
    props.data.expense_details.map((d) => parseFloat(d.total))
);
</script>

<template>
    <Head title="Laporan Laba Rugi" />

    <AuthenticatedLayout headerTitle="Laba Rugi (Profit & Loss)">
        <div class="max-w-5xl mx-auto space-y-6">
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
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                />
                <input
                    type="date"
                    v-model="form.end_date"
                    class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                />
                <button
                    @click="applyFilter"
                    class="px-4 text-sm font-bold text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                >
                    Hitung
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div
                    class="relative overflow-hidden bg-white border border-gray-200 shadow-xl md:col-span-2 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <div
                        class="h-2 bg-gradient-to-r from-purple-500 to-indigo-600"
                    ></div>
                    <div class="p-8">
                        <h2
                            class="mb-8 text-xl font-black tracking-widest text-center text-gray-800 uppercase dark:text-white"
                        >
                            Income Statement
                        </h2>

                        <div class="mb-6">
                            <p
                                class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Pendapatan
                            </p>
                            <div
                                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                            >
                                <span class="text-gray-700 dark:text-gray-300"
                                    >Penjualan Kotor (Omzet)</span
                                >
                                <span
                                    class="font-bold text-gray-900 dark:text-white"
                                    >{{ formatRupiah(data.revenue) }}</span
                                >
                            </div>
                        </div>

                        <div class="mb-6">
                            <p
                                class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Harga Pokok
                            </p>
                            <div
                                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                            >
                                <span class="text-gray-700 dark:text-gray-300"
                                    >HPP Barang Terjual</span
                                >
                                <span class="font-bold text-red-500"
                                    >({{ formatRupiah(data.cogs) }})</span
                                >
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between p-4 mb-6 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                        >
                            <div>
                                <h4
                                    class="text-sm font-black text-gray-800 uppercase dark:text-white"
                                >
                                    Laba Kotor
                                </h4>
                                <span class="text-xs font-bold text-blue-600"
                                    >Margin: {{ data.gross_margin }}%</span
                                >
                            </div>
                            <span
                                class="text-xl font-black text-blue-700 dark:text-blue-400"
                                >{{ formatRupiah(data.gross_profit) }}</span
                            >
                        </div>

                        <div class="mb-6">
                            <p
                                class="mb-2 text-xs font-bold tracking-wider text-gray-400 uppercase"
                            >
                                Biaya Operasional
                            </p>
                            <div
                                class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700"
                            >
                                <span class="text-gray-700 dark:text-gray-300"
                                    >Total Pengeluaran</span
                                >
                                <span class="font-bold text-red-500"
                                    >({{ formatRupiah(data.expenses) }})</span
                                >
                            </div>
                        </div>

                        <div
                            class="relative p-6 overflow-hidden text-white shadow-lg rounded-xl"
                            :class="
                                data.net_profit >= 0
                                    ? 'bg-gradient-to-br from-emerald-500 to-teal-600'
                                    : 'bg-gradient-to-br from-red-500 to-pink-600'
                            "
                        >
                            <div
                                class="relative z-10 flex items-center justify-between"
                            >
                                <div>
                                    <h3
                                        class="text-sm font-bold tracking-widest uppercase opacity-90"
                                    >
                                        Laba Bersih
                                    </h3>
                                    <p class="mt-1 text-xs opacity-75">
                                        Net Margin: {{ data.net_margin }}%
                                    </p>
                                </div>
                                <div class="text-3xl font-black tracking-tight">
                                    {{ formatRupiah(data.net_profit) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div
                        class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                    >
                        <h3
                            class="mb-4 text-sm font-bold text-gray-800 dark:text-white"
                        >
                            Komposisi Biaya
                        </h3>
                        <div v-if="data.expense_details.length > 0">
                            <VueApexCharts
                                type="donut"
                                height="250"
                                :options="chartOptions"
                                :series="chartSeries"
                            ></VueApexCharts>
                        </div>
                        <div
                            v-else
                            class="py-10 text-xs text-center text-gray-400"
                        >
                            Belum ada data pengeluaran.
                        </div>

                        <div class="mt-4 space-y-2">
                            <div
                                v-for="(exp, i) in data.expense_details"
                                :key="i"
                                class="flex justify-between pb-1 text-xs border-b border-gray-100 dark:border-gray-700"
                            >
                                <span
                                    class="text-gray-600 dark:text-gray-400"
                                    >{{ exp.category || "Lainnya" }}</span
                                >
                                <span class="font-bold">{{
                                    formatRupiah(exp.total)
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
