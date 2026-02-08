<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, Link } from "@inertiajs/vue3";
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
import { useExport } from "@/Composable/useExport";

const props = defineProps({
    filters: Object,
    data: Array,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
    limit: props.filters.limit,
    sort_by: props.filters.sort_by,
});

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.data.map((d, index) => ({
        Rank: index + 1,
        Nama: d.name,
        Kode: d.code,
        Kategori: d.category_name,
        'Terjual (Qty)': d.total_qty,
        'Omzet (Rp)': d.total_revenue
    }));
    exportToCsv('Laporan_Top_Produk', dataToExport);
};

const applyFilter = () => {
    form.get(route("reports.top-products"), { preserveScroll: true });
};

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// --- KONFIGURASI APEXCHARTS (HORIZONTAL BAR) ---
const chartOptions = computed(() => ({
    chart: {
        type: "bar",
        height: 400,
        fontFamily: "Inter, sans-serif",
        toolbar: { show: false },
    },
    plotOptions: {
        bar: {
            horizontal: true, // Wajib Horizontal untuk nama produk panjang
            borderRadius: 4,
            barHeight: "60%",
            distributed: true, // Warna beda-beda tiap bar
        },
    },
    colors: ["#84cc16", "#3b82f6", "#f59e0b", "#ef4444", "#8b5cf6"], // Palet warna-warni
    dataLabels: {
        enabled: true,
        textAnchor: "start",
        style: { colors: ["#fff"] },
        formatter: function (val, opt) {
            return props.filters.sort_by === "total_revenue"
                ? formatRupiah(val)
                : val + " Pcs";
        },
        offsetX: 0,
    },
    xaxis: {
        categories: props.data.map((d) =>
            d.name.length > 20 ? d.name.substring(0, 20) + "..." : d.name
        ),
        labels: {
            formatter: (val) =>
                props.filters.sort_by === "total_revenue"
                    ? (val / 1000000).toFixed(1) + "jt"
                    : val,
        },
    },
    tooltip: {
        y: {
            formatter: (val) =>
                props.filters.sort_by === "total_revenue"
                    ? formatRupiah(val)
                    : val + " Unit",
        },
    },
    grid: { show: false }, // Hilangkan grid biar bersih
}));

const chartSeries = computed(() => [
    {
        name:
            props.filters.sort_by === "total_qty"
                ? "Terjual (Qty)"
                : "Omzet (Rp)",
        data: props.data.map((d) =>
            props.filters.sort_by === "total_qty"
                ? d.total_qty
                : d.total_revenue
        ),
    },
]);
</script>

<template>
    <Head title="Produk Terlaris" />

    <AuthenticatedLayout headerTitle="Analisa Produk Terlaris">
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
                class="flex flex-col items-end justify-between gap-4 p-5 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 md:flex-row"
            >
                <div class="flex flex-col w-full gap-4 md:flex-row md:w-auto">
                    <div class="flex items-center gap-2">
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
                    </div>

                    <select
                        v-model="form.sort_by"
                        class="text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option value="total_qty">
                            Berdasarkan Jumlah Terjual (Qty)
                        </option>
                        <option value="total_revenue">
                            Berdasarkan Total Uang (Omzet)
                        </option>
                    </select>

                    <select
                        v-model="form.limit"
                        class="w-24 text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option value="5">Top 5</option>
                        <option value="10">Top 10</option>
                        <option value="20">Top 20</option>
                    </select>
                </div>

                <button
                    @click="applyFilter"
                    class="w-full px-6 py-2 font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700 md:w-auto"
                >
                    Analisa
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div
                    class="p-6 bg-white border border-gray-200 shadow-lg lg:col-span-2 rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <h3
                        class="flex items-center gap-2 mb-4 text-lg font-bold text-gray-800 dark:text-white"
                    >
                        <span class="text-2xl">🏆</span>
                        Grafik Top {{ form.limit }} Produk
                    </h3>
                    <div v-if="data.length > 0">
                        <VueApexCharts
                            type="bar"
                            height="400"
                            :options="chartOptions"
                            :series="chartSeries"
                        ></VueApexCharts>
                    </div>
                    <div
                        v-else
                        class="flex items-center justify-center h-64 text-gray-400"
                    >
                        Tidak ada data penjualan.
                    </div>
                </div>

                <div
                    class="flex flex-col overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div
                        class="p-4 border-b border-gray-100 bg-gray-50/50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        <h3 class="font-bold text-gray-800 dark:text-white">
                            Leaderboard
                        </h3>
                    </div>

                    <div
                        class="flex-1 overflow-y-auto max-h-[450px] custom-scrollbar"
                    >
                        <table class="w-full text-sm text-left">
                            <tbody
                                class="divide-y divide-gray-100 dark:divide-gray-700"
                            >
                                <tr
                                    v-for="(item, idx) in data"
                                    :key="idx"
                                    class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30 group"
                                >
                                    <td
                                        class="w-10 px-4 py-3 text-lg italic font-black text-center text-gray-300 group-hover:text-blue-500"
                                    >
                                        #{{ idx + 1 }}
                                    </td>
                                    <td class="px-2 py-3">
                                        <div
                                            class="font-bold text-gray-800 dark:text-white line-clamp-1"
                                            :title="item.name"
                                        >
                                            {{ item.name }}
                                        </div>
                                        <div
                                            class="text-[10px] text-gray-500 uppercase tracking-wider"
                                        >
                                            {{ item.code }} •
                                            {{ item.category_name || "No Cat" }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div
                                            class="font-black text-gray-800 dark:text-white"
                                        >
                                            {{
                                                form.sort_by === "total_qty"
                                                    ? item.total_qty
                                                    : formatRupiah(
                                                          item.total_revenue
                                                      )
                                            }}
                                        </div>
                                        <div class="text-[10px] text-gray-400">
                                            {{
                                                form.sort_by === "total_qty"
                                                    ? "Terjual"
                                                    : "Omzet"
                                            }}
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="data.length === 0">
                                    <td
                                        colspan="3"
                                        class="px-4 py-8 text-xs text-center text-gray-400"
                                    >
                                        Data kosong
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
