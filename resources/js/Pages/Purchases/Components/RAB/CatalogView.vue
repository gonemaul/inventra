<script setup>
import { ref, watch, onMounted, computed } from "vue";
import axios from "axios";
import debounce from "lodash/debounce";
import CatalogCard from "./CatalogCard.vue";

// --- PROPS & EMITS ---
const props = defineProps({
    supplierId: {
        type: [Number, String],
        required: true,
    },
    items: Object,
    stagingItem: Object,
    categories: Array,
    brands: Array,
});

const emit = defineEmits(["select-product"]);

// --- STATE ---
const products = ref([]);
const isLoading = ref(false); // Loading awal / ganti supplier
const isLoadingMore = ref(false); // Loading infinite scroll
const page = ref(1);
const hasMore = ref(true);
const searchKeyword = ref("");
const observerTarget = ref(null); 

// Filter State
const filterCategory = ref("all");
const filterSubCategory = ref("all");
const filterBrand = ref("all");
const filterSize = ref("all"); // New Size Filter
const filterStock = ref(""); // '' | 'empty' | 'low' | 'safe'

// Dynamic Data (from Backend)
const dynamicCategories = ref([]);
const dynamicBrands = ref([]);
const dynamicSizes = ref([]);
const dynamicTypes = ref([]);

// Computed SubCategories (Dynamic based on Category)
const activeSubCategories = computed(() => {
    if (filterCategory.value === "all") return [];
    const cat = props.categories.find((c) => c.id == filterCategory.value);
    return cat ? cat.product_types || [] : [];
});

