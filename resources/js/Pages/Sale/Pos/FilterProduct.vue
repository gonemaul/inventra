<script setup>
import { computed, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import BottomSheet from "@/Components/BottomSheet.vue";

// 1. Menerima Data dari Parent
const props = defineProps({
    // Data Utama
    categories: { type: Array, required: true },
    brands: { type: Array, default: () => [] },
    
    isFetching: { type: Boolean, default: false }, // Status loading sync

    // State Filter (v-model bindings)
    search: { type: String, default: "" }, 
    category: { type: [String, Number], default: "all" }, 
    subCategory: { type: [String, Number], default: "all" }, 
    brand: { type: [String, Number], default: "all" }, 
    size: { type: [String, Number], default: "all" }, // New Size Prop
    sort: { type: String, default: "default" }, 
    hideEmptyStock: { type: Boolean, default: false }, 
    
    sizes: { type: Array, default: () => [] },
});

// 2. Mengirim Event ke Parent
const emit = defineEmits([
    "update:search",
    "update:category",
    "update:subCategory",
    "update:brand",
    "update:size", // New Emit
    "update:sort",
    "update:hideEmptyStock",
    "scan", // Event tombol scan
]);

const showFilterModal = ref(false);

// Helper: Hitung Sub Kategori berdasarkan Kategori yang dipilih
const activeSubCategories = computed(() => {
    if (props.category === "all") return [];
    const cat = props.categories.find((c) => c.id == props.category);
    return cat ? cat.product_types || [] : []; 
});

const selectCategory = (val) => {
    emit("update:category", val);
    emit("update:subCategory", "all"); // Reset sub saat ganti kategori utama
    emit("update:brand", "all"); // Reset brand
    emit("update:size", "all"); // Reset size
};

const activeFiltersCount = computed(() => {
    let count = 0;
    if (props.subCategory !== 'all') count++;
    if (props.brand !== 'all') count++;
    if (props.size !== 'all' && props.size) count++; // Count Size
    if (props.sort !== 'default') count++;
    // if (props.hideEmptyStock) count++; // Maybe don't count this as "active filter" since it's default true often
    return count;
});

// Helper for resetting
const resetFilters = () => {
    emit('update:subCategory', 'all');
    emit('update:brand', 'all');
    emit('update:size', 'all');
    emit('update:sort', 'default');
    emit('update:hideEmptyStock', false);
    showFilterModal.value = false;
};
</script>

<template>
    <div class="flex flex-col bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 shadow-sm z-30 transition-colors duration-300">
        <!-- Top Bar: Search & Scan -->
        <div class="px-4 py-3 pb-0 flex items-center gap-3">
             <Link
                :href="route('dashboard')"
                class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </Link>
            
            <div class="relative flex-1">
                <input
                    :value="search"
                    @focus="$event.target.select()"
                    @input="$emit('update:search', $event.target.value)"
                    type="search"
                    placeholder="Cari produk..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-100 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-lime-500 text-sm font-medium text-gray-800 dark:text-white placeholder-gray-400 transition-all"
                />
                <span class="absolute left-3.5 top-2.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                
               <!-- Loading Indicator -->
                <div v-if="isFetching" class="absolute right-3 top-3">
                     <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-lime-500"></span>
                    </span>
                </div>
            </div>

            <button
                @click="$emit('scan')"
                class="p-2.5 rounded-xl bg-gray-900 text-white dark:bg-lime-500 dark:text-gray-900 shadow-lg active:scale-95 transition"
            >
            <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                    </svg>
                <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg> -->
            </button>
        </div>

        <!-- Middle Bar: Horizontal Category Scroll + Filter Button -->
        <div class="flex items-center gap-2 pl-4 py-3 overflow-hidden">
             <!-- Filter Button -->
             <button 
                @click="showFilterModal = true"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border text-xs font-bold whitespace-nowrap transition active:scale-95 shrink-0"
                :class="activeFiltersCount > 0 
                    ? 'bg-lime-500 border-lime-500 text-white shadow-md shadow-lime-500/20' 
                    : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300'"
             >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                Filter
                <span v-if="activeFiltersCount > 0" class="flex items-center justify-center w-4 h-4 text-[9px] bg-white text-lime-600 rounded-full">{{ activeFiltersCount }}</span>
             </button>

             <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 shrink-0 mx-1"></div>

             <!-- Categories -->
             <div class="flex items-center gap-2 overflow-x-auto scrollbar-hide pr-4 mask-fade-right">
                 <button
                    @click="selectCategory('all')"
                    class="px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95 border"
                    :class="category === 'all'
                        ? 'bg-gray-900 dark:bg-lime-500 text-white dark:text-gray-900 border-gray-900 dark:border-lime-500'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    Semua
                </button>
                 <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectCategory(cat.id)"
                    class="px-3 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition active:scale-95 border"
                    :class="category === cat.id
                        ? 'bg-gray-900 dark:bg-lime-500 text-white dark:text-gray-900 border-gray-900 dark:border-lime-500'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    {{ cat.name }}
                </button>
             </div>
        </div>
    </div>

    <!-- Advanced Filter Modal -->
    <BottomSheet :show="showFilterModal" @close="showFilterModal = false" title="Filter & Urutkan">
        <template #default>
            <div class="space-y-6 pb-6 p-4">
                <!-- 1. Sort -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Urutkan Berdasarkan</h4>
                    <div class="grid grid-cols-4 gap-2">
                        <button 
                            v-for="opt in [
                                { val: 'default', label: 'Nama (A-Z)', icon: 'AZ' },
                                { val: 'bestseller', label: 'Terlaris', icon: '🔥' },
                                { val: 'cheapest', label: 'Termurah', icon: '💰' },
                                { val: 'recommendation', label: 'Rekomendasi', icon: '⭐', disabled: category === 'all' }
                            ]"
                            :key="opt.val"
                            :disabled="opt.disabled"
                            @click="!opt.disabled && $emit('update:sort', opt.val)"
                            class="flex flex-col items-center justify-center p-3 rounded-xl border transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-100 disabled:border-gray-200 dark:disabled:bg-gray-800 dark:disabled:border-gray-700"
                            :class="[
                                sort === opt.val 
                                    ? 'bg-lime-50 border-lime-500 text-lime-700 dark:bg-lime-900/20 dark:text-lime-400' 
                                    : 'bg-white border-gray-200 text-gray-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300',
                            ]"
                            :title="opt.disabled ? 'Pilih kategori terlebih dahulu' : ''"
                        >
                            <span class="text-lg mb-1">{{ opt.icon }}</span>
                            <span class="text-xs font-bold text-center leading-tight">{{ opt.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Sub Category (If Category Selected) -->
                <div v-if="activeSubCategories.length > 0">
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Tipe Produk</h4>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="$emit('update:subCategory', 'all')"
                            class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                             :class="subCategory === 'all' 
                                ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                        >
                            Semua
                        </button>
                        <button
                            v-for="sub in activeSubCategories"
                            :key="sub.id"
                            @click="$emit('update:subCategory', sub.id)"
                            class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                            :class="subCategory === sub.id 
                                ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                        >
                            {{ sub.name }}
                        </button>
                    </div>
                </div>

                <!-- 3. Dynamic Filters (Brand & Size) -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- Brand Filter -->
                    <!-- Always show Brand filter if there are brands available (either dynamic or static) -->
                    <div v-if="brands.length > 0">
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Merk / Brand</h4>
                         <div class="flex flex-wrap gap-2 max-h-[120px] overflow-y-auto custom-scroll pr-1">
                            <button
                                @click="$emit('update:brand', 'all')"
                                class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                                 :class="brand === 'all' 
                                    ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                    : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                            >
                                Semua
                            </button>
                            <button
                                v-for="b in brands"
                                :key="b.id"
                                @click="$emit('update:brand', b.id)"
                                class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                                :class="brand === b.id 
                                    ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                    : 'bg-white text-gray-600 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                            >
                                {{ b.name }}
                            </button>
                         </div>
                    </div>

                    <!-- Size Filter -->
                    <!-- Only show Size filter if sizes are available (which backend controls based on Category) -->
                    <div v-if="sizes && sizes.length > 0">
                        <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Ukuran / Size</h4>
                         <div class="flex flex-wrap gap-2 max-h-[120px] overflow-y-auto custom-scroll pr-1">
                            <button
                                @click="$emit('update:size', 'all')"
                                class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                                 :class="size === 'all' 
                                    ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                    : 'bg-gray-50 text-gray-500 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                            >
                                Semua
                            </button>
                            <button
                                v-for="s in sizes"
                                :key="s.id"
                                @click="$emit('update:size', s.id)"
                                class="px-3 py-1.5 rounded-lg border text-xs font-bold transition active:scale-95"
                                :class="size === s.id 
                                    ? 'bg-gray-800 text-white dark:bg-lime-500 dark:text-gray-900 border-gray-800 dark:border-lime-500' 
                                    : 'bg-white text-gray-600 border-gray-200 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300'"
                            >
                                {{ s.name }}
                            </button>
                         </div>
                    </div>
                </div>
                
                <!-- 4. Options -->
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-3">Opsi Lainnya</h4>
                    <div class="flex items-center justify-between p-3 rounded-xl border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Sembunyikan Stok Habis</span>
                        <button 
                            @click="$emit('update:hideEmptyStock', !hideEmptyStock)"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                            :class="hideEmptyStock ? 'bg-lime-500' : 'bg-gray-200 dark:bg-gray-600'"
                        >
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"
                                :class="hideEmptyStock ? 'translate-x-6' : 'translate-x-1'"
                            />
                        </button>
                    </div>
                </div>

                <div class="pt-4 flex gap-3">
                     <button 
                        @click="resetFilters"
                        class="flex-1 py-3.5 bg-gray-100 text-gray-600 font-bold rounded-xl active:scale-95 transition"
                    >
                        Reset
                    </button>
                    <button 
                        @click="showFilterModal = false"
                        class="flex-[2] py-3.5 bg-gray-900 text-white font-bold rounded-xl active:scale-95 transition shadow-lg"
                    >
                        Terapkan
                    </button>
                </div>
            </div>
        </template>
    </BottomSheet>
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
}
</style>
