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
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="sale in sales"
            :key="sale.id"
            class="group relative bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-all cursor-pointer"
        >
            <div class="flex justify-between items-start gap-3">
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
                        </div>
                       
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ formatDate(sale.transaction_date, true) }}
                        </p>
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
                         <button 
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
