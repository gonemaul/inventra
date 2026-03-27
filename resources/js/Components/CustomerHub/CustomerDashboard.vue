<script setup>
defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    dss: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                <svg class="w-12 h-12 text-lime-500" fill="currentColor" viewBox="0 0 24 24"><path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Kendaraan</span>
            <span class="text-3xl font-black text-gray-900 dark:text-white">{{ stats?.total_vehicles || 0 }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between relative overflow-hidden group">
            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                <svg class="w-12 h-12 text-blue-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Servis Hari Ini</span>
            <span class="text-3xl font-black text-gray-900 dark:text-white">{{ stats?.service_today || 0 }}</span>
        </div>

        <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between relative overflow-hidden group">
             <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                <svg class="w-12 h-12 text-amber-500" fill="currentColor" viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 17.08l1.5 1.41z"/></svg>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Rata-rata KM</span>
            <span class="text-3xl font-black text-gray-900 dark:text-white">{{ (stats?.avg_km || 0).toLocaleString() }}</span>
        </div>

        <!-- DSS WIDGET (Compact) -->
        <div class="bg-gradient-to-br from-lime-500 to-green-600 p-4 sm:p-6 rounded-3xl shadow-lg border border-lime-400 flex flex-col justify-between relative overflow-hidden group text-white">
            <div class="absolute top-0 right-0 p-4 opacity-20 group-hover:scale-110 transition-transform">
                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <div>
                <span class="text-[10px] font-black uppercase tracking-widest mb-1 opacity-90 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    Jatuh Tempo (30 Hari)
                </span>
                <p class="text-3xl font-black leading-tight">{{ dss?.due_next_30_days || 0 }} <span class="text-sm font-bold opacity-80">Motor</span></p>
            </div>
        </div>
    </div>

    <!-- NEW PANELS: Detailed DSS Insights -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Predicted Oils -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                <div>
                    <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="text-lg">🔥</span> Prediksi Produk (30 Hari)
                    </h3>
                    <p class="text-[10px] text-gray-500 mt-1 cursor-help" title="Diprediksi otomatis oleh Smart DSS">Berdasarkan data lampau kendaraan</p>
                </div>
                <span class="px-3 py-1 bg-lime-500/10 text-lime-600 dark:text-lime-400 text-[10px] font-black rounded-full border border-lime-500/20">AI DSS</span>
            </div>
            <div class="p-5 flex-1">
                <div class="space-y-5">
                    <div>
                        <h4 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-gray-700 pb-1">Daftar Kebutuhan Oli Mesin</h4>
                        <div v-if="Object.keys(dss?.engine_oil_predictions || {}).length > 0" class="space-y-2">
                            <div v-for="(qty, name) in dss.engine_oil_predictions" :key="name" class="flex justify-between items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-xl transition">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ name }}</span>
                                <span class="text-[10px] font-black text-lime-600 bg-lime-50 dark:bg-lime-900/30 px-2 py-0.5 rounded-md">{{ qty }} Botol</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 p-2 italic">Belum ada estimasi pergantian oli mesin.</p>
                    </div>
                    <div>
                        <h4 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-3 border-b border-gray-100 dark:border-gray-700 pb-1">Daftar Kebutuhan Oli Gardan</h4>
                        <div v-if="Object.keys(dss?.gear_oil_predictions || {}).length > 0" class="space-y-2">
                            <div v-for="(qty, name) in dss.gear_oil_predictions" :key="name" class="flex justify-between items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-xl transition">
                                <span class="text-xs font-bold text-gray-800 dark:text-gray-200">{{ name }}</span>
                                <span class="text-[10px] font-black text-amber-600 bg-amber-50 dark:bg-amber-900/30 px-2 py-0.5 rounded-md">{{ qty }} Botol</span>
                            </div>
                        </div>
                        <p v-else class="text-xs text-gray-400 p-2 italic">Belum ada estimasi pergantian oli gardan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approaching Vehicles List -->
        <div class="bg-white dark:bg-gray-800 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden flex flex-col">
            <div class="p-6 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                <div>
                    <h3 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest flex items-center gap-2">
                        <span class="text-lg">📅</span> Reminder Kendaraan
                    </h3>
                    <p class="text-[10px] text-gray-500 mt-1 cursor-help" title="Data diurutkan berdasarkan yang paling mendesak">Mendekati estimasi ganti oli</p>
                </div>
            </div>
            <div class="p-5 flex-1 max-h-[450px] overflow-y-auto">
                <div v-if="dss?.upcoming_vehicles && dss.upcoming_vehicles.length > 0" class="space-y-3">
                    <div v-for="v in dss.upcoming_vehicles" :key="v.id" class="flex justify-between items-center p-3 border border-gray-100 dark:border-gray-700 rounded-2xl hover:border-blue-300 dark:hover:border-blue-700 transition group cursor-default">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-500 font-bold shadow-inner group-hover:scale-105 transition-transform shrink-0">
                                🛵
                            </div>
                            <div>
                                <p class="text-xs font-black text-gray-900 dark:text-white tracking-widest uppercase">{{ v.plate_number }}</p>
                                <p class="text-[10px] text-gray-500 truncate w-24 sm:w-32">{{ v.brand }} {{ v.model }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-[10px] font-bold uppercase tracking-widest" :class="new Date(v.next_engine_date) < new Date() ? 'text-red-500 dark:text-red-400' : 'text-blue-500 dark:text-blue-400'">
                                {{ new Date(v.next_engine_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
                            </p>
                            <p class="text-[9px] text-gray-400 truncate w-20 sm:w-24 mt-0.5" :title="v.engine_oil_name">{{ v.engine_oil_name }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-10 opacity-50">
                    <span class="text-3xl mb-2 block">✅</span>
                    <p class="text-xs font-black text-gray-500 uppercase tracking-widest">Semua Aman</p>
                    <p class="text-[10px] text-gray-400 font-bold mt-2">Tidak ada kendaraan yang perlu diingatkan.</p>
                </div>
            </div>
        </div>
    </div>
</template>
