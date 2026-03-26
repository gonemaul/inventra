<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import debounce from 'lodash/debounce';
import BottomSheet from '@/Components/BottomSheet.vue';
import InputRupiah from "@/Components/InputRupiah.vue";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

const props = defineProps({
    vehicles: Array,
    stats: Object, // Expecting stats from controller
});

// State
const search = ref('');
const filteredVehicles = ref(props.vehicles);
const selectedVehicle = ref(null);
const serviceHistory = ref([]);
const isLoadingHistory = ref(false);
const showAddBottomSheet = ref(false);
const showEditBottomSheet = ref(false);
const showHistoryBottomSheet = ref(false);
const toast = usePremiumAlert();

// Registration/Edit Form
const form = useForm({
    id: null,
    plate_number: '',
    brand: '',
    model: '',
    description: '',
    engine_type: 'matic',
    service_interval_km: 2000,
    service_interval_days: 60,
    security_code: '',
});

// Search Logic
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

// Actions
const selectVehicle = async (vehicle) => {
    selectedVehicle.value = vehicle;
    showHistoryBottomSheet.value = true;
    fetchHistory(vehicle.id);
};

const openEdit = (vehicle) => {
    selectedVehicle.value = vehicle;
    form.id = vehicle.id;
    form.plate_number = vehicle.plate_number;
    form.brand = vehicle.brand;
    form.model = vehicle.model;
    form.description = vehicle.description;
    form.engine_type = vehicle.engine_type;
    form.service_interval_km = vehicle.service_interval_km;
    form.service_interval_days = vehicle.service_interval_days;
    form.security_code = vehicle.security_code;
    showEditBottomSheet.value = true;
};

const fetchHistory = async (vehicleId) => {
    isLoadingHistory.value = true;
    try {
        const response = await axios.get(route('api.vehicles.history', vehicleId));
        serviceHistory.value = response.data;
    } catch (error) {
        console.error("Failed to fetch history", error);
    } finally {
        isLoadingHistory.value = false;
    }
};

const submitRegistration = () => {
    form.post(route('api.vehicles.store'), {
        onSuccess: () => {
            showAddBottomSheet.value = false;
            form.reset();
            toast.success("Kendaraan berhasil terdaftar!");
        }
    });
};

