<script setup>
import { defineProps, defineEmits } from 'vue';
import BottomSheet from '@/Components/BottomSheet.vue';

const props = defineProps({
    show: Boolean,
    vehicle: Object,
    serviceHistory: Array,
    isLoading: Boolean,
});

const emit = defineEmits(['close']);

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};
</script>

<template>
    <BottomSheet 
        :show="show" 
        :title="'Riwayat Servis ' + (vehicle?.plate_number || '')" 
        @close="$emit('close')"
    >
        <div class="space-y-6 pb-4">
            <div v-if="isLoading" class="py-20 flex flex-col items-center justify-center opacity-50">
                <div class="animate-spin rounded-full h-10 w-10 border-4 border-lime-500 border-t-transparent mb-4"></div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Memuat histori...</p>
            </div>
            
            <div v-else-if="serviceHistory && serviceHistory.length > 0" class="space-y-4">
                <div 
                    v-for="(log, idx) in serviceHistory" 
                    :key="log.id"
                    class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800 group transition-all"
                >
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">{{ formatDate(log.created_at) }}</span>
                            <h5 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">Servis Rutin #{{ serviceHistory.length - idx }}</h5>
                        </div>
                        <span class="px-2 py-1 bg-lime-500/10 text-lime-600 dark:text-lime-400 text-[9px] font-black rounded-lg uppercase border border-lime-500/20">Selesai</span>
                    </div>
                    
                    <!-- KM Saat Servis -->
                    <div class="mb-5 flex items-center gap-3">
                        <div class="w-10 h-10 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-center border border-gray-100 dark:border-gray-700 shadow-sm shrink-0">
                            <span class="text-lg">🛣️</span>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Odometer Saat Servis</p>
                            <p class="text-sm font-black text-gray-900 dark:text-white">{{ log.current_km ? log.current_km.toLocaleString() : '???' }} <span class="text-[10px] text-gray-500 font-bold">KM</span></p>
                        </div>
                    </div>

                    <!-- Target Mesin -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                        <div class="bg-blue-50/50 dark:bg-blue-900/20 p-4 rounded-2xl border border-blue-100 dark:border-blue-800/50 relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <p class="text-[10px] font-black text-blue-800 dark:text-blue-300 uppercase tracking-widest mb-1 flex items-center gap-1">
                                    <span class="text-sm">🔧</span> Oli Mesin
                                </p>
                                <p class="text-[11px] font-bold text-gray-700 dark:text-gray-300 line-clamp-2 leading-tight mb-3">
                                    {{ log.engine_oil?.name || 'Tidak Diganti' }}
                                </p>
                            </div>
                            
                            <div class="pt-3 border-t border-blue-200 dark:border-blue-800/50 flex justify-between items-end mt-auto">
                                <div>
                                    <p class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase">Target KM</p>
                                    <p class="text-xs font-black text-blue-900 dark:text-white">{{ log.next_engine_oil_km ? log.next_engine_oil_km.toLocaleString() : '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase">Target Tgl</p>
                                    <p class="text-xs font-black text-blue-900 dark:text-white">{{ formatDate(log.next_engine_oil_date) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Target Gardan -->
                        <div v-if="vehicle?.engine_type === 'matic'" class="bg-amber-50/50 dark:bg-amber-900/20 p-4 rounded-2xl border border-amber-100 dark:border-amber-800/50 relative overflow-hidden flex flex-col justify-between">
                            <div>
                                <p class="text-[10px] font-black text-amber-800 dark:text-amber-300 uppercase tracking-widest mb-1 flex items-center gap-1">
                                    <span class="text-sm">⚙️</span> Oli Gardan
                                </p>
                                <p class="text-[11px] font-bold text-gray-700 dark:text-gray-300 line-clamp-2 leading-tight mb-3">
                                    {{ log.gear_oil?.name || 'Tidak Diganti / Cek Berkala' }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-amber-200 dark:border-amber-800/50 flex justify-between items-end mt-auto">
                                <div>
                                    <p class="text-[9px] font-bold text-amber-600 dark:text-amber-400 uppercase">Target KM</p>
                                    <p class="text-xs font-black text-amber-900 dark:text-white">{{ log.next_gear_oil_km ? log.next_gear_oil_km.toLocaleString() : '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] font-bold text-amber-600 dark:text-amber-400 uppercase">Target Tgl</p>
                                    <p class="text-xs font-black text-amber-900 dark:text-white">{{ formatDate(log.next_gear_oil_date) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div v-if="log.notes" class="mt-3 p-3 bg-white dark:bg-gray-800 rounded-2xl text-[10px] text-gray-600 dark:text-gray-300 italic border border-gray-100 dark:border-gray-700 shadow-sm relative pt-4">
                        <span class="absolute -top-2 left-3 bg-white dark:bg-gray-800 px-1 text-[8px] font-black text-gray-400 uppercase border border-gray-100 dark:border-gray-700 rounded shadow-sm">Catatan Mekanik</span>
                        "{{ log.notes }}"
                    </div>
                </div>
            </div>

            <div v-else class="py-20 text-center">
                <div class="text-6xl mb-6 grayscale opacity-30">📂</div>
                <p class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Belum Ada Histori</p>
                <p class="text-[10px] text-gray-400 font-bold mt-2">Kendaraan ini belum pernah melakukan servis oli melalui POS.</p>
            </div>
        </div>
    </BottomSheet>
</template>
