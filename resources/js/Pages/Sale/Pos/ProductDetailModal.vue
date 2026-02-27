<script setup>
import { computed } from "vue";

const props = defineProps({
    show: Boolean,
    product: Object,
});

const emit = defineEmits(["close", "addToCart"]);
const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);

// Helper untuk menampilkan data yang aman (handle null)
const safe = (val, def = "-") => val || def;
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
        @click.self="$emit('close')"
    >
        <div
            class="w-full h-full sm:h-auto sm:max-w-2xl overflow-hidden bg-white shadow-2xl dark:bg-gray-800 sm:rounded-2xl animate-slide-up sm:animate-scale-up flex flex-col"
        >
            <!-- Header Gambar & Content Wrapper -->
            <div class="flex flex-col sm:flex-row h-full overflow-hidden">
                <!-- Image Section -->
                <div class="relative w-full sm:w-1/2 aspect-square sm:aspect-auto bg-gray-100 dark:bg-gray-700 shrink-0">
                    <img
                        v-if="product?.image_url"
                        :src="product.image_url"
                        class="object-cover w-full h-full"
                        alt="Product Image"
                    />
                    <div
                        v-else
                        class="flex items-center justify-center w-full h-full text-gray-400 bg-gradient-to-br from-lime-50 to-lime-100 dark:from-gray-700 dark:to-gray-800 p-4"
                    >
                        <span class="text-xl md:text-2xl font-bold text-center text-gray-500 dark:text-gray-400 line-clamp-3 break-words select-none">{{ product?.name }}</span>
                    </div>
                    
                    <button
                        @click="$emit('close')"
                        class="absolute top-4 right-4 sm:left-4 sm:right-auto p-2 bg-white/80 dark:bg-black/50 hover:bg-white dark:hover:bg-black text-gray-800 dark:text-white rounded-full shadow-lg transition z-10"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    
                    <div class="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent sm:hidden">
                        <h2 class="text-2xl font-bold text-white leading-tight">{{ product?.name }}</h2>
                        <p class="text-gray-300 font-mono text-sm mt-1">{{ product?.code }}</p>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="flex flex-col flex-1 h-full overflow-hidden bg-white dark:bg-gray-800">
                     <!-- Desktop Title -->
                    <div class="hidden sm:block p-6 pb-0">
                        <h2 class="text-2xl font-bold text-gray-800 dark:text-white leading-tight">{{ product?.name }}</h2>
                        <p class="text-gray-500 font-mono text-sm mt-1">{{ product?.code }}</p>
                    </div>

                    <!-- Scrollable Details -->
                    <div class="p-6 space-y-6 flex-1 overflow-y-auto custom-scroll">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-1">Harga Jual</p>
                                <p class="text-lg font-black text-lime-600 dark:text-lime-400">{{ rp(product?.selling_price) }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-bold tracking-wider mb-1">Stok Tersedia</p>
                                <p
                                    class="text-lg font-black"
                                    :class="product?.stock > 0 ? 'text-gray-800 dark:text-white' : 'text-red-500'"
                                >
                                    {{ parseFloat(product?.stock) }} {{ product?.unit?.name || 'Unit' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h3 class="font-bold text-gray-800 dark:text-white border-b pb-2 dark:border-gray-700">Informasi Detail</h3>
                            
                            <div class="grid grid-cols-[100px_1fr] gap-2 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Kategori</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ product?.category?.name || '-' }}</span>
                                
                                <span class="text-gray-500 dark:text-gray-400">Tipe</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ product?.product_type?.name || '-' }}</span>

                                <span class="text-gray-500 dark:text-gray-400">Brand</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ product?.brand?.name || '-' }}</span>
                                
                                <span class="text-gray-500 dark:text-gray-400">Ukuran</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ product?.size?.name || '-' }}</span>
                            </div>
                        </div>
                        
                        <div v-if="product?.description" class="space-y-2">
                            <h3 class="font-bold text-gray-800 dark:text-white border-b pb-2 dark:border-gray-700">Deskripsi</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                                {{ product.description }}
                            </p>
                        </div>

                    </div>

                    <!-- Footer Action -->
                    <div class="p-4 border-t dark:border-gray-700 flex justify-end gap-3 bg-gray-50 dark:bg-gray-900/50 mt-auto">
                        <button
                            v-if="product?.stock > 0"
                            @click="$emit('addToCart', product); $emit('close')"
                            class="w-full py-3 px-4 bg-gray-900 hover:bg-gray-800 dark:bg-lime-600 dark:hover:bg-lime-500 text-white font-bold rounded-xl shadow-lg transition active:scale-95 flex items-center justify-center gap-2"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Tambah ke Keranjang
                        </button>
                        <button
                            v-else
                            disabled
                            class="w-full py-3 px-4 bg-gray-200 dark:bg-gray-700 text-gray-400 font-bold rounded-xl cursor-not-allowed"
                        >
                            Stok Habis
                        </button>
                         <button @click="$emit('close')" class="py-3 px-4 bg-red-500 dark:bg-red-700 text-white font-bold rounded-xl cursor-not-allowed hidden sm:block">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-scale-up {
    animation: scaleUp 0.3s ease-out;
}
@keyframes scaleUp {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
