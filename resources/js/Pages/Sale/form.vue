<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePosRecap } from "@/Composable/usePosRecap";

const props = defineProps({
    products: Array,
    categories: Array,
    brands: Array, // [NEW]
    sale: Object,
    mode: String,
});

// Panggil Logic Rekap
const {
    searchQuery,
    selectedCategory,
    selectedBrand, // [NEW]
    selectedSort, // [NEW]
    selectedProductType,
    currentProductType,
    cart,
    filteredProducts,
    totalRevenue,
    form,
    addToCart,
    removeItem,
    processRecap,
    clearCart,
    transactionDate,
} = usePosRecap(props);

// --- INFINITE SCROLL LOGIC ---
const limit = ref(20);
const visibleProducts = computed(() => {
    return filteredProducts.value.slice(0, limit.value);
});

const handleScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    if (scrollTop + clientHeight >= scrollHeight - 50) {
        if (limit.value < filteredProducts.value.length) {
            limit.value += 20;
        }
    }
};

// Reset limit when filter changes (Optional but good UX)
// watch([searchQuery, selectedCategory, selectedBrand], () => { limit.value = 20; });

// Helper Format
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);
const getItemQty = (productId) => {
    const item = cart.value.find((i) => i.id === productId);
    return item ? item.quantity : 0;
};
</script>

