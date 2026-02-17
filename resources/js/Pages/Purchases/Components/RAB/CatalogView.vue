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
    // Jika Search ada isi, hapus search dulu agar filter aktif? 
    // SESUAI REQUEST: Search override filter. 
    // Jadi kalau user klik filter, kita asumsikan dia ingin filter manual, jadi search kita kosongkan biar gak bingung.
    if (searchKeyword.value) searchKeyword.value = ""; 
    
    fetchProducts(true);
};

// Reset Filter saat Searching
const handleSearch = debounce(() => {
    // Search tdk mereset filter total, hanya menyesuaikan hasil
    fetchProducts(true);
}, 500);

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
            class="sticky top-0 z-20 bg-white border-b shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <!-- Search Bar -->
            <div class="pb-2 px-4 py-3 flex items-center gap-3">
                <div class="relative flex-1">
                <input
                    v-model="searchKeyword"
                    @input="handleSearch"
                    @focus="$event.target.select()"
                    type="search"
                    placeholder="Cari produk..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-100 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-lime-500 text-sm font-medium text-gray-800 dark:text-white placeholder-gray-400 transition-all"
                />
                <span class="absolute left-3.5 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                
                <!-- Loading Indicator -->
                <div v-if="isLoading" class="absolute right-3 top-3">
                     <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-lime-500"></span>
                    </span>
                </div>
            </div>
            </div>

            <!-- Filters Scrollable -->
            <div class="px-3 pb-3 overflow-x-auto no-scrollbar scroll-smooth mask-fade-right">
                <div class="flex items-center gap-2">
                    
                    <!-- Kategori -->
                    <select 
                        v-model="filterCategory" 
                        @change="applyFilter"
                        class="text-xs font-medium py-1.5 pl-2 pr-8 bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all"
                    >
                        <option value="all">Semua Kategori</option>
                        <option v-for="c in dynamicCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <!-- Brand (Level 1/2) -->
                    <select 
                        v-model="filterBrand" 
                        @change="applyFilter"
                        class="text-xs font-medium py-1.5 pl-2 pr-8 bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all"
                    >
                        <option value="all">Semua Brand</option>
                        <option v-for="b in dynamicBrands" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>

                     <!-- Tipe Produk (Level 2 - Muncul jika Kategori dipilih) -->
                    <select 
                        v-if="filterCategory !== 'all' && dynamicTypes.length > 0"
                        v-model="filterSubCategory" 
                        @change="applyFilter"
                        class="text-xs font-medium py-1.5 pl-2 pr-8 bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all duration-300 animate-fade-in"
                    >
                        <option value="all">Semua Tipe</option>
                        <option v-for="t in dynamicTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>

                    <!-- Size (Level 3 - Muncul jika ada filter) -->
                    <select 
                        v-if="dynamicSizes.length > 0"
                        v-model="filterSize" 
                        @change="applyFilter"
                        class="text-xs font-medium py-1.5 pl-2 pr-8 bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all duration-300 animate-fade-in"
                    >
                        <option value="all">Semua Ukuran</option>
                        <option v-for="s in dynamicSizes" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>

                    <!-- Stock Status Logic -->
                    <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                    <!-- Filter Chips Stocks -->
                    <button 
                         @click="filterStock = filterStock === 'empty' ? '' : 'empty'; applyFilter()"
                         class="px-3 py-1.5 text-xs font-bold rounded-full border transition-all whitespace-nowrap"
                         :class="filterStock === 'empty' 
                            ? 'bg-red-500 text-white border-red-500 shadow-md shadow-red-500/30' 
                            : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 hover:border-gray-300'"
                    >
                        Habis
                    </button>
                    <button 
                         @click="filterStock = filterStock === 'low' ? '' : 'low'; applyFilter()"
                         class="px-3 py-1.5 text-xs font-bold rounded-full border transition-all whitespace-nowrap"
                         :class="filterStock === 'low' 
                            ? 'bg-yellow-500 text-white border-yellow-500 shadow-md shadow-yellow-500/30' 
                            : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 hover:border-gray-300'"
                    >
                        Menipis
                    </button>
                </div>
            </div>
        </div>

        <div class="flex-1 py-3 overflow-y-auto md:p-3 custom-scrollbar">
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
}</style>
