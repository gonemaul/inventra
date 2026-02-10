<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    data: Object, // { labels: [], values: [] }
    title: {
        type: String,
        default: 'Grafik Penjualan'
    },
    color: {
        type: String,
        default: '#84cc16'
    }
});

// ApexCharts Options
const chartOptions = computed(() => ({
    chart: {
        type: 'area', // or bar
        height: 250,
        toolbar: { show: false },
        zoom: { enabled: false },
        fontFamily: 'inherit'
    },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
        categories: props.data?.labels || [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: {
            style: { colors: '#9CA3AF', fontSize: '10px' }
        }
    },
    yaxis: {
        labels: {
             style: { colors: '#9CA3AF', fontSize: '10px' },
             formatter: (value) => {
                if(value >= 1000000) return (value/1000000).toFixed(1) + 'jt';
                if(value >= 1000) return (value/1000).toFixed(0) + 'rb';
                return value;
             }
        }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.4,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    colors: [props.color],
    grid: {
        borderColor: '#f3f4f6',
        strokeDashArray: 4,
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    tooltip: {
        y: {
            formatter: function (val) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
            }
        }
    }
}));

const series = computed(() => [{
    name: 'Omset',
    data: props.data?.values || []
}]);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700">
        <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-4 text-sm">{{ title }}</h3>
        <div v-if="data && data.values && data.values.length > 0">
             <apexchart type="area" height="250" :options="chartOptions" :series="series"></apexchart>
        </div>
        <div v-else class="h-[250px] flex items-center justify-center text-gray-400 text-xs italic">
            Belum ada data grafik
        </div>
    </div>
</template>
