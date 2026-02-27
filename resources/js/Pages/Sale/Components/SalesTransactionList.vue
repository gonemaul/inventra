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

// Helper untuk icons payment method
const getPaymentMethodIcon = (method) => {
    switch (method) {
        case 'qris':
            return '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>';
        case 'e-wallet':
             return '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
        default: // cash
            return '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>';
    }
};

const hasServiceItem = (sale) => {
    console.log(sale)
    return sale.items?.some(item => ['Jasa', 'Layanan'].includes(item.product?.category?.name));
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
    <div class="space-y-4">
        <div
            v-for="sale in sales"
            :key="sale.id"
            class="group relative rounded-2xl p-4 lg:p-5 border transition-all cursor-pointer bg-white dark:bg-gray-900"
            :class="[
                sale.deleted_at 
                    ? 'border-red-100 ring-1 ring-red-50 dark:border-red-900/40 dark:ring-red-900/20' 
                    : 'border-gray-200 shadow-sm hover:shadow-md hover:border-gray-300 dark:border-gray-800 dark:hover:border-gray-700'
            ]"
        >
            <div class="flex justify-between flex-wrap gap-4" :class="{ 'opacity-60 grayscale': sale.deleted_at }">
                <!-- Kiri: Info Utama -->
                <div class="flex gap-4 items-start flex-1 min-w-[200px]">
                    <div
                        class="w-10 h-10 lg:w-11 lg:h-11 rounded-xl flex items-center justify-center shrink-0 border"
                        :class="sale.deleted_at ? 'bg-red-50 border-red-100 text-red-400 dark:bg-red-900/20 dark:border-red-800' : 'bg-gray-50 border-gray-100 text-gray-500 dark:bg-gray-800 dark:border-gray-700'"
                    >
                        <!-- Icon Premium Monochrome -->
                        <svg class="w-5 h-5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>

                <div class="flex flex-col gap-1.5 flex-1 overflow-hidden">
                        <div class="flex items-center gap-2 flex-wrap">
                             <h4
                                class="font-bold text-gray-800 dark:text-white text-sm lg:text-base group-hover:text-gray-900 transition-colors tracking-tight"
                            >
                                {{ sale.reference_no }}
                            </h4>
                            
                            <!-- Premium Monochrome Payment Badge -->
                            <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-md border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 text-[10px] sm:text-xs font-medium text-gray-600 dark:text-gray-300 capitalize shadow-sm">
                                <span v-html="getPaymentMethodIcon(sale.payment_method)"></span>
                                {{ sale.payment_method || 'Cash' }}
                            </div>
                            
                            <!-- Badges -->
                            <span v-if="sale.notes" class="text-[10px] border border-orange-200 bg-orange-50 text-orange-600 px-1.5 py-0.5 rounded font-medium dark:bg-orange-900/20 dark:border-orange-800">Note</span>
                            <span v-if="sale.deleted_at" class="text-[10px] border border-red-200 bg-red-50 text-red-600 px-1.5 py-0.5 rounded font-black tracking-widest dark:bg-red-900/20 dark:border-red-800">VOID</span>
                        </div>
                       
                        <!-- Date Info -->
                        <div class="text-xs text-gray-500 dark:text-gray-400 flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                            <span>
                                {{ formatDate(sale.transaction_date) }}
                            </span>
                            
                            <span class="hidden sm:inline text-gray-300 dark:text-gray-600">•</span>
                            
                            <!-- Item Stats Premium Text -->
                            <span class="flex items-center gap-3 font-medium">
                                <span>{{ sale.items_count || 0 }} Items</span>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <span>{{ parseFloat(sale.items_sum_quantity || 0) }} Qty</span>
                            </span>
                        </div>
                        
                         <!-- Jasa Indicator (Appears below date if includes service) -->
                        <div v-if="hasServiceItem(sale)" class="flex items-center gap-1.5 mt-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400">
                             <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                             Termasuk Jasa/Layanan
                        </div>

                        <!-- History Created/Updated Minimal -->
                        <div v-if="sale.updated_at && sale.created_at && sale.updated_at !== sale.created_at" class="text-[10px] text-gray-400 mt-0.5">
                            *Diedit: {{ formatDate(sale.updated_at, true) }}
                        </div>
                    </div>
                </div>

                <!-- Kanan: Nominal & Status -->
                <div class="text-right flex flex-col justify-between sm:items-end w-full sm:w-auto mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-gray-100 dark:border-gray-800">
                    <div
                        class="font-black tracking-tight text-gray-900 dark:text-white text-base lg:text-xl"
                    >
                        {{ formatRupiah(sale.total_revenue) }}
                    </div>
                    <!-- Action Buttons Minimalist -->
                    <div class="flex items-center sm:justify-end gap-1.5 mt-2.5">
                         <Link
                            :href="route('sales.show', sale.id)"
                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition dark:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:border-gray-600"
                            title="Lihat Detail Lengkap"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </Link>
                        
                        <!-- Edit Button (Disabled if Deleted) -->
                        <Link
                            v-if="!sale.deleted_at"
                            :href="route('sales.edit', sale.id)"
                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition dark:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:border-gray-600"
                            title="Edit Transaksi"
                        >
                             <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </Link>

                        <!-- Delete Button (Disabled if Deleted) -->
                        <button
                            v-if="!sale.deleted_at"
                            @click.stop="confirmDelete(sale)"
                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200 transition dark:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-red-900/20 dark:hover:text-red-400 dark:hover:border-red-800"
                            title="Hapus Transaksi"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>

                         <button 
                            v-if="!sale.deleted_at"
                            @click.stop="emit('preview-invoice', sale)"
                            class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition dark:bg-transparent dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:border-gray-600 flex items-center gap-1.5"
                            title="Preview Invoice"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                            <span class="text-[10px] font-bold sm:hidden">Cetak</span>
                        </button>
                    </div>
                </div>
            </div>
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
