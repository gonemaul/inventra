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
    <div
        class="p-5 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700"
    >
        <h2
            class="flex items-center gap-2 mb-4 text-base font-bold text-gray-800 dark:text-white"
        >
            <span>📊</span> Analisis Pergerakan Barang
        </h2>

        <div
            v-if="inventory.is_dead_stock"
            class="p-4 mb-4 bg-gray-100 border-l-4 border-gray-500 rounded-r-lg"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">🐢</span>
                <div>
                    <h3 class="font-bold text-gray-800">
                        Dead Stock (Barang Mati)
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Barang ini masih menumpuk di gudang. Terakhir terjual
                        <strong>{{ inventory.last_sale }}</strong
                        >.
                    </p>
                    <p class="mt-1 text-sm text-yellow-700">
                        Produk ini tidak bergerak selama
                        <strong>{{ inventory.days_inactive }} hari</strong>.
                    </p>
                    <div
                        class="mt-2 text-xs font-mono bg-white p-1.5 rounded border border-gray-200 inline-block"
                    >
                        Uang Mandek:
                        {{
                            formatRupiah(product.purchase_price * product.stock)
                        }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else-if="inventory.avg_daily >= 1"
            class="p-4 mb-4 border-l-4 border-purple-500 rounded-r-lg bg-purple-50"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">🔥</span>
                <div>
                    <h3 class="font-bold text-purple-900">
                        Fast Moving (Laris)
                    </h3>
                    <p class="mt-1 text-sm text-purple-700">
                        Perputaran sangat cepat! Rata-rata laku
                        <strong>{{ inventory.avg_daily }} unit/hari</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else
            class="p-4 mb-4 border-l-4 border-blue-400 rounded-r-lg bg-blue-50"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚖️</span>
                <div>
                    <h3 class="font-bold text-blue-900">Pergerakan Normal</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Penjualan stabil. Terjual
                        <strong>{{ inventory.sales_30_days }} unit</strong>
                        dalam 30 hari terakhir.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <p class="mb-2 text-xs font-bold text-gray-400 uppercase">
                Tren Mingguan
            </p>
            <div class="relative -ml-2">
                <VueApexCharts
                    type="bar"
                    height="100"
                    :options="chartOptions"
                    :series="series"
                />
            </div>
        </div>
    </div>
</template>