// --- 1. CORE LOGIC: FETCH DATA ---
const fetchProducts = async (reset = false) => {
    if (!props.supplierId) return;

    if (reset) {
        isLoading.value = true;
        page.value = 1;
        products.value = [];
        hasMore.value = true;
    } else {
        isLoadingMore.value = true;
    }

    try {
        const response = await axios.get(
            `/purchases/products/${props.supplierId}`,
            {
                params: {
                    page: page.value,
                    search: searchKeyword.value,
                    // Kirim Parameter Filter
                    category_id: filterCategory.value === 'all' ? null : filterCategory.value,
                    product_type_id: filterSubCategory.value === 'all' ? null : filterSubCategory.value,
                    brand_id: filterBrand.value === 'all' ? null : filterBrand.value,
                    size_id: filterSize.value === 'all' ? null : filterSize.value,
                    stock_status: filterStock.value || null,
                },
            }
        );

        // Backend returns: { products: { data: [], ... }, available_brands: [], available_sizes: [] }
        const result = response.data;
        const newItems = result.products.data;

        // Update Dynamic Filters (Hanya update jika reset/ganti filter, biar gak kedip-kedip saat scroll)
        // Atau update selalu agar akurat? POS update selalu.
        if (result.available_brands) dynamicBrands.value = result.available_brands;
        if (result.available_sizes) dynamicSizes.value = result.available_sizes;

        if (reset) {
            products.value = newItems;
        } else {
            products.value = [...products.value, ...newItems];
        }

        hasMore.value = !!result.products.next_page_url;
        if (hasMore.value) page.value++;
    } catch (error) {
        console.error("Gagal load katalog:", error);
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
};

// --- 2. FITUR: AUTO REFRESH ---



// Reset Pagination saat Filter Ganti
const applyFilter = () => {
    // Jika ada filter manual terpilih, kita hapus kata kunci pencarian agar filter berfungsi.
    if (searchKeyword.value) searchKeyword.value = ""; 
    
    fetchProducts(true);
};

// Reset Filter saat Searching
const handleSearch = debounce(() => {
    // SESUAI REQUEST: Search menjadi filter tertinggi.
    // Jika ada kata kunci, kita reset filter lainnya agar pencarian mencakup seluruh katalog.
    if (searchKeyword.value.trim() !== "") {
        filterCategory.value = "all";
        filterSubCategory.value = "all";
        filterBrand.value = "all";
        filterSize.value = "all";
        filterStock.value = "";
    }
    fetchProducts(true);
}, 500);

const clearSearch = () => {
    searchKeyword.value = "";
    fetchProducts(true);
};

const resetFilters = () => {
    searchKeyword.value = "";
    filterCategory.value = "all";
    filterSubCategory.value = "all";
    filterBrand.value = "all";
    filterSize.value = "all";
    filterStock.value = "";
    // Kembalikan ke props awal agar tidak kosong
    dynamicCategories.value = props.categories || [];
    dynamicBrands.value = props.brands || [];
    dynamicTypes.value = [];
    dynamicSizes.value = [];
};

// --- RESET LOGIC (Smart Filters) ---
watch(filterCategory, (newVal) => {
    // Reset lower levels
    if (newVal === 'all') {
        dynamicTypes.value = [];
    }
    filterSubCategory.value = 'all';
    filterSize.value = 'all';
    // fetchProducts triggered by @change in template, but if we want to be safe we can rely on that.
    // However, CatalogView uses @change="applyFilter" on selects, which calls fetchProducts.
    // So the Watcher here effectively just cleans up the State *before* or *during* the fetch?
    // Actually applyFilter calls fetchProducts.
    // This watcher might trigger AFTER applyFilter updates the model?
    // Use `applyFilter` function modification instead?
    // Modifying `applyFilter` is better to ensure single flow.
});

watch(filterBrand, () => {
    // Brand also restricts Type usually (though technically Type belongs to Category, but "Available Types" depends on Brand)
    filterSubCategory.value = 'all';
    filterSize.value = 'all';
});

// --- 4. INFINITE SCROLL ---
const onIntersect = (entries) => {
    const entry = entries[0];
    if (
        entry.isIntersecting &&
        hasMore.value &&
        !isLoading.value &&
        !isLoadingMore.value
    ) {
        fetchProducts(false);
    }
};

onMounted(() => {
    const observer = new IntersectionObserver(onIntersect, { threshold: 0.1 });
    if (observerTarget.value) observer.observe(observerTarget.value);
});

// Reset Total saat Supplier Ganti
watch(
    () => props.supplierId,
    (newId) => {
        if (newId) {
            resetFilters();
            fetchProducts(true);
        } else {
            products.value = [];
        }
    },
    { immediate: true }
);

const getItemInCart = (itemId) => {
    return props.items.find((c) => c.product_id === itemId);
};

const selectItem = (item) => {
    emit("select-product", item);
};
</script>

<template>
    <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
        <div
            class="sticky top-0 z-20 bg-white border-b shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700"
        >
        <!-- Sort Info Banner -->
        <div class="px-4 pt-3 pb-0">
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/50 rounded-xl p-2.5 flex items-start gap-2.5 text-indigo-700 dark:text-indigo-300 shadow-sm">
                <svg class="w-4 h-4 mt-0.5 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div class="text-[11px] leading-relaxed">
                    <span class="font-bold">Urutan Relevansi:</span> 
                    Berdasarkan status <span class="font-bold text-red-600 dark:text-red-400">Habis Terjual (Laris)</span> ➔ <span class="font-bold text-yellow-600 dark:text-yellow-400">Menipis (Laris)</span> ➔ <span class="font-bold text-lime-600 dark:text-lime-400">Tersedia (Laris)</span> ➔ <span class="font-bold text-gray-500">Stok Mati / Kurang Laris</span>.
                </div>
            </div>
        </div>

            <div class="pb-2 px-4 py-3 flex items-center gap-3">
                <div class="relative flex-1 group">
                    <input
                        v-model="searchKeyword"
                        @input="handleSearch"
                        @focus="$event.target.select()"
                        type="text"
                        placeholder="Cari produk (Nama, SKU, atau Barcode)..."
                        class="w-full pl-10 pr-10 py-3 bg-gray-100 dark:bg-gray-700/50 border-2 border-transparent focus:border-lime-500 focus:bg-white dark:focus:bg-gray-800 rounded-2xl focus:ring-0 text-sm font-semibold text-gray-800 dark:text-white placeholder-gray-400 transition-all shadow-inner"
                    />
                    <span class="absolute left-3.5 top-3.5 text-gray-400 group-focus-within:text-lime-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    
                    <!-- Clear Button -->
                    <button 
                        v-if="searchKeyword" 
                        @click="clearSearch"
                        class="absolute right-3 top-3 text-gray-400 hover:text-red-500 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="absolute right-10 top-3.5">
                         <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-lime-500"></span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Smart Search Status Indicator -->
            <div v-if="searchKeyword && !isLoading" class="px-4 pb-2">
                <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400 italic">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Menampilkan hasil pencarian untuk "<span class="text-lime-600 dark:text-lime-400 font-bold">{{ searchKeyword }}</span>"
                </div>
            </div>

            <!-- Filters Section -->
            <div class="flex flex-col gap-3 px-4 pb-4">
                <!-- Category Section with distinct background -->
                <div class="p-2 bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-100 dark:border-gray-700/50">
                    <div class="flex items-center gap-2 overflow-x-auto mask-fade-right scrollbar-hide scroll-smooth">
                        <!-- 🚨 Butuh Restok Filter (Quick Action) -->
                        <button 
                             @click="filterStock = filterStock === 'low_empty' ? 'low_empty' : 'low_empty'; applyFilter()"
                             class="px-4 py-2 text-[11px] font-black rounded-xl border transition-all whitespace-nowrap flex items-center gap-1.5 shrink-0 shadow-sm uppercase tracking-wider"
                             :class="filterStock === 'low_empty' 
                                ? 'bg-red-600 text-white border-red-600 ring-2 ring-red-100 dark:ring-red-900/40' 
                                : 'bg-white border-gray-200 text-red-600 dark:bg-gray-800 dark:border-gray-700 hover:border-red-300'"
                        >
                            <span>🚨</span>
                            Restok
                        </button>

                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 shrink-0 mx-1"></div>

                        <!-- Categories Chips -->
                        <button
                            @click="filterCategory = 'all'; applyFilter()"
                            class="px-4 py-2 text-[11px] font-black rounded-xl transition-all whitespace-nowrap shrink-0 border shadow-sm uppercase tracking-wider"
                            :class="filterCategory === 'all' ? 'bg-indigo-600 text-white border-indigo-600 ring-2 ring-indigo-100 dark:ring-indigo-900/40' : 'bg-white text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 hover:bg-gray-50'"
                        >
                            Semua
                        </button>
                        <button
                            v-for="c in dynamicCategories" :key="c.id"
                            @click="filterCategory = c.id; applyFilter()"
                            class="px-4 py-2 text-[11px] font-black rounded-xl transition-all whitespace-nowrap shrink-0 border shadow-sm uppercase tracking-wider"
                            :class="filterCategory === c.id ? 'bg-indigo-600 text-white border-indigo-600 ring-2 ring-indigo-100 dark:ring-indigo-900/40' : 'bg-white text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 hover:bg-gray-50'"
                        >
                            {{ c.name }}
                        </button>
                    </div>
                </div>

                <!-- Secondary Filters Section (Dropdowns) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5">
                    <!-- Brand Filter Group -->
                    <div class="relative">
                        <select 
                            v-model="filterBrand" 
                            @change="applyFilter"
                            class="w-full text-xs font-bold py-2.5 pl-3 pr-8 bg-amber-50/50 border border-amber-200/50 rounded-xl focus:ring-amber-500 focus:border-amber-500 dark:bg-amber-900/10 dark:border-amber-800/50 dark:text-amber-200 transition-all shadow-sm appearance-none"
                            :class="{'ring-2 ring-amber-500/20 border-amber-500 bg-amber-50 dark:bg-amber-900/20': filterBrand !== 'all'}"
                        >
                            <option value="all">Semua Brand</option>
                            <option v-for="b in dynamicBrands" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                        <div class="absolute right-2.5 top-3 pointer-events-none text-amber-500/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Type Filter Group -->
                    <div v-if="filterCategory !== 'all' && dynamicTypes.length > 0" class="relative">
                        <select 
                            v-model="filterSubCategory" 
                            @change="applyFilter"
                            class="w-full text-xs font-bold py-2.5 pl-3 pr-8 bg-purple-50/50 border border-purple-200/50 rounded-xl focus:ring-purple-500 focus:border-purple-500 dark:bg-purple-900/10 dark:border-purple-800/50 dark:text-purple-200 transition-all shadow-sm appearance-none"
                            :class="{'ring-2 ring-purple-500/20 border-purple-500 bg-purple-50 dark:bg-purple-900/20': filterSubCategory !== 'all'}"
                        >
                            <option value="all">Semua Tipe</option>
                            <option v-for="t in dynamicTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </select>
                        <div class="absolute right-2.5 top-3 pointer-events-none text-purple-500/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Size Filter Group -->
                    <div v-if="dynamicSizes.length > 0" class="relative">
                        <select 
                            v-model="filterSize" 
                            @change="applyFilter"
                            class="w-full text-xs font-bold py-2.5 pl-3 pr-8 bg-cyan-50/50 border border-cyan-200/50 rounded-xl focus:ring-cyan-500 focus:border-cyan-500 dark:bg-cyan-900/10 dark:border-cyan-800/50 dark:text-cyan-200 transition-all shadow-sm appearance-none"
                            :class="{'ring-2 ring-cyan-500/20 border-cyan-500 bg-cyan-50 dark:bg-cyan-900/20': filterSize !== 'all'}"
                        >
                            <option value="all">Semua Ukuran</option>
                            <option v-for="s in dynamicSizes" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                        <div class="absolute right-2.5 top-3 pointer-events-none text-cyan-500/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>

                    <!-- Stock Status Filter Group -->
                    <div class="relative">
                        <select 
                            v-model="filterStock" 
                            @change="applyFilter"
                            class="w-full text-xs font-bold py-2.5 pl-3 pr-8 bg-emerald-50/50 border border-emerald-200/50 rounded-xl focus:ring-emerald-500 focus:border-emerald-500 dark:bg-emerald-900/10 dark:border-emerald-800/50 dark:text-emerald-200 transition-all shadow-sm appearance-none"
                            :class="{'ring-2 ring-red-500/20 border-red-500 bg-red-50 dark:bg-red-900/20': filterStock === 'low_empty'}"
                        >
                            <option value="">Status Stok (Semua)</option>
                            <option value="empty">Habis (0)</option>
                            <option value="low">Menipis (≤ Min)</option>
                            <option value="safe">Aman</option>
                            <option value="low_empty">Butuh Restok</option>
                        </select>
                        <div class="absolute right-2.5 top-3 pointer-events-none text-emerald-500/50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex-1 py-3 overflow-y-auto md:p-2 custom-scrollbar">
            <div
                v-if="isLoading"
                class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
            >
                <div
                    v-for="n in 8"
                    :key="n"
                    class="flex flex-col p-2 bg-white rounded-lg shadow dark:bg-gray-800 animate-pulse"
                >
                    <div
                        class="w-full mb-2 bg-gray-200 rounded aspect-square dark:bg-gray-700"
                    ></div>
                    <div
                        class="w-3/4 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                    ></div>
                    <div
                        class="w-1/2 h-3 mt-auto bg-gray-200 rounded dark:bg-gray-700"
                    ></div>
                </div>
            </div>

            <div
                v-else-if="products.length > 0"
                class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
            >
                <CatalogCard
                    v-for="item in products"
                    :key="item.id"
                    :item="item"
                    :is-selected="stagingItem.product_id === item.id"
                    :is-in-cart="!!getItemInCart(item.id)"
                    :cart-qty="getItemInCart(item.id)?.quantity || 0"
                    :search-term="searchKeyword"
                    @select="selectItem"
                />
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center py-10 text-gray-400"
            >
                <svg
                    class="w-12 h-12 mb-2 opacity-50"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    ></path>
                </svg>
                <p class="text-sm">Tidak ada produk ditemukan</p>
            </div>

            <div v-if="isLoadingMore" class="flex justify-center py-4">
                <svg
                    class="w-5 h-5 text-blue-500 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <div ref="observerTarget" class="w-full h-4"></div>
        </div>
    </div>
</template>
<style scoped>
.mask-fade-right {
    mask-image: linear-gradient(to right, black 90%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, black 90%, transparent 100%);
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}</style>