const submitUpdate = () => {
    form.put(route('api.vehicles.update', form.id), {
        onSuccess: () => {
            showEditBottomSheet.value = false;
            form.reset();
            toast.success("Data kendaraan berhasil diperbarui!");
        }
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

</script>

<template>
    <Head title="Customer Hub - Inventra" />

    <AuthenticatedLayout headerTitle="Customer Hub">
            <div class="w-full min-h-screen space-y-6 pb-20">
                
                <!-- SUMMARY DASHBOARD -->
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

                    <div class="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-3xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col justify-between relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition-transform">
                            <svg class="w-12 h-12 text-purple-500" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14h-2V9h-2V7h4v10z"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Member Aktif</span>
                        <span class="text-3xl font-black text-gray-900 dark:text-white">PRO</span>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Panel: Vehicle Management -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-700">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                                <div>
                                    <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Eksplorasi Kendaraan</h3>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Temukan riwayat servis dengan cepat.</p>
                                </div>
                                <button 
                                    @click="showAddBottomSheet = true"
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
                                    class="block w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl text-gray-900 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-lime-500 font-bold transition-all"
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
                                        <button @click.stop="openEdit(vehicle)" class="p-2 bg-white dark:bg-gray-700 text-gray-400 hover:text-blue-500 rounded-lg shadow-sm border border-gray-100 dark:border-gray-600 transition opacity-0 group-hover:opacity-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <button @click.stop="selectVehicle(vehicle)" class="p-2 bg-lime-500 text-white rounded-lg shadow-lg shadow-lime-500/20 transition opacity-0 group-hover:opacity-100">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </button>
                                    </div>

                                    <div @click="selectVehicle(vehicle)" class="flex items-center gap-4">
                                        <div class="w-14 h-14 bg-white dark:bg-gray-800 rounded-2xl shadow-sm flex items-center justify-center shrink-0 border border-gray-50 dark:border-gray-700">
                                            <span class="text-3xl" v-if="vehicle.engine_type === 'matic'">🛵</span>
                                            <span class="text-3xl" v-else>🏍️</span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <h4 class="text-lg font-black text-gray-900 dark:text-white tracking-widest uppercase leading-tight truncate">{{ vehicle.plate_number }}</h4>
                                            <p class="text-xs font-bold text-gray-500 dark:text-gray-400 truncate">{{ vehicle.brand }} {{ vehicle.model }}</p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <span class="px-2 py-0.5 text-[9px] bg-lime-500/10 text-lime-600 rounded-md uppercase font-black tracking-tighter">{{ vehicle.engine_type }}</span>
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
                    </div>

                    <!-- Right Panel: Marketing / Stats Area -->
                    <div class="space-y-6">
                        <div class="bg-gray-900 overflow-hidden shadow-2xl rounded-3xl p-8 text-white relative h-full min-h-[400px] border border-gray-800 flex flex-col justify-between">
                            <div class="relative z-10">
                                <div class="mb-6 inline-block px-4 py-1 bg-lime-500 text-black rounded-full text-[10px] font-black uppercase tracking-widest shadow-lg shadow-lime-500/20">
                                    Premium Experience
                                </div>
                                <h3 class="text-4xl font-black mb-4 leading-[1.1]">Loyalty<br><span class="text-lime-500">Program</span></h3>
                                <p class="text-gray-400 text-sm font-medium mb-8 leading-relaxed">Berikan pengalaman terbaik untuk pelanggan setia Anda. Fitur member akan segera hadir untuk meningkatkan retensi.</p>
                                
                                <div class="space-y-4">
                                    <div class="flex items-center gap-4 p-4 bg-gray-800/50 rounded-2xl border border-gray-700/50 backdrop-blur-md">
                                        <div class="w-12 h-12 bg-lime-500/20 rounded-xl flex items-center justify-center text-lime-500 font-bold shadow-inner">🎟️</div>
                                        <div>
                                            <p class="text-xs font-black uppercase text-white">Kupon Cashback</p>
                                            <p class="text-[10px] text-gray-400 font-medium">Potongan otomatis pada servis kelima.</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 p-4 bg-gray-800/50 rounded-2xl border border-gray-700/50 backdrop-blur-md">
                                        <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center text-blue-500 font-bold shadow-inner">📊</div>
                                        <div>
                                            <p class="text-xs font-black uppercase text-white">Laporan Digital</p>
                                            <p class="text-[10px] text-gray-400 font-medium">Kirim riwayat servis via WA otomatis.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-12 p-6 bg-lime-500/10 rounded-3xl border border-lime-500/20 text-center backdrop-blur-xl">
                                <span class="block text-sm font-black text-lime-500 uppercase tracking-widest mb-1">Coming Next</span>
                                <span class="text-[10px] font-bold text-gray-500">Direncanakan Q2 2026.</span>
                            </div>

                            <!-- Decorative Elements -->
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-lime-500/10 blur-[80px] rounded-full"></div>
                        </div>
                    </div>

                </div>
            </div>

        <!-- REGISTRATION BOTTOM SHEET -->
        <BottomSheet 
            :show="showAddBottomSheet" 
            title="Daftar Kendaraan Baru" 
            persistent 
            @close="showAddBottomSheet = false"
        >
            <form @submit.prevent="submitRegistration" class="space-y-6">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest mb-2">Pilih Tipe Mesin</label>
                            <div class="flex gap-4">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.engine_type" value="matic" class="hidden peer">
                                    <div class="p-4 text-center border-2 border-gray-100 dark:border-gray-800 rounded-2xl peer-checked:border-lime-500 peer-checked:bg-lime-500/5 text-xs font-black text-gray-400 dark:text-gray-600 peer-checked:text-lime-500 transition-all uppercase">
                                        <span class="text-2xl block mb-1">🛵</span> Matic
                                    </div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="form.engine_type" value="manual" class="hidden peer">
                                    <div class="p-4 text-center border-2 border-gray-100 dark:border-gray-800 rounded-2xl peer-checked:border-lime-500 peer-checked:bg-lime-500/5 text-xs font-black text-gray-400 dark:text-gray-600 peer-checked:text-lime-500 transition-all uppercase">
                                        <span class="text-2xl block mb-1">🏍️</span> Manual
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Nomor Plat</label>
                            <input 
                                v-model="form.plate_number" 
                                type="text"
                                placeholder="B 1234 ABC"
                                class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 uppercase font-black text-gray-900 dark:text-white"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Merk</label>
                                <input v-model="form.brand" type="text" placeholder="Honda" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Model</label>
                                <input v-model="form.model" type="text" placeholder="Vario 150" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold" required />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval KM</label>
                                <input v-model="form.service_interval_km" type="number" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-center" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval Hari</label>
                                <input v-model="form.service_interval_days" type="number" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-center" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Keterangan</label>
                            <textarea 
                                v-model="form.description" 
                                rows="2"
                                placeholder="Warna kendaraan atau modifikasi..."
                                class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-medium text-sm"
                            ></textarea>
                        </div>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    :disabled="form.processing"
                    class="w-full py-5 bg-lime-500 text-white rounded-3xl font-black shadow-xl shadow-lime-500/30 transition-all uppercase tracking-widest text-sm hover:bg-lime-600 disabled:opacity-50"
                >
                    {{ form.processing ? 'Sedang Memproses...' : 'Daftarkan Sekarang' }}
                </button>
            </form>
        </BottomSheet>

        <!-- EDIT VEHICLE BOTTOM SHEET -->
        <BottomSheet 
            :show="showEditBottomSheet" 
            title="Edit Data Kendaraan" 
            persistent 
            @close="showEditBottomSheet = false"
        >
            <form @submit.prevent="submitUpdate" class="space-y-6">
                 <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Nomor Plat</label>
                                <input v-model="form.plate_number" type="text" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 uppercase font-black" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Kode Keamanan</label>
                                <input v-model="form.security_code" type="text" placeholder="P001" class="w-full px-5 py-4 bg-blue-500/5 text-blue-600 border border-blue-200 dark:border-blue-900 rounded-2xl focus:ring-2 focus:ring-blue-500 font-black uppercase" />
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Merk</label>
                                <input v-model="form.brand" type="text" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold" required />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Model</label>
                                <input v-model="form.model" type="text" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold" required />
                            </div>
                        </div>

                         <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval KM</label>
                                <input v-model="form.service_interval_km" type="number" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-center" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval Hari</label>
                                <input v-model="form.service_interval_days" type="number" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-center" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Keterangan</label>
                            <textarea v-model="form.description" rows="2" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-medium text-sm"></textarea>
                        </div>
                    </div>
                </div>
                
                <button type="submit" :disabled="form.processing" class="w-full py-5 bg-blue-600 text-white rounded-3xl font-black shadow-xl shadow-blue-500/30 transition-all uppercase tracking-widest text-sm disabled:opacity-50">
                    {{ form.processing ? 'Menyimpan...' : 'Perbarui Perubahan' }}
                </button>
            </form>
        </BottomSheet>

        <!-- HISTORY BOTTOM SHEET -->
        <BottomSheet 
            :show="showHistoryBottomSheet" 
            :title="'Riwayat ' + (selectedVehicle?.plate_number || 'Servis')" 
            @close="showHistoryBottomSheet = false"
        >
            <div class="space-y-6 pb-4">
                <div v-if="isLoadingHistory" class="py-20 flex flex-col items-center justify-center opacity-50">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-lime-500 border-t-transparent mb-4"></div>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Memuat database...</p>
                </div>
                
                <div v-else-if="serviceHistory.length > 0" class="space-y-4">
                    <div 
                        v-for="(log, idx) in serviceHistory" 
                        :key="log.id"
                        class="p-5 bg-gray-50 dark:bg-gray-900/50 rounded-3xl border border-gray-100 dark:border-gray-800 group"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest block">{{ formatDate(log.created_at) }}</span>
                                <h5 class="text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight">Servis Rutin #{{ serviceHistory.length - idx }}</h5>
                            </div>
                            <span class="px-2 py-1 bg-lime-500/10 text-lime-600 text-[9px] font-black rounded-lg uppercase">Success</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-6 bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-inner-sm border border-gray-50 dark:border-gray-700">
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-1">Kilometer</p>
                                <p class="text-sm font-black text-gray-900 dark:text-white">{{ log.current_km ? log.current_km.toLocaleString() : '---' }} <span class="text-[9px] text-gray-400">KM</span></p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-gray-400 uppercase tracking-tighter mb-1">Rekomendasi</p>
                                <p class="text-sm font-black text-lime-600">{{ log.next_service_km ? log.next_service_km.toLocaleString() : '---' }} <span class="text-[9px] text-lime-400">KM</span></p>
                            </div>
                        </div>
                        
                        <div class="mt-4 space-y-3 px-1">
                            <div class="flex items-center gap-3 justify-between">
                                <p class="text-[11px] font-bold text-gray-600 dark:text-gray-400 leading-tight">Ganti Oli Mesin<br><span class="text-gray-900 dark:text-white font-black uppercase text-[10px]">{{ log.engine_oil.name }}</span></p>
                                <p v-if="log.gear_oil_id" class="text-[11px] font-bold text-gray-600 dark:text-gray-400 leading-tight">Ganti Oli Gardan<br><span class="text-gray-900 dark:text-white font-black uppercase text-[10px]">{{ log.gear_oil.name }}</span></p>
                            </div>
                            <div v-if="log.notes" class="mt-4 p-3 bg-gray-100 dark:bg-gray-800 rounded-2xl text-[10px] text-gray-500 dark:text-gray-400 italic border-l-4 border-gray-300 dark:border-gray-600">
                                "{{ log.notes }}"
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-20 text-center">
                    <div class="text-6xl mb-6 grayscale opacity-30">📂</div>
                    <p class="text-sm font-black text-gray-500 dark:text-gray-400 uppercase tracking-widest">Database Kosong</p>
                    <p class="text-[10px] text-gray-400 font-bold mt-2">Kendaraan ini belum pernah melakukan servis.</p>
                </div>
            </div>
        </BottomSheet>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
    width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}
.dark .custom-scroll::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
