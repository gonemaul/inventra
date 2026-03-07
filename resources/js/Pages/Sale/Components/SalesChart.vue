<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    data: Object, // { labels: [], values: [] }
    title: {
        type: String,
        default: 'Grafik Penjualan'
    }
});

const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const totalRevenue = computed(() => {
    if (!props.data?.revenues) return 0;
    return props.data.revenues.reduce((a, b) => a + b, 0);
});

const totalProfit = computed(() => {
    if (!props.data?.profits) return 0;
    return props.data.profits.reduce((a, b) => a + b, 0);
});

const avgRevenue = computed(() => {
    if (!props.data?.revenues || props.data.revenues.length === 0) return 0;
    return totalRevenue.value / props.data.revenues.length;
});

const marginPercent = computed(() => {
    if (totalRevenue.value === 0) return 0;
    return ((totalProfit.value / totalRevenue.value) * 100).toFixed(1);
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
    stroke: { curve: 'smooth', width: [3, 2] },
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
            opacityFrom: 0.45,
            opacityTo: 0.05,
            stops: [0, 90, 100]
        }
    },
    colors: ['#2563eb', '#059669'], // Deeper blue, deeper emerald
    grid: {
        borderColor: '#f3f4f6',
        strokeDashArray: 4,
        padding: { top: 0, right: 0, bottom: 0, left: 10 }
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
        offsetY: -10,
        markers: { radius: 12 }
    },
    tooltip: {
        theme: 'light',
        y: {
            formatter: function (val) {
                return formatRupiah(val);
            }
        }
    }
}));

const series = computed(() => [
    {
        name: 'Omset',
        data: props.data?.revenues || []
    },
    {
        name: 'Laba Bersih',
        data: props.data?.profits || []
    }
]);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-4 lg:p-6 shadow-sm border border-gray-100 dark:border-gray-700 h-full flex flex-col">
        
        <!-- Header & Stats Summary -->
        <div class="flex flex-col sm:flex-row sm:items-start lg:items-center justify-between gap-4 mb-4 border-b border-gray-100 dark:border-gray-700 pb-4">
            <div>
                <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm md:text-base">{{ title }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Ringkasan performa pada periode ini</p>
            </div>
            
            <div v-if="data && data.revenues && data.revenues.length > 0" class="flex flex-wrap items-center gap-4 sm:gap-6 bg-gray-50 dark:bg-gray-900/50 p-2 sm:p-3 rounded-xl border border-gray-100 dark:border-gray-700/50">
                <!-- Avg Revenue -->
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-0.5 whitespace-nowrap">Rata-rata/H</span>
                    <span class="text-xs sm:text-sm font-black text-blue-600 dark:text-blue-400">{{ formatRupiah(avgRevenue) }}</span>
                </div>
                
                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

                <!-- Total Profit -->
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-0.5">Total Laba</span>
                    <span class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatRupiah(totalProfit) }}</span>
                </div>

                <div class="w-px h-6 bg-gray-200 dark:bg-gray-700"></div>

                <!-- Margin -->
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-0.5">Est Margin</span>
                    <span class="text-xs sm:text-sm font-black text-gray-700 dark:text-gray-200">
                        {{ marginPercent }}%
                    </span>
                </div>
            </div>
        </div>

        <div v-if="data && data.revenues && data.revenues.length > 0" class="flex-1 w-full relative z-0 min-h-[250px]">
             <apexchart type="area" height="100%" width="100%" :options="chartOptions" :series="series"></apexchart>
        </div>
        <div v-else class="flex-1 min-h-[250px] flex flex-col items-center justify-center text-gray-400 text-xs italic bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
             <svg class="w-8 h-8 mb-2 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Belum ada data grafik
        </div>
    </div>
</template>
