<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({ labels: [], revenues: [], profits: [] })
    },
    title: {
        type: String,
        default: 'Grafik Penjualan'
    },
    range: {
        type: String,
        default: ''
    }
});

const formatRupiah = (val) => new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR', 
    minimumFractionDigits: 0 
}).format(val);

// Theme Detection
const isDark = ref(false);
let observer = null;

const checkTheme = () => {
    isDark.value = document.documentElement.classList.contains('dark');
};

onMounted(() => {
    checkTheme();
    observer = new MutationObserver(checkTheme);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// Nominal Statistics Calculations
const stats = computed(() => {
    const rev = props.data?.revenues || [];
    const prof = props.data?.profits || [];
    
    const calculate = (arr) => {
        const filtered = arr.filter(v => v > 0);
        const total = arr.reduce((a, b) => a + b, 0);
        
        if (!filtered.length) return { total, avg: 0, max: 0, min: 0 };
        
        return {
            total,
            avg: total / filtered.length,
            max: Math.max(...filtered),
            min: Math.min(...filtered)
        };
    };

    return {
        revenue: calculate(rev),
        profit: calculate(prof)
    };
});

const marginPercent = computed(() => {
    if (stats.value.revenue.total === 0) return 0;
    return ((stats.value.profit.total / stats.value.revenue.total) * 100).toFixed(1);
});

// ApexCharts Options
const chartOptions = computed(() => {
    const textColor = isDark.value ? '#E5E7EB' : '#6B7280';
    const gridColor = isDark.value ? 'rgba(55, 65, 81, 0.5)' : '#F3F4F6';
    
    return {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent'
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: [3, 2], dashArray: [0, 4] },
        xaxis: {
            categories: props.data?.labels || [],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { colors: textColor, fontSize: '10px', fontWeight: 500 }
            }
        },
        yaxis: {
            labels: {
                style: { colors: textColor, fontSize: '10px', fontWeight: 500 },
                formatter: (value) => {
                    if (value >= 1000000) return (value / 1000000).toFixed(1) + 'jt';
                    if (value >= 1000) return (value / 1000).toFixed(0) + 'rb';
                    return value;
                }
            }
        },
        fill: {
            type: 'gradient',
            gradient: {
                shade: isDark.value ? 'dark' : 'light',
                shadeIntensity: 1,
                opacityFrom: isDark.value ? 0.4 : 0.5,
                opacityTo: isDark.value ? 0.05 : 0.1,
                stops: [0, 90, 100]
            }
        },
        colors: isDark.value ? ['#60A5FA', '#34D399'] : ['#2563EB', '#059669'],
        grid: {
            borderColor: gridColor,
            strokeDashArray: 4,
            padding: { top: 10, right: 10, bottom: 0, left: 10 }
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'right',
            labels: { colors: textColor },
            markers: { radius: 12 }
        },
        tooltip: {
            theme: isDark.value ? 'dark' : 'light',
            x: { show: true },
            y: {
                formatter: (val) => formatRupiah(val)
            }
        }
    };
});

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
    <div class="bg-white dark:bg-gray-800/95 rounded-3xl p-5 lg:p-7 shadow-sm border border-gray-100 dark:border-gray-700/50 backdrop-blur-sm h-full flex flex-col">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-1.2 h-5 bg-blue-600 dark:bg-blue-500 rounded-full"></div>
                    <h3 class="font-black text-gray-900 dark:text-gray-50 text-base lg:text-lg tracking-tight">{{ title }}</h3>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-gray-400 font-medium ml-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>{{ range || 'Periode Aktif' }}</span>
                </div>
            </div>
        </div>

        <!-- Simplified Numerical Summary (Symmetrical Grid) -->
        <div v-if="data?.revenues?.length" class="mb-6 space-y-4 px-1">
            <!-- Table Header (Labels) - Hidden on mobile if needed, but lets keep it simple -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-y-4 gap-x-2 sm:gap-x-8">
                
                <!-- Row: Omset -->
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 text-[9px] uppercase font-bold text-gray-400 tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                        <span>Total Omset</span>
                    </div>
                    <p class="text-xs sm:text-sm font-black text-gray-900 dark:text-gray-100 leading-none">{{ formatRupiah(stats.revenue.total) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-gray-400 tracking-wider">Avg Omset</span>
                    <p class="text-xs sm:text-sm font-black text-gray-700 dark:text-gray-300 leading-none">{{ formatRupiah(stats.revenue.avg) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-emerald-500/80 tracking-wider">Peak Omset</span>
                    <p class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ formatRupiah(stats.revenue.max) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-amber-500/80 tracking-wider">Min Omset</span>
                    <p class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-500 leading-none">{{ formatRupiah(stats.revenue.min) }}</p>
                </div>

                <!-- Row: Laba -->
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1.5 text-[9px] uppercase font-bold text-gray-400 tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <span>Total Laba</span>
                    </div>
                    <p class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ formatRupiah(stats.profit.total) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-gray-400 tracking-wider">Avg Laba</span>
                    <p class="text-xs sm:text-sm font-black text-gray-700 dark:text-gray-300 leading-none">{{ formatRupiah(stats.profit.avg) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-emerald-500/80 tracking-wider">Peak Laba</span>
                    <p class="text-xs sm:text-sm font-black text-emerald-600 dark:text-emerald-400 leading-none">{{ formatRupiah(stats.profit.max) }}</p>
                </div>

                <div class="flex flex-col gap-1 border-l border-gray-100 dark:border-gray-800 pl-3">
                    <span class="text-[9px] uppercase font-bold text-amber-500/80 tracking-wider">Min Laba</span>
                    <p class="text-xs sm:text-sm font-black text-amber-600 dark:text-amber-500 leading-none">{{ formatRupiah(stats.profit.min) }}</p>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div v-if="data?.labels?.length" class="flex-1 w-full relative z-0 min-h-[300px]">
             <apexchart type="area" height="100%" width="100%" :options="chartOptions" :series="series"></apexchart>
        </div>
        
        <!-- Empty State -->
        <div v-else class="flex-1 min-h-[300px] flex flex-col items-center justify-center text-gray-400 text-xs italic bg-gray-50/50 dark:bg-gray-800/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
             <div class="w-12 h-12 mb-3 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-300 dark:text-gray-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
             </div>
             <p class="font-medium text-gray-500 dark:text-gray-400">Belum ada data grafik</p>
        </div>
    </div>
</template>

<style scoped>
/* Smooth transition for theme changes */
.apexcharts-canvas {
    transition: all 0.3s ease;
}
</style>
