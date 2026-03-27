<script setup>
import { ref } from 'vue';

// Dummy Data
const customers = ref([
    { id: 1, name: 'Budi Santoso', phone: '081234567890', type: 'PRO', joined: '2025-01-15', vehicles: 2 },
    { id: 2, name: 'Siti Aminah', phone: '085678901234', type: 'REGULAR', joined: '2025-02-20', vehicles: 1 },
    { id: 3, name: 'Andi Wijaya', phone: '087890123456', type: 'PRO', joined: '2025-03-05', vehicles: 3 },
    { id: 4, name: 'Rina Melati', phone: '081112223344', type: 'REGULAR', joined: '2025-04-10', vehicles: 1 },
]);

const search = ref('');
</script>

<template>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-3xl p-6 border border-gray-100 dark:border-gray-700">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white uppercase tracking-tight">Manajemen Pelanggan</h3>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fitur dalam tahap pengembangan (Mockup)</p>
            </div>
            <button class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 bg-blue-500 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-600 shadow-lg shadow-blue-500/30 transition-all active:scale-95 disabled:opacity-50 cursor-not-allowed" disabled>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Pelanggan
            </button>
        </div>

        <div class="relative mb-6">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
            <input 
                v-model="search"
                type="text" 
                placeholder="Cari Nama atau No HP..."
                class="block w-full pl-12 pr-4 py-4 bg-gray-50 dark:bg-gray-900/50 border-none rounded-2xl text-gray-900 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-blue-500 font-bold transition-all"
            />
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="py-4 px-4 text-[10px] font-black tracking-widest text-gray-400 uppercase">Nama Pelanggan</th>
                        <th class="py-4 px-4 text-[10px] font-black tracking-widest text-gray-400 uppercase">Kontak</th>
                        <th class="py-4 px-4 text-[10px] font-black tracking-widest text-gray-400 uppercase">Status</th>
                        <th class="py-4 px-4 text-[10px] font-black tracking-widest text-gray-400 uppercase text-center">Kendaraan</th>
                        <th class="py-4 px-4 text-[10px] font-black tracking-widest text-gray-400 uppercase text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="customer in customers" 
                        :key="customer.id"
                        class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group"
                    >
                        <td class="py-4 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-sm shadow-inner">
                                    {{ customer.name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ customer.name }}</p>
                                    <p class="text-[10px] text-gray-500">Bergabung {{ customer.joined }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 font-mono">{{ customer.phone }}</p>
                        </td>
                        <td class="py-4 px-4">
                            <span 
                                :class="customer.type === 'PRO' ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 border border-gray-200 dark:border-gray-700'"
                                class="px-2.5 py-1 text-[9px] font-black tracking-widest uppercase rounded-md"
                            >
                                {{ customer.type }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-center">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 text-xs font-bold ring-2 ring-white dark:ring-gray-800">{{ customer.vehicles }}</span>
                        </td>
                        <td class="py-4 px-4 text-right">
                            <button class="text-[10px] font-bold text-blue-500 hover:text-blue-700 uppercase tracking-widest opacity-50 cursor-not-allowed" disabled>
                                Detail
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
