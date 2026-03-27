<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BottomSheet from '@/Components/BottomSheet.vue';
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

const props = defineProps({
    show: Boolean,
    vehicle: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close', 'success']);
const toast = usePremiumAlert();

const form = useForm({
    id: null,
    plate_number: '',
    brand: '',
    model: '',
    description: '',
    color: '',
    engine_type: 'matic',
    engine_interval_km: 2000,
    engine_interval_days: 60,
    gear_interval_km: 6000,
    gear_interval_days: 180,
    security_code: '',
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.vehicle && props.vehicle.id) {
            form.id = props.vehicle.id;
            form.plate_number = props.vehicle.plate_number || '';
            form.brand = props.vehicle.brand || '';
            form.model = props.vehicle.model || '';
            form.description = props.vehicle.description || '';
            form.color = props.vehicle.color || '';
            form.engine_type = props.vehicle.engine_type || 'matic';
            form.engine_interval_km = props.vehicle.engine_interval_km ?? 2000;
            form.engine_interval_days = props.vehicle.engine_interval_days ?? 60;
            form.gear_interval_km = props.vehicle.gear_interval_km ?? 6000;
            form.gear_interval_days = props.vehicle.gear_interval_days ?? 180;
            form.security_code = props.vehicle.security_code || '';
        } else {
            form.reset();
            form.id = null;
        }
    }
});

const submit = () => {
    if (form.id) {
        form.put(route('api.vehicles.update', form.id), {
            onSuccess: () => {
                emit('success');
                emit('close');
                form.reset();
                toast.success("Data kendaraan berhasil diperbarui!");
            }
        });
    } else {
        form.post(route('api.vehicles.store'), {
            onSuccess: () => {
                emit('success');
                emit('close');
                form.reset();
                toast.success("Kendaraan berhasil terdaftar!");
            }
        });
    }
};
</script>

<template>
    <BottomSheet 
        :show="show" 
        :title="form.id ? 'Edit Data Kendaraan' : 'Daftar Kendaraan Baru'" 
        persistent 
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <!-- Tipe Mesin -->
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

                    <!-- Nomor Plat & Kode Kemanan -->
                    <div class="grid grid-cols-2 gap-4">
                        <div :class="form.id ? 'col-span-1' : 'col-span-2'">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Nomor Plat</label>
                            <input 
                                v-model="form.plate_number" 
                                type="text"
                                placeholder="B 1234 ABC"
                                class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 uppercase font-black text-gray-900 dark:text-white"
                                required
                            />
                        </div>
                        <div v-if="form.id">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Kode Keamanan</label>
                            <input v-model="form.security_code" type="text" placeholder="P001" class="w-full px-5 py-4 bg-blue-500/5 text-blue-600 border border-blue-200 dark:border-blue-900 rounded-2xl focus:ring-2 focus:ring-blue-500 font-black uppercase" />
                        </div>
                    </div>

                    <!-- Merk & Model -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Merk <span class="text-red-500">*</span></label>
                            <input v-model="form.brand" type="text" placeholder="Cth: Honda" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-gray-800 dark:text-white" required />
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Model <span class="text-red-500">*</span></label>
                            <input v-model="form.model" type="text" placeholder="Cth: Vario 150" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-gray-800 dark:text-white" required />
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Warna (Opsional)</label>
                        <input v-model="form.color" type="text" placeholder="Cth: Merah Hitam" class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-bold text-gray-800 dark:text-white" />
                    </div>

                    <!-- Interval Oli Mesin -->
                    <div class="p-4 bg-blue-50/50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/50">
                        <p class="text-[10px] font-black text-blue-800 dark:text-blue-400 uppercase tracking-widest mb-3">Target Oli Mesin</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval KM</label>
                                <input v-model="form.engine_interval_km" type="number" class="w-full px-5 py-3 bg-white dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-center text-gray-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval Hari</label>
                                <input v-model="form.engine_interval_days" type="number" class="w-full px-5 py-3 bg-white dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-center text-gray-800 dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <!-- Interval Oli Gardan -->
                    <div v-if="form.engine_type === 'matic'" class="p-4 bg-amber-50/50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-900/50 transition-all">
                        <p class="text-[10px] font-black text-amber-800 dark:text-amber-400 uppercase tracking-widest mb-3">Target Oli Gardan</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval KM</label>
                                <input v-model="form.gear_interval_km" type="number" class="w-full px-5 py-3 bg-white dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-amber-500 font-bold text-center text-gray-800 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Interval Hari</label>
                                <input v-model="form.gear_interval_days" type="number" class="w-full px-5 py-3 bg-white dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-amber-500 font-bold text-center text-gray-800 dark:text-white" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2 px-1">Keterangan Tambahan (Opsional)</label>
                        <textarea 
                            v-model="form.description" 
                            rows="2"
                            placeholder="Catatan servis..."
                            class="w-full px-5 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl focus:ring-2 focus:ring-lime-500 font-medium text-sm text-gray-800 dark:text-white"
                        ></textarea>
                    </div>
                </div>
            </div>
            
            <button 
                type="submit" 
                :disabled="form.processing"
                class="w-full py-5 bg-lime-500 text-white rounded-3xl font-black shadow-xl shadow-lime-500/30 transition-all uppercase tracking-widest text-sm hover:bg-lime-600 disabled:opacity-50"
            >
                {{ form.processing ? 'Memproses...' : (form.id ? 'Simpan Perubahan' : 'Daftarkan Kendaraan') }}
            </button>
        </form>
    </BottomSheet>
</template>
