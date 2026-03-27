<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';

const props = defineProps({
    vehicles: {
        type: [Array, Object],
        default: () => []
    }
});

const emit = defineEmits(['add', 'edit', 'select']);

const search = ref('');
const filteredVehicles = ref(Array.isArray(props.vehicles) ? props.vehicles : (props.vehicles?.data || []));

watch(() => props.vehicles, (newVal) => {
    if (!search.value) {
        filteredVehicles.value = Array.isArray(newVal) ? newVal : (newVal?.data || []);
    }
});

const handleSearch = debounce(async () => {
    if (!search.value) {
        filteredVehicles.value = props.vehicles;
        return;
    }
    
    try {
        const response = await axios.get(route('api.vehicles.search'), {
            params: { query: search.value }
        });
        filteredVehicles.value = response.data;
    } catch (error) {
        console.error("Search failed", error);
    }
}, 300);

watch(search, handleSearch);
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Eksplorasi Kendaraan</h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Temukan riwayat servis dengan cepat.</p>
            </div>
            <button 
                @click="$emit('add')"
                class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-lime-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-lime-600 shadow-lg shadow-lime-500/30 transition-all active:scale-95"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Motor
            </button>
        </div>

        <!-- Search Input -->
        <div class="relative mb-8">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                v-model="search"
                type="text" 
                placeholder="Cari Plat Nomor atau Model..."
                class="block w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl text-gray-900 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-lime-500 font-bold transition-all uppercase"
            />
        </div>

        <!-- Vehicle List -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
                v-for="vehicle in filteredVehicles" 
                :key="vehicle.id"
                class="group p-4 bg-gray-50 dark:bg-gray-900/30 rounded-3xl border border-gray-100 dark:border-gray-800 hover:border-lime-500 dark:hover:border-lime-500 hover:bg-white dark:hover:bg-gray-800 transition-all cursor-pointer relative overflow-hidden shadow-sm hover:shadow-xl"
            >
                <div class="absolute top-2 right-2 flex gap-1">
                    <button @click.stop="$emit('edit', vehicle)" class="p-2 bg-white dark:bg-gray-700 text-gray-400 hover:text-blue-500 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600 transition opacity-0 group-hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </button>
                    <button @click.stop="$emit('select', vehicle)" class="p-2 bg-lime-500 text-white rounded-lg shadow-lg shadow-lime-500/20 transition opacity-0 group-hover:opacity-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div @click="$emit('select', vehicle)" class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-white dark:bg-gray-800 rounded-2xl shadow-sm flex items-center justify-center shrink-0 border border-gray-50 dark:border-gray-700">
                        <span class="text-3xl" v-if="vehicle.engine_type === 'matic'">🛵</span>
                        <span class="text-3xl" v-else>🏍️</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-lg font-black text-gray-900 dark:text-white tracking-widest uppercase leading-tight truncate">{{ vehicle.plate_number }}</h4>
                        <p class="text-xs font-bold text-gray-500 dark:text-gray-400 truncate">{{ vehicle.brand }} {{ vehicle.model }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 text-[9px] bg-lime-500/10 text-lime-600 rounded-md uppercase font-black tracking-tighter">{{ vehicle.engine_type }}</span>
                            <span v-if="vehicle.color" class="px-2 py-0.5 text-[9px] bg-gray-500/10 text-gray-600 dark:text-gray-400 rounded-md uppercase font-black tracking-tighter">{{ vehicle.color }}</span>
                            <span v-if="vehicle.security_code" class="px-2 py-0.5 text-[9px] bg-blue-500/10 text-blue-600 rounded-md font-black tracking-tighter">SECURED</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div v-if="filteredVehicles.length === 0" class="col-span-full py-12 text-center bg-gray-50 dark:bg-gray-900/50 rounded-3xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                <div class="text-4xl mb-4">🔍</div>
                <p class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Tidak ada hasil ditemukan</p>
                <p class="text-xs text-gray-400 mt-1">Coba kata kunci lain atau daftarkan kendaraan baru.</p>
            </div>
        </div>
    </div>
</template>
