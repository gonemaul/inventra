<script setup>
const props = defineProps({
    inventory: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div class="space-y-6">
        <!-- Main Forecasting Card -->
        <div class="p-5 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700">
             <div class="flex items-center justify-between mb-6">
                <div>
                     <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🔮</span> Prediksi & Rekomendasi
                    </h2>
                    <p class="text-xs text-gray-500 mt-1 dark:text-gray-400">Analisis cerdas berdasarkan riwayat penjualan</p>
                </div>
                
                <span
                    v-if="inventory.stock_status === 'critical'"
                    class="px-3 py-1 text-xs font-black uppercase tracking-wider text-red-600 bg-red-50 border border-red-100 rounded-lg animate-pulse dark:bg-red-900/20 dark:border-red-800 dark:text-red-400"
                    >KRITIS</span
                >
                <span
                    v-else-if="inventory.stock_status === 'warning'"
                    class="px-3 py-1 text-xs font-black uppercase tracking-wider text-yellow-600 bg-yellow-50 border border-yellow-100 rounded-lg dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-400"
                    >WASPADA</span
                >
                <span
                    v-else
                    class="px-3 py-1 text-xs font-black uppercase tracking-wider text-green-600 bg-green-50 border border-green-100 rounded-lg dark:bg-green-900/20 dark:border-green-800 dark:text-green-400"
                    >AMAN</span
                >
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left: Metrics -->
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-blue-500">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                        </div>
                        <div>
                             <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Kecepatan Penjualan</p>
                             <p class="text-2xl font-black text-gray-900 dark:text-white">
                                {{ inventory.avg_daily }} 
                                <span class="text-sm font-medium text-gray-400">unit/hari</span>
                             </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                         <div class="p-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-gray-500">
                             <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                         </div>
                         <div>
                             <p class="text-xs font-bold text-gray-400 uppercase tracking-wide">Estimasi Stok Habis</p>
                             <template v-if="inventory.avg_daily > 0">
                                <p class="text-2xl font-black text-gray-900 dark:text-white">
                                    {{ inventory.days_left }} 
                                    <span class="text-sm font-medium text-gray-400">Hari Lagi</span>
                                </p>
                                <p class="text-xs px-2 py-0.5 mt-1 bg-red-50 text-red-600 rounded inline-block font-mono">
                                    Tgl: {{ inventory.stockout_date }}
                                </p>
                             </template>
                             <p v-else class="text-sm text-gray-500 italic mt-1">Belum cukup data untuk prediksi.</p>
                         </div>
                    </div>
                </div>

                <!-- Right: Recommendation Action -->
                <div class="relative overflow-hidden rounded-xl border border-blue-100 bg-gradient-to-br from-blue-50 to-white dark:from-blue-900/10 dark:to-gray-800 dark:border-blue-800 p-6 flex flex-col justify-center items-center text-center">
                    
                    <template v-if="inventory.suggested_qty > 0">
                        <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mb-2">Rekomendasi Restock</p>
                        <div class="text-5xl font-black text-blue-600 dark:text-blue-400 mb-1">
                            {{ inventory.suggested_qty }}
                        </div>
                         <p class="text-xs text-gray-500 mb-6">Unit untuk stok aman 14 hari</p>
                         
                         <button class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 transition-all flex items-center justify-center gap-2">
                             <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                             Buat Purchase Order
                         </button>
                    </template>
                    
                    <template v-else>
                         <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-2xl mb-3 dark:bg-green-900/20 dark:text-green-400">
                             👍
                         </div>
                         <h3 class="font-bold text-gray-900 dark:text-white mb-1">Stok Aman</h3>
                         <p class="text-sm text-gray-500 dark:text-gray-400">Tidak perlu melakukan pembelian ulang saat ini.</p>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
