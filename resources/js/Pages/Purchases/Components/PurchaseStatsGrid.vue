<script setup>
import { computed } from 'vue';

const props = defineProps({
    summary: Object
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Monthly Spend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden group">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-3">
                    <div class="p-2 bg-blue-50 dark:bg-blue-900/20 text-blue-500 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Belanja Bulan Ini</p>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-xl lg:text-2xl font-black text-gray-800 dark:text-gray-100">
                        {{ formatCurrency(summary.spend_this_month) }}
                    </h3>
                </div>
                <div class="mt-2 text-xs flex items-center gap-1">
                     <span 
                        class="px-1.5 py-0.5 rounded font-bold"
                        :class="summary.spend_growth_month >= 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'"
                    >
                        {{ summary.spend_growth_month }}%
                    </span>
                    <span class="text-gray-400">vs bulan lalu</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Yearly Spend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-3">
                    <div class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-500 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Belanja Tahun Ini</p>
                </div>
                 <div class="flex items-baseline gap-2">
                    <h3 class="text-xl lg:text-2xl font-black text-gray-800 dark:text-gray-100">
                        {{ formatCurrency(summary.spend_this_year) }}
                    </h3>
                </div>
                 <div class="mt-2 text-xs flex items-center gap-1">
                    <span 
                        class="px-1.5 py-0.5 rounded font-bold"
                         :class="summary.spend_growth_year >= 0 ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'"
                    >
                        {{ summary.spend_growth_year }}%
                    </span>
                    <span class="text-gray-400">vs tahun lalu</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Top Supplier -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-3">
                    <div class="p-2 bg-orange-50 dark:bg-orange-900/20 text-orange-500 rounded-lg">
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    </div>
                    <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Top Supplier (Bulan Ini)</p>
                </div>
                <div class="mt-2">
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100 truncate" :title="summary.top_supplier_name">
                        {{ summary.top_supplier_name }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ formatCurrency(summary.top_supplier_amount) }}
                    </p>
                </div>
                 <div class="mt-3">
                    <a href="/reports" class="text-[10px] text-blue-600 hover:underline font-semibold flex items-center gap-1">
                        Lihat Analisa Supplier
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card 4: Action/Plan -->
        <div class="bg-gray-900 dark:bg-gray-800 rounded-xl p-4 shadow-lg text-white relative overflow-hidden">
             <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="p-1.5 bg-lime-500/20 text-lime-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </div>
                        <h3 class="font-bold text-sm leading-tight text-white tracking-wider uppercase">Perencanaan</h3>
                    </div>
                    <p class="text-xs text-gray-300 opacity-90 mt-1">
                        <strong class="text-lime-400 text-base">{{ summary.active_orders }}</strong> Order sedang berjalan
                    </p>
                </div>
                <div class="mt-3">
                    <Link :href="route('reports.index')" class="bg-white/10 hover:bg-white/20 transition px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-2 backdrop-blur-sm">
                        <svg class="w-4 h-4 text-lime-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Buka Laporan
                    </Link>
                </div>
            </div>
             <!-- Decor -->
            <div class="absolute -right-6 -bottom-6 opacity-5 pointer-events-none text-lime-500">
               <svg class="w-32 h-32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
        </div>
    </div>
</template>
