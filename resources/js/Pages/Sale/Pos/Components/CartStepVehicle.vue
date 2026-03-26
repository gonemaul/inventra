<script setup>
import { ref, computed, watch } from "vue";
import { usePosState } from "@/Composables/POS/usePosState";
import { storeToRefs } from "pinia";
import { Link } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";

const posState = usePosState();
const { activeDraft } = storeToRefs(posState);
const { nextStep, rp } = posState;

const plateInput = ref("");
const searchResults = ref([]);
const isSearching = ref(false);
const showDropdown = ref(false);

// Smart search: debounced autocomplete via api.vehicles.search
const doSearch = debounce(async (query) => {
    if (!query || query.length < 2) {
        searchResults.value = [];
        showDropdown.value = false;
        return;
    }
    isSearching.value = true;
    try {
        const res = await axios.get(route('api.vehicles.search'), {
            params: { query: query.replace(/\s/g, '') } // strip spaces client-side too
        });
        searchResults.value = res.data || [];
        showDropdown.value = searchResults.value.length > 0;
    } catch (e) {
        console.error("Vehicle search error:", e);
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 350);

watch(plateInput, (val) => {
    doSearch(val);
});

const selectVehicle = async (vehicle) => {
    showDropdown.value = false;
    plateInput.value = vehicle.plate_number;
    activeDraft.value.serviceData.vehicle = vehicle;
    activeDraft.value.selected_vehicle = vehicle;

    // Fetch last service info
    activeDraft.value.serviceData.is_fetching_vehicle = true;
    try {
        const res = await axios.get(route('api.vehicles.info'), {
            params: { plate_number: vehicle.plate_number }
        });
        if (res.data.status === 'success') {
            activeDraft.value.serviceData.last_service = res.data.data.last_service;
        }
    } catch (e) {
        console.error(e);
    } finally {
        activeDraft.value.serviceData.is_fetching_vehicle = false;
    }
};

const skipVehicle = () => {
    activeDraft.value.serviceData.vehicle = null;
    activeDraft.value.serviceData.plate_number = "";
    activeDraft.value.selected_vehicle = null;
    nextStep();
};

const clearVehicle = () => {
    activeDraft.value.serviceData.vehicle = null;
    activeDraft.value.serviceData.last_service = null;
    activeDraft.value.selected_vehicle = null;
    plateInput.value = "";
    searchResults.value = [];
};

const proceedWithVehicle = () => {
    nextStep();
};

const noResults = computed(() => {
    return plateInput.value.length >= 2 && !isSearching.value && searchResults.value.length === 0 && showDropdown.value === false;
});
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Scrollable Content -->
        <div class="max-h-[calc(100vh-175px)] flex-1 px-5 py-4 overflow-y-auto custom-scroll space-y-4">

            <!-- STATE: No vehicle selected -->
            <div v-if="!activeDraft.serviceData.vehicle">
                <div class="text-center pt-4 pb-3">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/30 mb-2">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10M13 16l3-8h4l3 8" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-white">Data Kendaraan</h3>
                    <p class="text-xs text-gray-400 mt-1">Ketik plat nomor untuk mencari otomatis</p>
                </div>

                <!-- Smart Search Input with Autocomplete -->
                <div class="relative">
                    <input
                        v-model="plateInput"
                        type="text"
                        placeholder="Cth: B1234XYZ atau b 1234 xyz"
                        class="w-full text-sm p-3 pl-10 rounded-xl border border-blue-200 dark:border-blue-700 bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition uppercase"
                        @focus="plateInput.length >= 2 && (showDropdown = searchResults.length > 0)"
                        @blur="setTimeout(() => showDropdown = false, 200)"
                    />
                    <svg class="w-4 h-4 text-blue-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>

                    <!-- Loading spinner -->
                    <div v-if="isSearching" class="absolute right-3 top-3.5">
                        <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <!-- Autocomplete Dropdown -->
                    <div
                        v-if="showDropdown && searchResults.length > 0"
                        class="absolute left-0 right-0 z-50 mt-1 bg-white dark:bg-gray-800 border border-blue-200 dark:border-blue-700 rounded-xl shadow-xl overflow-hidden"
                    >
                        <button
                            v-for="v in searchResults"
                            :key="v.id"
                            @mousedown.prevent="selectVehicle(v)"
                            class="w-full text-left p-3 hover:bg-blue-50 dark:hover:bg-blue-900/30 border-b border-gray-100 dark:border-gray-700 last:border-0 transition"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-bold text-blue-900 dark:text-blue-100 tracking-wider">{{ v.plate_number }}</span>
                                    <span class="ml-2 px-1.5 py-0.5 bg-blue-100 dark:bg-blue-900 text-[10px] font-bold rounded uppercase text-blue-600 dark:text-blue-300">{{ v.engine_type }}</span>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ v.brand }} — {{ v.model }}</p>
                        </button>
                    </div>
                </div>

                <!-- Not Found + Register Link -->
                <div v-if="noResults || (plateInput.length >= 3 && !isSearching && searchResults.length === 0)" class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 text-center">
                    <p class="text-xs text-amber-700 dark:text-amber-300 font-medium mb-2">Kendaraan tidak ditemukan</p>
                    <Link
                        :href="route('customer-hub')"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs font-bold rounded-lg transition"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Daftarkan di Customer Hub
                    </Link>
                </div>

                <!-- Skip Button -->
                <button
                    @click="skipVehicle"
                    class="w-full mt-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-sm font-bold text-gray-500 dark:text-gray-400 hover:border-blue-400 hover:text-blue-500 transition"
                >
                    Tanpa Plat / Lewati →
                </button>
            </div>

            <!-- STATE: Vehicle selected -->
            <div v-else class="space-y-4">
                <!-- Vehicle Card -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/30 dark:to-blue-800/20 p-4 rounded-2xl border border-blue-200 dark:border-blue-800">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-lg font-black text-blue-900 dark:text-blue-100 tracking-wider">
                                    {{ activeDraft.serviceData.vehicle.plate_number }}
                                </span>
                                <span class="px-2 py-0.5 bg-blue-500/10 text-blue-600 dark:text-blue-300 text-[10px] font-bold rounded-md uppercase">
                                    {{ activeDraft.serviceData.vehicle.engine_type }}
                                </span>
                            </div>
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                {{ activeDraft.serviceData.vehicle.brand }} — {{ activeDraft.serviceData.vehicle.model }}
                            </p>
                        </div>
                        <button
                            @click="clearVehicle"
                            class="px-2 py-1 text-[10px] font-bold text-red-500 hover:text-red-600 bg-white dark:bg-gray-800 rounded-lg border border-red-200 dark:border-red-800 hover:bg-red-50 transition"
                        >
                            Ganti
                        </button>
                    </div>
                </div>

                <!-- Last Service Detailed Comparison -->
                <div v-if="activeDraft.serviceData.last_service" class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 space-y-3">
                    <p class="text-[10px] font-bold text-amber-700 dark:text-amber-300 uppercase flex items-center gap-1">
                        <span>⚡</span> Riwayat Servis Terakhir
                    </p>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-[9px] text-gray-400 uppercase font-bold mb-0.5">KM Servis Lalu</p>
                            <p class="text-xs font-black text-gray-800 dark:text-white">
                                {{ activeDraft.serviceData.last_service.current_km?.toLocaleString() || '-' }} KM
                            </p>
                        </div>
                        <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-[9px] text-gray-400 uppercase font-bold mb-0.5">Tgl Servis Lalu</p>
                            <p class="text-[11px] font-bold text-gray-800 dark:text-white mt-0.5">
                                {{ new Date(activeDraft.serviceData.last_service.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) }}
                            </p>
                        </div>
                        
                        <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-[9px] text-gray-400 uppercase font-bold mb-0.5">Oli Mesin</p>
                            <p class="text-[10px] font-bold text-gray-700 dark:text-gray-300 line-clamp-2 leading-tight">
                                {{ activeDraft.serviceData.last_service.engine_oil?.name || '-' }}
                            </p>
                        </div>
                        <div class="p-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <p class="text-[9px] text-gray-400 uppercase font-bold mb-0.5">Oli Gardan</p>
                            <p class="text-[10px] font-bold text-gray-700 dark:text-gray-300 line-clamp-2 leading-tight">
                                {{ activeDraft.serviceData.vehicle.engine_type === 'matic' ? (activeDraft.serviceData.last_service.gear_oil?.name || '-') : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <!-- Next Service Targets -->
                    <div class="grid grid-cols-2 gap-2 pt-2 border-t border-amber-200 dark:border-amber-700/50">
                        <div>
                            <p class="text-[9px] text-amber-600 dark:text-amber-400 font-bold uppercase">Target KM Baru</p>
                            <p class="text-[11px] font-black text-amber-800 dark:text-amber-300">
                                {{ activeDraft.serviceData.last_service.next_service_km?.toLocaleString() || '-' }} KM
                            </p>
                        </div>
                        <div>
                            <p class="text-[9px] text-amber-600 dark:text-amber-400 font-bold uppercase">Target Tanggal</p>
                            <p class="text-[11px] font-black text-amber-800 dark:text-amber-300">
                                {{ activeDraft.serviceData.last_service.next_service_date ? new Date(activeDraft.serviceData.last_service.next_service_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="activeDraft.serviceData.last_service.notes" class="p-2.5 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg border border-yellow-200 dark:border-yellow-700/50">
                        <p class="text-[9px] font-bold text-yellow-700 dark:text-yellow-400 uppercase mb-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Catatan Mekanik
                        </p>
                        <p class="text-[10px] text-gray-700 dark:text-gray-300 italic leading-snug">"{{ activeDraft.serviceData.last_service.notes }}"</p>
                    </div>

                    <!-- KM Comparison (live) -->
                    <div v-if="activeDraft.serviceData.current_km && activeDraft.serviceData.last_service.current_km" class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-800 mt-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-500">Selisih KM Saat Ini</span>
                            <span class="font-bold" :class="(activeDraft.serviceData.current_km - activeDraft.serviceData.last_service.current_km) >= 0 ? 'text-blue-600' : 'text-red-500'">
                                {{ (activeDraft.serviceData.current_km - activeDraft.serviceData.last_service.current_km).toLocaleString() }} KM
                            </span>
                        </div>
                        <div v-if="activeDraft.serviceData.vehicle.service_interval_km" class="flex items-center justify-between text-[10px] text-gray-400 mt-1">
                            <span>Interval rekomendasi motor</span>
                            <span>{{ activeDraft.serviceData.vehicle.service_interval_km?.toLocaleString() }} KM</span>
                        </div>
                    </div>
                </div>

                <!-- KM Input -->
                <div>
                    <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase block mb-1.5">Kilometer Saat Ini</label>
                    <input
                        type="number"
                        v-model="activeDraft.serviceData.current_km"
                        :placeholder="activeDraft.serviceData.last_service?.current_km ? `Terakhir: ${activeDraft.serviceData.last_service.current_km.toLocaleString()} KM` : 'Contoh: 15000'"
                        class="w-full p-3 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition"
                    />
                </div>

                <!-- Oil Selection -->
                <div class="grid grid-cols-1 gap-3">
                    <div>
                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase block mb-1.5">Oli Mesin</label>
                        <select
                            v-model="activeDraft.serviceData.engine_oil_id"
                            class="w-full p-3 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition"
                        >
                            <option :value="null">-- Pilih dari Keranjang --</option>
                            <option v-for="item in activeDraft.cart_items" :key="item.product_id" :value="item.product_id">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="activeDraft.serviceData.vehicle?.engine_type === 'matic'">
                        <label class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase block mb-1.5">Oli Gardan</label>
                        <select
                            v-model="activeDraft.serviceData.gear_oil_id"
                            class="w-full p-3 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-blue-500 transition"
                        >
                            <option :value="null">-- Pilih dari Keranjang --</option>
                            <option v-for="item in activeDraft.cart_items" :key="item.product_id" :value="item.product_id">
                                {{ item.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Footer CTA -->
        <div class="shrink-0 px-5 pb-5 pt-3 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-4px_12px_rgba(0,0,0,0.04)]">
            <button
                @click="proceedWithVehicle"
                v-if="activeDraft.serviceData.vehicle"
                class="w-full h-[52px] bg-blue-500 hover:bg-blue-600 text-white font-bold rounded-xl text-sm transition-all active:scale-[0.98] flex items-center justify-center gap-2 shadow-lg shadow-blue-500/20"
            >
                Lanjut ke Keranjang
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
    </div>
</template>
