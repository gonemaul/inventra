<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            spend_this_month: 0,
            spend_prev_month: 0,
            trans_this_month: 0,
            trans_prev_month: 0,
            
            spend_this_year: 0,
            spend_prev_year: 0,
            trans_this_year: 0,
            trans_prev_year: 0,

            top_supplier_name: "-",
            top_supplier_amount: 0,
            active_orders: 0
        })
    }
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const calculateGrowth = (curr, prev) => {
    if (!prev || prev === 0) return curr > 0 ? 100 : 0;
    return (((curr - prev) / prev) * 100).toFixed(1);
};

const stats = computed(() => [
    {
        label: "Belanja Bulan Ini",
        value: props.summary.spend_this_month,
        prevValue: props.summary.spend_prev_month,
        trans: props.summary.trans_this_month,
        prevTrans: props.summary.trans_prev_month,
        icon: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
        color: "blue",
        isMain: true
    },
    {
        label: "Belanja Tahun Ini",
        value: props.summary.spend_this_year,
        prevValue: props.summary.spend_prev_year,
        trans: props.summary.trans_this_year,
        prevTrans: props.summary.trans_prev_year,
        icon: "M13 7h8m0 0v8m0-8l-8 8-4-4-6 6",
        color: "orange",
        isMain: true
    },
    {
        label: "Top Supplier",
        value: props.summary.top_supplier_name,
        subValue: formatCurrency(props.summary.top_supplier_amount),
        icon: "M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4",
        color: "emerald",
        isMain: false
    },
    {
        label: "Pesanan Aktif",
        value: props.summary.active_orders,
        subLabel: "PO diproses",
        description: "Menunggu validasi & kirim.",
        icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
        color: "indigo",
        isMain: false,
        isAction: true
    }
]);