<template>
    <Head title="Input Rekap" />

    <AuthenticatedLayout>
        <div class="w-full max-w-7xl mx-auto flex flex-col h-[calc(100vh-8.5rem)] lg:h-[calc(100vh-6rem)] lg:flex-row gap-4 lg:gap-6 p-1 lg:p-4 overflow-hidden">
            <!-- LEFT COLUMN: CATALOGUE (Flexible Width) -->
            <div class="flex-1 flex flex-col min-w-0 bg-white dark:bg-gray-800 rounded-2xl lg:rounded-3xl shadow-xl lg:shadow-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden relative">
                <!-- Header & Search -->
                <div class="shrink-0 p-3 lg:p-4 bg-white/80 dark:bg-gray-800/90 backdrop-blur-md z-10 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-3 lg:mb-4">
                        <div>
                            <h2 class="text-lg lg:text-xl font-black text-gray-800 dark:text-white tracking-tight flex items-center gap-2">
                                <span class="bg-lime-500 text-white w-7 h-7 lg:w-8 lg:h-8 flex items-center justify-center rounded-lg shadow-lime-500/30 shadow-md text-xs lg:text-sm">📦</span>
                                Katalog
                            </h2>
                            <!-- <p class="text-xs text-gray-400 font-medium ml-9 hidden lg:block">Pilih produk</p> -->
                        </div>
                    </div>

                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 group-focus-within:text-lime-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari produk..."
                            class="w-full pl-10 pr-4 py-2 lg:py-3 bg-gray-50 dark:bg-gray-900/50 border-none ring-1 ring-gray-200 dark:ring-gray-700 rounded-xl text-xs lg:text-sm font-medium focus:ring-2 focus:ring-lime-500 transition-all shadow-inner placeholder-gray-400"
                        />
                    </div>

                    <!-- Filters -->
                    <div class="pt-2 lg:pt-3 flex gap-2">
                        <select v-model="selectedBrand" class="flex-1 bg-gray-50 dark:bg-gray-900 border-none rounded-lg text-xs font-bold text-gray-500 py-1.5 lg:py-2 focus:ring-1 focus:ring-lime-500">
                            <option value="all">Semua Brand</option>
                            <option v-for="b in brands" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <select v-model="selectedSort" class="flex-1 bg-gray-50 dark:bg-gray-900 border-none rounded-lg text-xs font-bold text-gray-500 py-1.5 lg:py-2 focus:ring-1 focus:ring-lime-500">
                            <option value="name_asc">A-Z</option>
                            <option value="name_desc">Z-A</option>
                            <option value="price_low">Termurah</option>
                            <option value="price_high">Termahal</option>
                            <option value="most_sold">Terlaris</option>
                        </select>
                    </div>
                </div>

                <!-- Categories -->
                <div class="shrink-0 px-3 lg:px-4 pb-2">
                     <div class="flex gap-2 overflow-x-auto custom-scroll-x pb-2">
                        <button
                            @click="selectedCategory = 'all'"
                            class="px-3 py-1.5 lg:px-4 lg:py-2 rounded-xl text-[10px] lg:text-xs font-bold whitespace-nowrap transition-all duration-300 border"
                            :class="selectedCategory === 'all' 
                                ? 'bg-gray-900 text-white border-gray-900 shadow-lg shadow-gray-900/20 dark:bg-white dark:text-gray-900' 
                                : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400'"
                        >
                            Semua
                        </button>
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            @click="selectedCategory = cat.id"
                            class="px-3 py-1.5 lg:px-4 lg:py-2 rounded-xl text-[10px] lg:text-xs font-bold whitespace-nowrap transition-all duration-300 border"
                             :class="selectedCategory === cat.id
                                ? 'bg-gray-900 text-white border-gray-900 shadow-lg shadow-gray-900/20 dark:bg-white dark:text-gray-900' 
                                : 'bg-white text-gray-500 border-gray-200 hover:border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400'"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                </div>

                <!-- Product Grid -->
                <div 
                    class="flex-1 overflow-y-auto p-3 lg:p-4 custom-scroll bg-gray-50/50 dark:bg-gray-900/20"
                    @scroll="handleScroll"
                >
                    <div v-if="visibleProducts.length === 0" class="flex flex-col items-center justify-center h-48 text-gray-400 animate-fade-in">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        </div>
                        <p class="text-sm font-medium">Produk tidak ditemukan</p>
                    </div>

                    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2 lg:gap-3">
                        <div
                            v-for="product in visibleProducts"
                            :key="product.id"
                            @click="addToCart(product)"
                            class="group relative flex flex-col bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden"
                            :class="{'opacity-60 grayscale': product.stock === 0}"
                        >
                            <!-- Image Section (Aspect Square 1:1) -->
                            <div class="aspect-square relative w-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    loading="lazy"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                                    alt=""
                                    onerror="this.style.display='none'"
                                />
                                <div v-else class="flex items-center justify-center w-full h-full text-gray-300 dark:text-gray-600">
                                    <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>

                                <!-- Overlay Badge: Qty in Cart -->
                                <div v-if="getItemQty(product.id) > 0" class="absolute top-2 right-2 bg-gray-900 text-white dark:bg-white dark:text-gray-900 text-[10px] font-bold w-6 h-6 flex items-center justify-center rounded-full shadow-lg z-20 animate-bounce-short border-2 border-white dark:border-gray-900">
                                    {{ getItemQty(product.id) }}
                                </div>
                                
                                <!-- Overlay Badge: Empty -->
                                <div v-if="product.stock <= 0" class="absolute inset-0 z-10 flex items-center justify-center bg-gray-900/60 backdrop-blur-[1px]">
                                    <span class="px-2 py-1 text-xs font-bold text-white transform border-2 border-white rounded -rotate-12">KOSONG</span>
                                </div>
                            </div>

                            <!-- Content Section -->
                            <div class="flex-1 flex flex-col p-2 lg:p-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[9px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded-md">{{ product.code }}</span>
                                    <span v-if="product.unit" class="text-[9px] font-bold text-lime-700 bg-lime-100 px-1.5 py-0.5 rounded-md">{{ product.unit.name }}</span>
                                </div>

                                <h3 class="text-xs font-bold text-gray-800 dark:text-gray-100 leading-snug line-clamp-2 min-h-[2.2rem] mb-2 group-hover:text-lime-600 dark:group-hover:text-lime-400 transition-colors">
                                    {{ product.name }}
                                </h3>

                                <div class="flex items-center justify-between mt-auto pt-2 border-t border-gray-50 dark:border-gray-700/50">
                                    <div class="flex flex-col">
                                        <span class="font-black text-xs lg:text-sm text-lime-600 dark:text-lime-400">
                                            {{ rp(product.selling_price) }}
                                        </span>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <div class="w-1.5 h-1.5 rounded-full" :class="product.stock > 0 ? 'bg-green-500' : 'bg-red-500'"></div>
                                            <span class="text-[9px] text-gray-400">{{ product.stock }} left</span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="product.total_sold > 0" class="text-[9px] font-bold text-gray-500 bg-gray-100 dark:bg-gray-700 px-1.5 py-1 rounded-md flex flex-col items-center leading-none">
                                        <span>{{ product.total_sold }}</span>
                                        <span class="text-[7px] uppercase mt-0.5 opacity-70">Terjual</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: RECAP FORM (Fixed Width on Desktop, Half Height on Mobile) -->
            <div class="flex flex-col w-full lg:w-[400px] xl:w-[450px] shrink-0 h-[45%] lg:h-full min-w-0 bg-white dark:bg-gray-800 rounded-2xl lg:rounded-3xl shadow-xl lg:shadow-2xl border border-gray-100 dark:border-gray-700/50 overflow-hidden relative">
                <!-- Header -->
                <div class="shrink-0 p-3 lg:p-5 border-b border-gray-100 dark:border-gray-700 bg-white/80 dark:bg-gray-800/90 backdrop-blur-md z-20 flex justify-between items-center">
                   <div>
                        <h2 class="text-lg lg:text-xl font-black text-gray-800 dark:text-white tracking-tight flex items-center gap-2">
                             <span class="bg-blue-500 text-white w-7 h-7 lg:w-8 lg:h-8 flex items-center justify-center rounded-lg shadow-blue-500/30 shadow-md text-xs lg:text-sm">📝</span>
                            Rekap
                        </h2>
                   </div>
                   
                   <div class="flex items-center gap-2 lg:gap-3">
                        <div class="relative">
                            <input
                                type="date"
                                v-model="transactionDate"
                                class="pl-8 pr-3 py-1 lg:py-1.5 text-xs font-bold bg-gray-50 dark:bg-gray-900 border-none rounded-lg focus:ring-1 focus:ring-blue-500 cursor-pointer shadow-sm w-32 lg:w-auto"
                            />
                            <span class="absolute left-2.5 top-1 lg:top-1.5 text-gray-400 text-xs">📅</span>
                        </div>
                         <button
                            v-if="cart.length"
                            @click="clearCart"
                            class="p-1.5 lg:p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all"
                            title="Reset Keranjang"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                   </div>
                </div>

                <!-- Table Content -->
                <div class="flex-1 overflow-auto bg-white custom-scroll dark:bg-gray-800 relative">
                     <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 z-10 bg-gray-50/95 dark:bg-gray-900/95 backdrop-blur shadow-sm">
                            <tr>
                                <th class="py-2 lg:py-3 px-3 lg:px-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider">Produk</th>
                                <th class="py-2 lg:py-3 px-3 lg:px-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider text-center w-16 lg:w-24">Qty</th>
                                <th class="hidden sm:table-cell py-2 lg:py-3 px-3 lg:px-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider text-right w-24 lg:w-32">Harga</th>
                                <th class="hidden sm:table-cell py-2 lg:py-3 px-3 lg:px-4 text-[10px] uppercase font-bold text-gray-400 dark:text-gray-500 tracking-wider text-right w-24 lg:w-32">Subtotal</th>
                                <th class="py-2 lg:py-3 px-3 lg:px-4 w-8 lg:w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                            <tr v-if="cart.length === 0">
                                <td colspan="5" class="py-10 lg:py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-gray-300 dark:text-gray-600">
                                        <svg class="w-16 h-16 lg:w-20 lg:h-20 mb-3 lg:mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="text-xs lg:text-sm font-medium">Kosong</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-for="(item, index) in cart" :key="index" class="group hover:bg-blue-50/30 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="p-3 lg:p-4 align-top">
                                    <div class="font-bold text-gray-800 dark:text-white text-xs lg:text-sm leading-tight mb-0.5">{{ item.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ item.code }}</div>
                                    <!-- Mobile Price -->
                                    <div class="sm:hidden mt-1 text-[10px] font-medium text-gray-500">
                                        {{ rp(item.selling_price) }} x {{ item.quantity }} = <span class="text-gray-900 font-bold">{{ rp(item.quantity * item.selling_price) }}</span>
                                    </div>
                                </td>
                                <td class="p-3 lg:p-4 align-top">
                                    <input
                                        type="number"
                                        v-model="item.quantity"
                                        min="1"
                                        class="w-full py-1 lg:py-1.5 px-0 text-center text-xs lg:text-sm font-bold bg-gray-100 dark:bg-gray-900 border-none rounded-lg focus:ring-2 focus:ring-blue-500 transition-all text-gray-800 dark:text-white"
                                        @focus="$event.target.select()"
                                    />
                                </td>
                                <td class="hidden sm:table-cell p-3 lg:p-4 align-top text-right">
                                    <input
                                        type="number"
                                        v-model="item.selling_price"
                                        class="w-full py-1 lg:py-1.5 px-0 text-right text-xs lg:text-sm font-medium bg-transparent border-b border-gray-200 dark:border-gray-700 focus:border-blue-500 border-dashed focus:ring-0 transition-colors text-gray-600 dark:text-gray-300"
                                        @focus="$event.target.select()"
                                    />
                                </td>
                                <td class="hidden sm:table-cell p-3 lg:p-4 align-top text-right font-black text-gray-800 dark:text-white text-xs lg:text-sm">
                                    {{ rp(item.quantity * item.selling_price) }}
                                </td>
                                <td class="p-3 lg:p-4 align-middle text-center">
                                    <button @click="removeItem(index)" class="text-gray-300 hover:text-red-500 transition-colors p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                     </table>
                </div>

                <!-- Footer Summary -->
                <div class="shrink-0 bg-gray-50/50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 p-5 space-y-4 shadow-inner-sm">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1 relative">
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Catatan transaksi (opsional)..."
                                class="w-full pl-10 pr-4 py-3 bg-white dark:bg-gray-800 border-none rounded-xl text-sm shadow-sm focus:ring-2 focus:ring-blue-500 resize-none placeholder-gray-400"
                            ></textarea>
                            <span class="absolute left-3.5 top-3.5 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </span>
                        </div>
                        
                        <div class="flex flex-col gap-2 min-w-[200px]">
                            <div class="flex justify-between items-end">
                                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total</span>
                                <span class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ rp(totalRevenue) }}</span>
                            </div>
                            <button
                                @click="processRecap"
                                :disabled="!cart.length || form.processing"
                                class="w-full py-3.5 px-6 bg-gradient-to-r from-gray-900 to-gray-800 hover:from-black hover:to-gray-900 text-white rounded-xl font-bold shadow-lg shadow-gray-900/20 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <span>SIMPAN TRANSAKSI</span>
                                <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scrollbar Vertical (Untuk Tabel/List Produk) */
.custom-scroll::-webkit-scrollbar {
    width: 5px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* [BARU] Scrollbar Horizontal Tipis (Untuk Kategori) */
.custom-scroll-x::-webkit-scrollbar {
    height: 3px; /* Tinggi sangat kecil */
}
.custom-scroll-x::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll-x::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}
.dark .custom-scroll-x::-webkit-scrollbar-thumb {
    background: #4b5563;
}

/* Hilangkan panah input number */
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
