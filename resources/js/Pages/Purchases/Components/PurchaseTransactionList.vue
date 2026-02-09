<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    purchases: Array
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (dateStr) => {
    if(!dateStr) return '-';
    // Format: 09 Feb 2026
    const date = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(date);
};

const getStatusColor = (status) => {
   switch(status) {
       case 'draft': return 'bg-gray-100 text-gray-600 border-gray-200';
       case 'dipesan': return 'bg-blue-50 text-blue-600 border-blue-200';
       case 'dikirim': return 'bg-yellow-50 text-yellow-600 border-yellow-200';
       case 'diterima': return 'bg-purple-50 text-purple-600 border-purple-200';
       case 'checking': return 'bg-teal-50 text-teal-600 border-teal-200';
       case 'selesai': return 'bg-green-50 text-green-600 border-green-200';
       case 'dibatalkan': return 'bg-red-50 text-red-600 border-red-200';
       default: return 'bg-gray-100 text-gray-600';
   }
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div 
            v-for="purchase in purchases" 
            :key="purchase.id"
            class="group bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
             :class="{'opacity-75 grayscale': purchase.status === 'dibatalkan'}"
        >
            <!-- Header -->
            <div class="flex justify-between items-start mb-3">
                 <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm truncate max-w-[120px]" :title="purchase.reference_no">
                            {{ purchase.reference_no }}
                        </h4>
                        <p class="text-[10px] text-gray-500">{{ formatDate(purchase.transaction_date) }}</p>
                    </div>
                </div>
                <span 
                    class="px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wide"
                    :class="getStatusColor(purchase.status)"
                >
                    {{ purchase.status }}
                </span>
            </div>

            <!-- Content -->
            <div class="mb-4">
                 <div class="flex justify-between items-end mb-1">
                    <p class="text-xs text-gray-500">Supplier</p>
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 text-right truncate max-w-[150px]">
                        {{ purchase.supplier?.name || 'Umum/Tunai' }}
                    </p>
                </div>
                <div class="flex justify-between items-end border-t border-dashed pt-2 mt-2">
                    <p class="text-xs text-gray-500">Total</p>
                    <p class="text-lg font-black text-gray-800 dark:text-white">
                        {{ formatCurrency(purchase.grand_total) }}
                    </p>
                </div>
            </div>

            <!-- Footer / Actions -->
            <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700 flex gap-2">
                <!-- Check if Validasi needed -->
                <Link 
                    v-if="purchase.status == 'checking' || purchase.status == 'diterima'"
                    :href="route('purchases.checking', purchase.id)"
                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold py-2 rounded-lg text-center transition shadow-sm hover:shadow"
                >
                    Validasi Barang
                </Link>
                <Link 
                    v-else
                    :href="route('purchases.show', purchase.id)"
                     class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 text-xs font-bold py-2 rounded-lg text-center transition"
                >
                    Detail
                </Link>
            </div>
        </div>
    </div>
</template>