</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6">
        <div
            v-for="(stat, idx) in stats"
            :key="idx"
            class="group relative flex flex-col bg-white dark:bg-gray-800/95 border border-gray-200 dark:border-gray-700/50 rounded-2xl lg:rounded-[2rem] shadow-sm hover:shadow-xl transition-all duration-500 overflow-hidden"
        >
            <!-- COLOR ACCENT BAR -->
            <div 
                class="absolute left-0 top-0 bottom-0 w-1 lg:w-1.5 transition-all duration-500"
                :class="[
                    stat.color === 'blue' ? 'bg-blue-500 shadow-[0_0_15px_-3px_rgba(59,130,246,0.5)]' :
                    stat.color === 'orange' ? 'bg-orange-500 shadow-[0_0_15px_-3px_rgba(249,115,22,0.5)]' :
                    stat.color === 'emerald' ? 'bg-emerald-500 shadow-[0_0_15px_-3px_rgba(16,185,129,0.5)]' :
                    'bg-indigo-500 shadow-[0_0_15px_-3px_rgba(99,102,241,0.5)]'
                ]"
            ></div>

            <div class="p-4 sm:p-5 lg:p-6 pl-5 sm:pl-6 lg:pl-8 relative z-10 flex flex-col h-full">
                <!-- Header: Label & Icon -->
                <div class="flex items-start justify-between mb-4 lg:mb-6">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-[9px] sm:text-[10px] lg:text-xs uppercase font-extrabold tracking-[0.15em] text-gray-400 dark:text-gray-500 mb-1 truncate">
                            {{ stat.label }}
                        </h3>
                        <div v-if="stat.isMain" class="flex items-center gap-1.5">
                             <div 
                                class="flex items-center px-1.5 py-0.5 rounded text-[8px] lg:text-[10px] font-black"
                                :class="calculateGrowth(stat.value, stat.prevValue) >= 0 
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' 
                                    : 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400'"
                            >
                                {{ calculateGrowth(stat.value, stat.prevValue) >= 0 ? '▲' : '▼' }} {{ Math.abs(calculateGrowth(stat.value, stat.prevValue)) }}%
                            </div>
                            <span class="text-[8px] lg:text-[10px] font-bold text-gray-400 dark:text-gray-600 tracking-tighter">vs lalu</span>
                        </div>
                    </div>
                    <div 
                        class="p-2 lg:p-2.5 rounded-xl lg:rounded-2xl transition-all duration-300"
                        :class="[
                            stat.color === 'blue' ? 'bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400' :
                            stat.color === 'orange' ? 'bg-orange-50 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400' :
                            stat.color === 'emerald' ? 'bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400' :
                            'bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400'
                        ]"
                    >
                        <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon"></path>
                        </svg>
                    </div>
                </div>

                <!-- Content Body -->
                <div class="flex-1 flex flex-col justify-end mt-2">
                    <!-- MAIN CARDS (Month/Year) -->
                    <div v-if="stat.isMain" class="space-y-4">
                        <div class="flex flex-col">
                            <div class="flex items-baseline gap-1 lg:gap-2">
                                <span class="text-xl sm:text-2xl lg:text-[1.85rem] font-black text-gray-900 dark:text-white tracking-tight leading-none transition-colors" 
                                :class="[
                                    stat.color === 'blue' ? 'group-hover:text-blue-600 dark:group-hover:text-blue-400' :
                                    stat.color === 'orange' ? 'group-hover:text-orange-600 dark:group-hover:text-orange-400' :
                                    stat.color === 'emerald' ? 'group-hover:text-emerald-600 dark:group-hover:text-emerald-400' :
                                    'group-hover:text-indigo-600 dark:group-hover:text-indigo-400'
                                ]">
                                    {{ formatCurrency(stat.value).replace('Rp', '') }}
                                </span>
                                <span class="text-[8px] lg:text-[10px] font-black text-gray-400 dark:text-gray-600 uppercase">IDR</span>
                            </div>
                        </div>

                        <!-- Secondary Metric: Transactions -->
                        <div class="pt-3 lg:pt-4 border-t border-dotted border-gray-100 dark:border-gray-700/80 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-[9px] lg:text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest leading-none mb-1.5">Transaksi</span>
                                <div class="flex items-baseline gap-1">
                                    <span class="text-sm lg:text-base font-black text-gray-800 dark:text-gray-200 leading-none">{{ stat.trans }}</span>
                                    <span class="text-[9px] font-bold text-gray-400 uppercase">PO</span>
                                </div>
                            </div>
                            <!-- Small Trans Growth -->
                            <div 
                                class="text-[9px] font-black"
                                :class="calculateGrowth(stat.trans, stat.prevTrans) >= 0 ? 'text-emerald-500' : 'text-rose-500'"
                            >
                                {{ calculateGrowth(stat.trans, stat.prevTrans) >= 0 ? '▲' : '▼' }} {{ Math.abs(calculateGrowth(stat.trans, stat.prevTrans)) }}%
                            </div>
                        </div>
                    </div>

                    <!-- SUPPLIER CARDS -->
                    <div v-else-if="!stat.isAction" class="flex flex-col h-full justify-between py-1">
                        <div class="flex flex-col gap-1">
                             <h4 class="text-xs sm:text-sm lg:text-base font-bold text-gray-800 dark:text-gray-100 line-clamp-2 leading-snug group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors" :title="stat.value">
                                {{ stat.value }}
                            </h4>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-[10px] lg:text-xs font-black text-gray-900 dark:text-white">{{ stat.subValue }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION/STATUS CARDS -->
                    <div v-else class="flex flex-col">
                         <div class="flex items-baseline gap-2">
                            <span class="text-3xl lg:text-4xl font-black text-gray-900 dark:text-white tracking-tighter group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">{{ stat.value }}</span>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">{{ stat.subLabel }}</span>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-3 font-medium leading-relaxed italic">
                            {{ stat.description }}
                        </p>
                    </div>
                </div>

                <!-- Footer Action for specific card -->
                <div v-if="stat.isAction" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/50">
                    <Link :href="route('reports.index')" class="flex items-center justify-center gap-2 py-2.5 px-4 bg-gray-50 dark:bg-gray-700/50 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl text-[10px] font-black transition-all uppercase tracking-widest border border-transparent hover:border-indigo-100 dark:hover:border-indigo-500/30">
                        Cek Laporan
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Premium shadows & smoothing */
.shadow-sm {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03);
}

.hover\:shadow-xl {
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
}

span, p, h3, h4 {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}
</style>
