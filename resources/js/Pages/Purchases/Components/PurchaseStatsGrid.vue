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
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Belanja Bulan Ini</p>
                <div class="mt-2 flex items-baseline gap-2">
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
            <div class="absolute right-0 bottom-0 opacity-10 text-6xl text-blue-500 transform translate-x-2 translate-y-2 pointer-events-none">
                📅
            </div>
        </div>

        <!-- Card 2: Yearly Spend -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Belanja Tahun Ini</p>
                 <div class="mt-2 flex items-baseline gap-2">
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
             <div class="absolute right-0 bottom-0 opacity-10 text-6xl text-purple-500 transform translate-x-2 translate-y-2 pointer-events-none">
                📈
            </div>
        </div>

        <!-- Card 3: Top Supplier -->
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Top Supplier (Bulan Ini)</p>
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
                        Lihat Analisa Supplier_
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="M12 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
              <div class="absolute right-0 bottom-0 opacity-10 text-6xl text-orange-500 transform translate-x-2 translate-y-2 pointer-events-none">
                🏆
            </div>
        </div>

        <!-- Card 4: Action/Plan -->
        <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-xl p-4 shadow-lg text-white relative overflow-hidden">
             <div class="relative z-10 flex flex-col h-full justify-between">
                <div>
                    <h3 class="font-bold text-lg leading-tight">Perencanaan</h3>
                    <p class="text-xs text-blue-100 opacity-90 mt-1">
                        {{ summary.active_orders }} Order sedang berjalan
                    </p>
                </div>
                <div class="mt-2">
                    <Link :href="route('reports.index')" class="bg-white/20 hover:bg-white/30 transition px-3 py-1.5 rounded-lg text-xs font-bold inline-flex items-center gap-2 backdrop-blur-sm">
                        📄 Buka Laporan Procurement
                    </Link>
                </div>
            </div>
             <!-- Decor -->
            <div class="absolute -right-4 -bottom-4 text-8xl opacity-20 rotate-12 pointer-events-none">
                📝
            </div>
        </div>
    </div>
</template>
