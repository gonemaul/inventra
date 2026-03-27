<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import BottomSheet from '@/Components/BottomSheet.vue';

// Import new modular components
import CustomerDashboard from '@/Components/CustomerHub/CustomerDashboard.vue';
import VehicleFormBottomSheet from '@/Components/CustomerHub/VehicleFormBottomSheet.vue';
import CustomerDataTable from '@/Components/CustomerHub/CustomerDataTable.vue';
import VehicleHistoryBottomSheet from '@/Components/CustomerHub/VehicleHistoryBottomSheet.vue';
import VehicleDataTable from '@/Components/CustomerHub/VehicleDataTable.vue';

const props = defineProps({
    vehicles: [Array, Object],
    stats: Object,
    dss: Object,
});

// State
const activeTab = ref('vehicles');
const selectedVehicle = ref(null);
const serviceHistory = ref([]);
const isLoadingHistory = ref(false);
const showFormBottomSheet = ref(false);
const showHistoryBottomSheet = ref(false);

// Actions
const handleAddNew = () => {
    selectedVehicle.value = null; // null triggers register mode
    showFormBottomSheet.value = true;
};

const handleEdit = (vehicle) => {
    selectedVehicle.value = vehicle; // passing vehicle triggers edit mode
    showFormBottomSheet.value = true;
};

const handleSelect = async (vehicle) => {
    selectedVehicle.value = vehicle;
    showHistoryBottomSheet.value = true;
    fetchHistory(vehicle.id);
};

const handleFormSuccess = () => {
    // Optionally trigger a reload to update data
    // router.reload({ only: ['vehicles', 'stats'] });
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
    <Head title="Customer Hub - Inventra" />

    <AuthenticatedLayout headerTitle="Customer Hub">
        <div class="w-full min-h-screen space-y-6 pb-20">
            
            <!-- Dashboard Component -->
            <CustomerDashboard :stats="stats" :dss="dss" />

            <!-- Tabs Navigation -->
            <div class="flex items-center gap-2 mt-2 mb-6 border-b border-gray-200 dark:border-gray-800 pb-px font-sans">
                <button 
                    @click="activeTab = 'vehicles'"
                    :class="activeTab === 'vehicles' ? 'border-lime-500 text-lime-600 dark:text-lime-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-4 text-xs font-black uppercase tracking-widest border-b-2 transition-all relative flex items-center gap-2"
                >
                    <span class="text-lg">🏍️</span> Kendaraan
                </button>
                <button 
                    @click="activeTab = 'customers'"
                    :class="activeTab === 'customers' ? 'border-blue-500 text-blue-600 dark:text-blue-500' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-6 py-4 text-xs font-black uppercase tracking-widest border-b-2 transition-all relative flex items-center gap-2"
                >
                    <span class="text-lg">👥</span> Pelanggan
                    <span class="absolute top-2 right-1 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                    </span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Data Component -->
                <div class="lg:col-span-2 space-y-6">
                    <VehicleDataTable 
                        v-if="activeTab === 'vehicles'"
                        :vehicles="vehicles" 
                        @add="handleAddNew"
                        @edit="handleEdit"
                        @select="handleSelect"
                    />
                    <CustomerDataTable 
                        v-else-if="activeTab === 'customers'"
                    />
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

        <!-- Form Component -->
        <VehicleFormBottomSheet 
            :show="showFormBottomSheet" 
            :vehicle="selectedVehicle"
            @close="showFormBottomSheet = false"
            @success="handleFormSuccess"
        />

        <!-- HISTORY BOTTOM SHEET -->
        <VehicleHistoryBottomSheet
            :show="showHistoryBottomSheet"
            :vehicle="selectedVehicle"
            :serviceHistory="serviceHistory"
            :isLoading="isLoadingHistory"
            @close="showHistoryBottomSheet = false"
        />

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
