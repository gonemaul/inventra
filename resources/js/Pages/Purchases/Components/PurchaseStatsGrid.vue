<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    summary: Object
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">
        <!-- Card 1: Monthly Spend -->
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
            <!-- Background Glow/Gradient -->
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[11px] font-bold tracking-widest text-gray-500 dark:text-gray-400 uppercase">Belanja Bulan Ini</p>
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-xl shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-2xl lg:text-3xl font-black text-gray-800 dark:text-white tracking-tight">
                        {{ formatCurrency(summary.spend_this_month) }}
                    </h3>
                    <div class="mt-3 flex items-center gap-2">
                        <span 
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-bold"
                            :class="summary.spend_growth_month >= 0 ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400'"
                        >
                            <svg v-if="summary.spend_growth_month >= 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            {{ Math.abs(summary.spend_growth_month) }}%
                        </span>
                        <span class="text-[10px] text-gray-400 font-medium">vs bulan lalu</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Yearly Spend -->
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl group-hover:bg-purple-500/20 transition-all duration-500"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-[11px] font-bold tracking-widest text-gray-500 dark:text-gray-400 uppercase">Total Tahun Ini</p>
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 rounded-xl shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                </div>
                 <div>
                    <h3 class="text-2xl lg:text-3xl font-black text-gray-800 dark:text-white tracking-tight">
                        {{ formatCurrency(summary.spend_this_year) }}
                    </h3>
                     <div class="mt-3 flex items-center gap-2">
                        <span 
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-bold"
                            :class="summary.spend_growth_year >= 0 ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400'"
                        >
                            <svg v-if="summary.spend_growth_year >= 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path></svg>
                            {{ Math.abs(summary.spend_growth_year) }}%
                        </span>
                        <span class="text-[10px] text-gray-400 font-medium">vs tahun lalu</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Top Supplier -->
        <div class="group relative bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
            <div class="absolute -right-6 -top-6 w-32 h-32 bg-orange-500/10 rounded-full blur-3xl group-hover:bg-orange-500/20 transition-all duration-500"></div>
            
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-bold tracking-widest text-gray-500 dark:text-gray-400 uppercase">Top Supplier (Bln Ini)</p>
                    <div class="p-2 bg-orange-50 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 rounded-xl shadow-inner">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                </div>
                <div class="flex-1 flex flex-col justify-center">
                    <h3 class="text-lg font-black text-gray-800 dark:text-white truncate" :title="summary.top_supplier_name">
                        {{ summary.top_supplier_name }}
                    </h3>
                    <p class="text-sm font-semibold text-gray-500 dark:text-gray-400 mt-1">
                        {{ formatCurrency(summary.top_supplier_amount) }}
                    </p>
                </div>
                 <div class="mt-2 pt-3 border-t border-gray-50 dark:border-gray-700/50">
                    <a href="/reports" class="inline-flex items-center gap-1 text-[11px] font-bold text-gray-400 hover:text-orange-500 transition-colors uppercase tracking-wider">
                        Analisa Laporan
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card 4: Action/Plan (Gradient Background) -->
        <div class="group relative bg-gradient-to-br from-indigo-900 to-gray-900 rounded-2xl p-5 shadow-lg overflow-hidden flex flex-col justify-between">
            <!-- Background Elements -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4wNSkiLz48L3N2Zz4=')] opacity-50 mix-blend-overlay"></div>
            <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-lime-500/20 rounded-full blur-3xl group-hover:scale-110 transition-transform duration-700"></div>
            
             <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2.5 bg-white/10 text-lime-400 rounded-xl backdrop-blur-sm border border-white/5 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm tracking-widest text-white/90 uppercase">Status Pesanan</h3>
                    </div>
                </div>
                <div>
                     <div class="flex items-baseline gap-2">
                        <span class="text-4xl font-black text-white tracking-tighter">{{ summary.active_orders }}</span>
                        <span class="text-xs font-medium text-indigo-200">Pesanan Aktif</span>
                    </div>
                    <p class="text-xs text-white/60 mt-2 leading-relaxed">
                        Total PO dalam proses pengiriman atau butuh validasi stok.
                    </p>
                </div>
            </div>
            
            <div class="mt-4 relative z-10">
                <Link :href="route('reports.index')" class="w-full flex justify-center items-center gap-2 py-2.5 px-4 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition-all backdrop-blur-md border border-white/10 hover:border-white/20">
                    Buka Laporan
                    <svg class="w-4 h-4 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </Link>
            </div>
        </div>
    </div>
</template>
