<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({ 
            suppliers: []
        })
    },
    title: {
        type: String,
        default: 'Tren Pengadaan Barang (1 Tahun)'
    },
    range: {
        type: String,
        default: 'Periode 1 Tahun'
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

// Nominal Statistics
const stats = computed(() => {
    const totalArr = props.data?.total || [];
    const total = totalArr.reduce((a, b) => a + b, 0);
    const avg = total / (totalArr.length || 1);
    const max = Math.max(...(totalArr.length ? totalArr : [0]));
    
    return { total, avg, max };
});

// ApexCharts Options
const chartOptions = computed(() => {
    const textColor = isDark.value ? '#E5E7EB' : '#6B7280';
    const gridColor = isDark.value ? 'rgba(55, 65, 81, 0.5)' : '#F3F4F6';
    
    return {
        chart: {
            type: 'area',
            height: 320,
            width: '100%',
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: 'Inter, sans-serif',
            background: 'transparent',
            stacked: false
        },
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: props.data?.labels || [],
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { colors: textColor, fontSize: '10px', fontWeight: 600 }
            }
        },
        yaxis: {
            labels: {
                style: { colors: textColor, fontSize: '10px', fontWeight: 600 },
                formatter: (value) => {
                    if (value >= 1000000) return (value / 1000000).toFixed(0) + 'jt';
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
                opacityFrom: isDark.value ? 0.3 : 0.4,
                opacityTo: 0.05,
                stops: [0, 90, 100]
            }
        },
        colors: isDark.value 
            ? ['#84CC16', '#60A5FA', '#F97316', '#A855F7', '#FACC15', '#EC4899'] 
            : ['#65A30D', '#2563EB', '#EA580C', '#9333EA', '#EAB308', '#DB2777'],
        grid: {
            borderColor: gridColor,
            strokeDashArray: 4,
            padding: { top: 10, right: 10, bottom: 0, left: 10 }
        },
        legend: {
            show: true,
            position: 'top',
            horizontalAlign: 'left',
            labels: { colors: textColor, useSeriesColors: false },
            markers: { radius: 12, width: 10, height: 10 }
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

const series = computed(() => {
    const sArr = [];
    
    // Tambahkan Series Per Supplier
    if (props.data?.suppliers) {
        props.data.suppliers.forEach(s => {
            sArr.push({
                name: s.name,
                data: s.data
            });
        });
    }

    return sArr;
});

const hasData = computed(() => series.value.length > 0);
</script>

<template>
    <div class="bg-white dark:bg-gray-800/95 rounded-3xl p-5 lg:p-7 shadow-sm border border-gray-100 dark:border-gray-700/50 backdrop-blur-sm h-full flex flex-col">
        
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-8">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5">
                    <div class="w-1.5 h-6 bg-orange-500 rounded-full shadow-[0_0_10px_rgba(249,115,22,0.4)]"></div>
                    <h3 class="font-black text-gray-900 dark:text-gray-50 text-base lg:text-xl tracking-tight">{{ title }}</h3>
                </div>
                <div class="flex items-center gap-2 text-[11px] text-gray-400 font-bold ml-4 uppercase tracking-widest">
                    <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>{{ range }}</span>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="flex items-center gap-4 lg:gap-8 px-5 py-3 bg-gray-50 dark:bg-gray-700/30 rounded-2xl border border-gray-100 dark:border-gray-700/50">
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Total Belanja</span>
                    <span class="text-sm lg:text-base font-black text-gray-900 dark:text-white">{{ formatRupiah(stats.total) }}</span>
                </div>
                <div class="w-px h-8 bg-gray-200 dark:bg-gray-700"></div>
                <div class="flex flex-col">
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Rata-rata</span>
                    <span class="text-sm lg:text-base font-black text-lime-600 dark:text-lime-400">{{ formatRupiah(stats.avg) }}</span>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="flex-1 w-full relative z-0 min-h-[320px]">
             <div v-if="!hasData" class="flex flex-col items-center justify-center text-center p-10 space-y-4">
                <div class="w-16 h-16 bg-gray-50 dark:bg-gray-700/50 rounded-2xl flex items-center justify-center mb-2">
                    <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400">Belum Ada Riwayat</h4>
                    <p class="text-[10px] text-gray-400 dark:text-gray-600 max-w-[200px]">Data transaksi 'Selesai' dalam 1 tahun terakhir akan muncul di sini.</p>
                </div>
             </div>
             <apexchart v-else type="area" height="100%" width="100%" :options="chartOptions" :series="series"></apexchart>
        </div>
        
        <div class="mt-6 flex items-center gap-3 text-[10px] font-medium text-gray-400 italic">
            <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p>Grafik menampilkan tren belanja kumulatif serta perbandingan per supplier (Top 5) selama 1 tahun terakhir.</p>
        </div>
    </div>
</template>

<style scoped>
.apexcharts-canvas {
    transition: all 0.3s ease;
}
</style>
