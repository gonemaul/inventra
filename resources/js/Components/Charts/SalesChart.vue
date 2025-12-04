<script setup>
import { computed } from "vue";
import VueApexCharts from "vue3-apexcharts";

const props = defineProps({
    data: Array, // Data Angka [10, 20, 30]
    labels: Array, // Label Bawah ['Sen', 'Sel', 'Rab']
    height: { type: Number, default: 300 },
    color: { type: String, default: "#84cc16" }, // Default Lime-500
});

// Konfigurasi Chart (Modern Style)
const chartOptions = computed(() => ({
    chart: {
        type: "area", // Area chart terlihat lebih modern drpd bar biasa
        fontFamily: "Inter, sans-serif",
        toolbar: { show: false }, // Hilangkan menu download/zoom biar bersih
        zoom: { enabled: false },
    },
    dataLabels: { enabled: false }, // Hilangkan angka di setiap titik biar tidak semak
    stroke: {
        curve: "smooth", // Garis melengkung halus
        width: 3,
    },
    colors: [props.color], // Warna garis
    fill: {
        type: "gradient",
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100],
        },
    },
    xaxis: {
        categories: props.labels,
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: "#9ca3af", fontSize: "12px" },
        },
    },
    yaxis: {
        labels: {
            style: { colors: "#9ca3af", fontSize: "12px" },
            formatter: (value) => {
                // Format K (Ribuan) atau M (Juta) biar rapi
                if (value >= 1000000)
                    return (value / 1000000).toFixed(1) + "jt";
                if (value >= 1000) return (value / 1000).toFixed(0) + "rb";
                return value;
            },
        },
    },
    grid: {
        borderColor: "#f3f4f6",
        strokeDashArray: 4, // Garis putus-putus
        yaxis: { lines: { show: true } },
    },
    tooltip: {
        y: {
            formatter: (val) =>
                "Rp " + new Intl.NumberFormat("id-ID").format(val),
        },
    },
}));

const series = computed(() => [
    {
        name: "Penjualan",
        data: props.data,
    },
]);
</script>

<template>
    <div
        class="w-full p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700"
    >
        <VueApexCharts
            type="area"
            :height="height"
            :options="chartOptions"
            :series="series"
        />
    </div>
</template>
