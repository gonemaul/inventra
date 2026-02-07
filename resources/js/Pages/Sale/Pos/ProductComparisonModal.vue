<script setup>
import { watch } from "vue";

const props = defineProps({
    show: Boolean,
    products: { type: Array, default: () => [] },
});

const emit = defineEmits(["close", "remove", "addToCart"]);

watch(() => props.products.length, (newLength) => {
    if (newLength === 0 && props.show) {
        emit('close');
    }
});

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);

// Helper untuk styling row ganjil/genap agar mudah dibaca
const rowClass = "border-b border-gray-100 dark:border-gray-700 py-3 px-2";
const labelClass = "text-xs text-gray-400 dark:text-gray-500 font-medium uppercase tracking-wider w-24 shrink-0";
const valClass = "text-sm font-semibold text-gray-800 dark:text-gray-200 flex-1 break-words";

import { computed } from "vue";
const minPrice = computed(() => {
    if (!props.products.length) return 0;
    return Math.min(...props.products.map(p => p.selling_price || p.price || 0));
});

const maxSold = computed(() => {
    if (!props.products.length) return 0;
    return Math.max(...props.products.map(p => p.total_sold || 0));
});

</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center sm:p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
        @click.self="$emit('close')"
    >
        <div
            class="w-full h-full sm:h-auto sm:max-h-[90vh] sm:max-w-4xl flex flex-col bg-white dark:bg-gray-900 sm:rounded-2xl shadow-2xl animate-slide-up sm:animate-scale-up overflow-hidden"
        >
            <!-- Header -->
            <div class="px-4 py-3 border-b dark:border-gray-800 flex items-center justify-between bg-white dark:bg-gray-900 z-10">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    Perbandingan Produk
                </h2>
                <button
                    @click="$emit('close')"
                    class="p-2 text-gray-400 hover:text-red-500 transition"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Content Container -->
            <div class="flex-1 overflow-x-auto overflow-y-auto custom-scroll bg-gray-50 dark:bg-gray-900/50">
                <div class="flex min-w-full divide-x dark:divide-gray-800">
                    
                    <!-- Product Columns -->
                    <div 
                        v-for="product in products" 
                        :key="product.id"
                        class="flex-1 min-w-[280px] max-w-[350px] flex flex-col relative"
                    >
                        <!-- Badges -->
                        <div class="absolute top-2 left-2 z-20 flex flex-col gap-1">
                            <span v-if="product.selling_price <= minPrice && products.length > 1" class="bg-lime-500 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">
                                💰 Paling Murah
                            </span>
                            <span v-if="product.total_sold >= maxSold && maxSold > 0 && products.length > 1" class="bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-md shadow-sm">
                                🔥 Paling Laris
                            </span>
                        </div>

                        <!-- Image & Action Header -->
                        <div class="relative aspect-square bg-white dark:bg-gray-800 border-b dark:border-gray-700 shadow-sm">
                             <img
                                v-if="product.image_url"
                                :src="product.image_url"
                                class="object-contain w-full h-full p-4 hover:scale-105 transition duration-500"
                                alt=""
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>

                            <button
                                @click="$emit('remove', product)"
                                class="absolute top-2 right-2 p-1.5 bg-red-100/80 text-red-500 rounded-full hover:bg-red-500 hover:text-white transition"
                                title="Hapus dari perbandingan"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Details Body -->
                        <div class="p-4 bg-white dark:bg-gray-800 flex-1 space-y-4">
                            
                            <!-- Nama & Harga -->
                            <div>
                                <h3 class="font-bold text-gray-800 dark:text-gray-100 text-lg leading-tight mb-2 min-h-[3rem]">
                                    {{ product.name }}
                                </h3>
                                <div class="text-xl font-black text-lime-600 dark:text-lime-400">
                                    {{ rp(product.selling_price) }}
                                </div>
                            </div>

                            <!-- Attributes Table -->
                            <div class="space-y-0 text-sm">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400">Kode</span>
                                    <span class="font-mono">{{ product.code }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400">Stok</span>
                                    <span :class="product.stock > 0 ? 'text-gray-800 dark:text-white font-bold' : 'text-red-500 font-bold'">
                                        {{ parseFloat(product.stock) }} {{ product.unit?.name }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400">Kategori</span>
                                    <span class="text-right">{{ product.category?.name || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400">Brand</span>
                                    <span class="text-right">{{ product.brand?.name || '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700">
                                   <span class="text-gray-400">Ukuran</span>
                                   <span class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-xs">{{ product.size?.name || '-' }}</span>
                                </div>
                                 <div class="flex flex-col py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400 mb-1">Tipe</span>
                                    <span class="text-sm font-medium">{{ product.product_type?.name || '-' }}</span>
                                 </div>
                                 <div class="flex flex-col py-2 border-b border-gray-100 dark:border-gray-700">
                                    <span class="text-gray-400 mb-1">Deskripsi</span>
                                    <span class="text-xs text-gray-600 dark:text-gray-300 line-clamp-3 leading-relaxed">{{ product.description || '-' }}</span>
                                 </div>
                            </div>
                            
                            <!-- Add to Cart -->
                             <button
                                v-if="product.stock > 0"
                                @click="$emit('addToCart', product)"
                                class="w-full py-2.5 mt-auto border-2 border-gray-900 dark:border-gray-600 hover:bg-gray-900 hover:text-white dark:text-gray-200 dark:hover:bg-white dark:hover:text-gray-900 font-bold rounded-xl transition text-sm uppercase tracking-wide"
                            >
                                + Keranjang
                            </button>
                             <button
                                v-else
                                disabled
                                class="w-full py-2.5 mt-auto bg-gray-100 text-gray-400 font-bold rounded-xl cursor-not-allowed text-sm uppercase tracking-wide border border-transparent"
                            >
                                Stok Habis
                            </button>
                        </div>
                    </div>

                    <!-- Placeholder if list is small (optional visuals) -->
                </div>
            </div>
            
             <div class="p-3 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-800 text-center">
                <p class="text-xs text-gray-400">Geser ke samping untuk melihat produk lainnya</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
.animate-slide-up {
    animation: slideUp 0.3s ease-out;
}
@keyframes slideUp {
    from { transform: translateY(100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.animate-scale-up {
    animation: scaleUp 0.3s ease-out;
}
@keyframes scaleUp {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}
</style>
