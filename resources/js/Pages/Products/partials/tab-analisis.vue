<script setup>
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";
const props = defineProps({
    inventory: {
        type: Object,
        required: true,
    },
    product: {
        type: Object,
        required: true,
    },
    chart_data: Array,
});
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// 1. Mengambil data angka untuk Series
const series = computed(() => [
    {
        name: "Terjual",
        data: props.chart_data.map((d) => d.qty),
    },
]);
const chartOptions = computed(() => {
    // Ambil label dari data (misal: Senin, Selasa) atau index jika tidak ada label
    const categories = props.chart_data.map((d) => d.day);

    return {
        chart: {
            type: "bar",
            toolbar: { show: false }, // Hilangkan menu download/zoom
            fontFamily: "inherit", // Ikuti font website
            sparkline: { enabled: false }, // Set true jika ingin super minimalis tanpa sumbu X
        },
        plotOptions: {
            bar: {
                borderRadius: 4, // Sudut tumpul pada bar
                columnWidth: "60%", // Lebar batang
                distributed: false, // False agar warnanya seragam (Lime)
            },
        },
        dataLabels: {
            enabled: false, // Hilangkan angka statis di atas bar (biar bersih)
        },
        colors: ["#84cc16"], // Tailwind Lime-500
        stroke: {
            show: true,
            width: 0,
            colors: ["transparent"],
        },
        xaxis: {
            categories: categories,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: {
                    colors: "#9ca3af", // Gray-400
                    fontSize: "10px",
                },
            },
        },
        yaxis: {
            show: false, // Hilangkan angka di kiri (Sumbu Y) agar bersih
        },
        grid: {
            show: false, // Hilangkan garis-garis latar belakang
            padding: {
                top: 0,
                right: 0,
                bottom: 0,
                left: 0,
            },
        },
        tooltip: {
            theme: "dark", // Bisa 'light' atau 'dark'
            y: {
                formatter: function (val) {
                    return val + " Pcs";
                },
            },
        },
        fill: {
            opacity: 1,
        },
    };
});
</script>
<template>
    <div class="space-y-6">
        <!-- Insight Card -->
        <div
            v-if="inventory.is_dead_stock"
            class="flex items-start gap-4 p-5 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <div class="p-3 bg-gray-100 rounded-xl dark:bg-gray-700 text-3xl">🐢</div>
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    Dead Stock (Barang Mati)
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Barang ini tidak bergerak selama <strong class="text-gray-900 dark:text-gray-200">{{ inventory.days_inactive }} hari</strong>.
                    Pertimbangkan untuk diskon atau bundling agar modal berputar kembali.
                </p>
                <div class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 text-xs font-mono font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600">
                    <span>💸 Modal Tertahan:</span>
                    <span class="font-bold">{{ formatRupiah(product.purchase_price * product.stock) }}</span>
                </div>
            </div>
        </div>

        <div
            v-else-if="inventory.avg_daily >= 1"
            class="flex items-start gap-4 p-5 bg-gradient-to-br from-purple-50 to-white border border-purple-100 rounded-xl shadow-sm dark:from-purple-900/10 dark:to-gray-800 dark:border-purple-800"
        >
            <div class="p-3 bg-purple-100 rounded-xl dark:bg-purple-900/30 text-3xl">🔥</div>
            <div>
                <h3 class="text-lg font-bold text-purple-900 dark:text-purple-300">
                    Fast Moving (Laris Manis)
                </h3>
                <p class="mt-1 text-sm text-purple-700 dark:text-purple-400">
                    Performa luar biasa! Rata-rata penjualan mencapai
                    <strong class="text-purple-900 dark:text-purple-200">{{ inventory.avg_daily }} unit/hari</strong>.
                    Pastikan stok selalu aman agar tidak kehilangan momen penjualan.
                </p>
            </div>
        </div>

        <div
            v-else
            class="flex items-start gap-4 p-5 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
             <div class="p-3 bg-blue-50 rounded-xl dark:bg-blue-900/20 text-3xl">⚖️</div>
            <div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Pergerakan Normal</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Penjualan stabil dan terkendali. Total terjual
                    <strong class="text-gray-900 dark:text-gray-200">{{ inventory.sales_30_days }} unit</strong>
                    dalam 30 hari terakhir.
                </p>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="p-5 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
                 <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Tren Penjualan Mingguan</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Data visualisasi harian untuk analisis tren</p>
                 </div>
                 <div class="px-3 py-1 text-xs font-bold text-lime-700 bg-lime-100 rounded-full dark:bg-lime-900/30 dark:text-lime-400">
                     7 Hari Terakhir
                 </div>
            </div>
           
            <div class="relative w-full overflow-hidden">
                <VueApexCharts
                    type="bar"
                    height="200"
                    :options="chartOptions"
                    :series="series"
                />
            </div>
        </div>
    </div>
</template>
