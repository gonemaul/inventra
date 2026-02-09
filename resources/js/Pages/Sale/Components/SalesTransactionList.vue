<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    sales: Array,
});

const emit = defineEmits(["preview-invoice"]);

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const formatDate = (dateString, withTime = false) => {
    const options = {
        day: "numeric",
        month: "short",
        year: "numeric",
    };
    if (withTime) {
        options.hour = "2-digit";
        options.minute = "2-digit";
    }
    return new Date(dateString).toLocaleDateString("id-ID", options);
};

// Helper untuk status warna badge (opsional jika ada status pembayaran)
const getStatusColor = (sale) => {
    // Logic sementara, bisa dikembangkan
    return "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400";
};

import { router } from "@inertiajs/vue3";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import { ref } from "vue";

const deleteConfirm = ref(null);

const confirmDelete = (sale) => {
    deleteConfirm.value.open({
        title: "Hapus Transaksi?",
        message: "Stok produk akan dikembalikan. Anda yakin ingin menghapus transaksi ",
        itemName: sale.reference_no,
        url: route('sales.destroy', sale.id),
    });
};
</script>

<template>
    <DeleteConfirm ref="deleteConfirm" @success="$emit('refresh')" />
    <div class="space-y-3">
        <div
            v-for="sale in sales"
            :key="sale.id"
            class="group relative rounded-2xl p-4 border shadow-sm hover:shadow-md transition-all cursor-pointer"
            :class="[
                sale.deleted_at 
                    ? 'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-900/30' 
                    : 'bg-white dark:bg-gray-800 border-gray-100 dark:border-gray-700'
            ]"
        >
            <div class="flex justify-between items-start gap-3" :class="{ 'opacity-60 grayscale': sale.deleted_at }">
                <!-- Kiri: Icon & Info Utama -->
                <div class="flex gap-3.5 items-center">
                    <div
                        class="w-10 h-10 lg:w-12 lg:h-12 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0"
                    >
                        <!-- Icon Keranjang / Receipt -->
                        <span class="text-lg lg:text-xl">🧾</span>
                    </div>

                <div>
                        <div class="flex items-center gap-2">
                             <h4
                                class="font-bold text-gray-900 dark:text-white text-sm lg:text-base group-hover:text-blue-600 transition-colors"
                            >
                                {{ sale.reference_no }}
                            </h4>
                            <span v-if="sale.notes" class="text-[10px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded">Note</span>
                            <span v-if="sale.deleted_at" class="text-[10px] bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-bold">VOID</span>
                        </div>
                       
                        <!-- Date Info -->
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex flex-col gap-0.5">
                            <span class="flex items-center gap-1">
                                📅 {{ formatDate(sale.transaction_date) }}
                            </span>
                            
                            <!-- History Created/Updated -->
                            <div v-if="sale.updated_at && sale.created_at && sale.updated_at !== sale.created_at" class="text-[10px] text-gray-400 italic">
                                (Diedit: {{ formatDate(sale.updated_at, true) }})
                            </div>
                        </div>

                        <!-- Item Stats -->
                        <div class="mt-1.5 flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                            <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-md">
                                📦 {{ sale.items_count || 0 }} Item
                            </span>
                            <span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 px-2 py-0.5 rounded-md">
                                🔢 {{ parseFloat(sale.items_sum_quantity || 0) }} Qty
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Kanan: Nominal & Status -->
                <div class="text-right">
                    <div
                        class="font-black text-gray-900 dark:text-white text-sm lg:text-lg"
                    >
                        {{ formatRupiah(sale.total_revenue) }}
                    </div>
                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-2 mt-2">
                         <Link
                            :href="route('sales.show', sale.id)"
                            class="p-1.5 rounded-full bg-gray-100 text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-blue-900/30 dark:hover:text-blue-400"
                            title="Lihat Detail Lengkap"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        </Link>
                        
                        <!-- Edit Button (Disabled if Deleted) -->
                        <Link
                            v-if="!sale.deleted_at"
                            :href="route('sales.edit', sale.id)"
                            class="p-1.5 rounded-full bg-gray-100 text-gray-500 hover:bg-yellow-100 hover:text-yellow-600 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-yellow-900/30 dark:hover:text-yellow-400"
                            title="Edit Transaksi"
                        >
                             <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </Link>

                        <!-- Delete Button (Disabled if Deleted) -->
                        <button
                            v-if="!sale.deleted_at"
                            @click.stop="confirmDelete(sale)"
                            class="p-1.5 rounded-full bg-gray-100 text-gray-500 hover:bg-red-100 hover:text-red-600 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                            title="Hapus Transaksi"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>

                         <button 
                            v-if="!sale.deleted_at"
                            @click.stop="emit('preview-invoice', sale)"
                            class="p-1.5 rounded-full bg-gray-100 text-gray-500 hover:bg-orange-100 hover:text-orange-600 transition dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-orange-900/30 dark:hover:text-orange-400"
                            title="Preview Invoice"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bottom Action (Hidden by default, shown on hover or mobile always accessible via tap) -->
            <!-- <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center text-xs">
                <span class="text-gray-400">{{ sale.user?.name || 'Kasir' }}</span>
                <span class="text-blue-600 font-bold">Lihat Detail &rarr;</span>
            </div> -->
        </div>

        <!-- Empty State -->
        <div
            v-if="sales.length === 0"
            class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-dashed border-2 border-gray-200 dark:border-gray-700"
        >
            <span class="text-4xl block mb-2">📭</span>
            <p class="text-gray-500 font-medium">Belum ada transaksi pada periode ini.</p>
        </div>
    </div>
</template>
