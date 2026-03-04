<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    items: {
        type: Array, // items from create.vue (cartItems)
        default: () => [],
    },
    isDraft: Boolean,
    isEditMode: Boolean,
    brands: {
        type: Array,
        default: () => [],
    }
});

const emit = defineEmits(["remove", "edit", "remove-multiple"]);

const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};

// SEARCH & FILTER STATE
const searchQuery = ref("");
const sortOption = ref("name_asc"); // default
const selectedBrand = ref("");

// FILTERED & SORTED ITEMS
const filteredAndSortedItems = computed(() => {
    // 1. Search Logic
    let result = props.items;
    
    if (searchQuery.value.trim() !== '') {
        const terms = searchQuery.value.toLowerCase().split(' ').filter(t => t);
        result = result.filter(item => {
            const searchString = `${item.name || ''} ${item.code || ''} ${item.brand || ''} ${item.category || ''}`.toLowerCase();
            return terms.every(term => searchString.includes(term));
        });
    }

    // 2. Brand Filter
    if (selectedBrand.value) {
        result = result.filter(item => (item.brand?.name || item.brand) === selectedBrand.value);
    }

    // 3. Sort Logic
    return result.sort((a, b) => {
        switch (sortOption.value) {
            case 'price_desc':
                return (b.purchase_price || 0) - (a.purchase_price || 0);
            case 'price_asc':
                return (a.purchase_price || 0) - (b.purchase_price || 0);
            case 'qty_desc':
                return (b.quantity || 0) - (a.quantity || 0);
            case 'qty_asc':
                return (a.quantity || 0) - (b.quantity || 0);
            case 'total_desc':
                return ((b.purchase_price || 0) * (b.quantity || 0)) - ((a.purchase_price || 0) * (a.quantity || 0));
            case 'name_asc':
            default:
                const nameA = a.name?.toLowerCase() || "";
                const nameB = b.name?.toLowerCase() || "";
                if (nameA < nameB) return -1;
                if (nameA > nameB) return 1;
                return 0;
        }
    });
});

// Load More Logic
const displayedCount = ref(12);
const currentDisplayItems = computed(() => {
    if (!props.isEditMode) return filteredAndSortedItems.value;
    return filteredAndSortedItems.value.slice(0, displayedCount.value);
});

const loadMore = () => {
    if (props.isEditMode) {
        displayedCount.value += 12;
    }
};

// Intersection Observer for Infinite Scroll
const loadMoreTrigger = ref(null);
let observer = null;

import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting) {
            loadMore();
        }
    });
    if (loadMoreTrigger.value) observer.observe(loadMoreTrigger.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

const totalAmount = computed(() => {
    return props.items.reduce((sum, item) => sum + (item.purchase_price * item.quantity), 0);
});

// BULK SELECTION
const selectedItems = ref([]); // Stores IDs of selected items
const allSelected = computed({
    get() {
        return currentDisplayItems.value.length > 0 && 
            currentDisplayItems.value.every(i => selectedItems.value.includes(i.product_id || i.id));
    },
    set(value) {
        if (value) {
            // Only select visible items
            const currentIds = currentDisplayItems.value.map(i => i.product_id || i.id);
            // Merge unique
            selectedItems.value = [...new Set([...selectedItems.value, ...currentIds])];
        } else {
            // Deselect visible items only
            const currentIds = currentDisplayItems.value.map(i => i.product_id || i.id);
            selectedItems.value = selectedItems.value.filter(id => !currentIds.includes(id));
        }
    }
})

const showConfirmModal = ref(false);
const itemToDelete = ref(null);

const g = (item) => {
    if (props.isEditMode) {
        itemToDelete.value = item;
        showConfirmModal.value = true;
    } else {
        emit('remove', item);
    }
};

const handleBulkDelete = () => {
    if (props.isEditMode) {
        itemToDelete.value = 'bulk';
        showConfirmModal.value = true;
    } else {
        emit('remove-multiple', selectedItems.value);
        selectedItems.value = [];
    }
};

const executeDelete = () => {
    if (itemToDelete.value === 'bulk') {
        emit('remove-multiple', selectedItems.value);
        selectedItems.value = [];
    } else if (itemToDelete.value) {
        emit('remove', itemToDelete.value);
    }
    showConfirmModal.value = false;
    itemToDelete.value = null;
};

// Clear selection if items change (e.g. deleted externally) based on IDs preserving valid ones
watch(() => props.items, (newItems) => {
    const activeIds = newItems.map(i => i.product_id || i.id);
    selectedItems.value = selectedItems.value.filter(id => activeIds.includes(id));
}, { deep: true });

</script>

<template>
    <div class="space-y-4">
        <!-- HEADER / SUMMARY -->
        <div class="flex flex-col sm:flex-row justify-between items-center bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm sticky top-0 z-20">
            <!-- Left: Select All / Count -->
             <div class="flex items-center gap-4 w-full sm:w-auto">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" v-model="allSelected" class="w-5 h-5 rounded border-gray-300 text-lime-600 focus:ring-lime-500 transition">
                    <span class="font-bold text-gray-700 dark:text-gray-200">
                        {{ selectedItems.length > 0 ? `${selectedItems.length} Terpilih` : 'Pilih Semua' }}
                    </span>
                </label>
                
                <button 
                    v-if="selectedItems.length > 0"
                    @click="handleBulkDelete"
                    class="ml-auto sm:ml-0 px-3 py-1.5 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 transition flex items-center gap-1 shadow-sm"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
             </div>

            <!-- Right: Total -->
            <div class="text-right mt-2 sm:mt-0 w-full sm:w-auto border-t sm:border-t-0 pt-2 sm:pt-0 flex justify-between sm:block">
                <span class="text-xs text-gray-500 uppercase font-bold tracking-wider">Total Semua</span>
                <div class="text-xl font-black text-lime-600 dark:text-lime-400">{{ formatRupiah(totalAmount) }}</div>
            </div>
        </div>

        <!-- SEARCH & FILTER TOOLBAR -->
        <div class="flex flex-col gap-3 sm:flex-row mb-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input 
                    v-model="searchQuery"
                    type="search" 
                    placeholder="Cari barang di daftar belanja..." 
                    class="block w-full p-2.5 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-lime-500 dark:focus:border-lime-500 shadow-sm"
                >
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <div class="flex-1 sm:w-48">
                    <select 
                        v-model="selectedBrand"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-lime-500 focus:border-lime-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-lime-500 dark:focus:border-lime-500 shadow-sm font-medium"
                    >
                        <option value="">Semua Brand</option>
                        <option v-for="brand in props.brands" :key="brand.id || brand" :value="brand.name || brand">
                            {{ brand.name || brand }}
                        </option>
                    </select>
                </div>
                <div class="flex-1 sm:w-48">
                    <select 
                        v-model="sortOption"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-lime-500 focus:border-lime-500 block w-full p-2.5 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white dark:focus:ring-lime-500 dark:focus:border-lime-500 shadow-sm font-medium"
                    >
                        <option value="name_asc">A-Z (Nama)</option>
                        <option value="total_desc">Total Harga Tertinggi</option>
                        <option value="price_desc">Harga Satuan Termahal</option>
                        <option value="price_asc">Harga Satuan Termurah</option>
                        <option value="qty_desc">Kuantitas Terbanyak</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- CARDS GRID (Desktop & Mobile) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4 pb-10">
            <!-- Premium Empty State -->
            <div v-if="currentDisplayItems.length === 0" class="col-span-full flex flex-col items-center justify-center py-16 px-4 text-center bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 animate-fade-in">
                <div class="relative mb-6">
                    <div class="flex items-center justify-center w-20 h-20 rounded-full bg-gray-50 dark:bg-gray-700/50 shadow-inner">
                        <svg class="w-10 h-10 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ props.items.length === 0 ? 'Daftar Belanja Kosong' : 'Tidak Ada Produk' }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">{{ props.items.length === 0 ? 'Pilih produk dari katalog atau rekomendasi untuk mulai membuat RAB.' : 'Tidak ada produk yang cocok dengan kata kunci atau filter yang Anda pilih.' }}</p>
            </div>

            <div 
                v-for="item in currentDisplayItems" 
                :key="item.product_id || item.id" 
                class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-sm p-3 flex gap-3 relative overflow-hidden transition-all"
                :class="selectedItems.includes(item.product_id || item.id) ? 'ring-2 ring-lime-500 bg-lime-50/20' : ''"
                @click="toggleSelect(item.product_id || item.id)"
            >
                <!-- Checkbox Indicator -->
                <div class="absolute top-3 left-3 z-10" v-if="selectedItems.includes(item.product_id || item.id)">
                     <div class="w-5 h-5 bg-lime-500 rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                     </div>
                </div>

                <!-- Image -->
                <div class="flex-shrink-0 w-24 h-24 sm:w-28 sm:h-28 bg-gray-100 rounded-lg flex items-center justify-center relative overflow-hidden aspect-square">
                     <img v-if="item.image_url || item.image_path" :src="item.image_url || item.image_path" class="w-full h-full object-cover" alt="">
                     <div v-else class="flex items-center justify-center w-full h-full bg-gray-200 dark:bg-gray-700">
                          <span class="font-black text-3xl text-gray-400 dark:text-gray-500 tracking-widest">{{ item.name ? item.name.substring(0, 2).toUpperCase() : 'P' }}</span>
                     </div>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0 pl-1 py-1 flex flex-col justify-between">
                    <div class="flex justify-between items-start">
                        <h4 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white line-clamp-2 leading-tight mb-1" :title="item.name">{{ item.name }}</h4>
                        <!-- Delete Button -->
                        <button @click.stop="g(item)" class="text-gray-400 hover:text-red-600 transition p-1 rounded-full hover:bg-red-50 -mt-1 -mr-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                    
                    <div class="text-[10px] sm:text-xs text-gray-500 font-mono mb-2 flex flex-wrap items-center gap-1.5 mt-auto">
                        <span class="truncate font-bold max-w-[80px] sm:max-w-none">{{ item.brand?.name || item.brand || '-' }}</span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span :class="(item.current_stock || 0) <= (item.min_stock || 0) ? 'text-red-600 dark:text-red-400 font-black' : 'font-medium'">
                            Stok: <span class="text-xs sm:text-sm">{{ item.current_stock || item.stock || 0 }}</span> (Min: {{ item.min_stock || 0 }})
                        </span>
                    </div>

                    <div class="flex items-end justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-700">
                        <div class="flex flex-col gap-1" @click.stop>
                             <div class="text-xs sm:text-sm font-medium text-gray-500 dark:text-gray-400">
                                @ {{ formatRupiah(item.purchase_price) }}
                             </div>
                             <button @click="$emit('edit', item)" class="text-xs sm:text-sm font-black text-lime-700 dark:text-lime-300 bg-lime-100 dark:bg-lime-900/50 border border-lime-200 dark:border-lime-700 px-2 sm:px-3 py-1 rounded inline-flex items-center gap-1 mt-0.5 active:scale-95 transition-transform hover:bg-lime-200 dark:hover:bg-lime-800">
                                <span>qty: {{ item.quantity }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                             </button>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">Subtotal</span>
                             <div class="font-black text-sm sm:text-lg text-gray-900 dark:text-white">{{ formatRupiah((item.purchase_price || 0) * (item.quantity || 0)) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Load More Trigger Point -->
            <div ref="loadMoreTrigger" class="col-span-full h-8"></div>
        </div>
        
        <!-- Custom Delete Confirmation Modal -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showConfirmModal = false">
                <div class="bg-white dark:bg-gray-800 rounded-xl max-w-sm w-full p-6 shadow-xl border border-gray-100 dark:border-gray-700 transform transition-all">
                    <!-- Icon -->
                    <div class="mx-auto w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-center text-gray-900 dark:text-white mb-2">Hapus Item?</h3>
                    <p class="text-center text-gray-500 text-sm mb-6">Tindakan ini akan menghapus item dari daftar RAB. Lanjutkan?</p>
                    <div class="flex gap-3">
                        <button @click="showConfirmModal = false" class="flex-1 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition">Batal</button>
                        <button @click="executeDelete" class="flex-1 py-2.5 rounded-lg bg-red-600 text-white font-bold hover:bg-red-700 shadow-lg shadow-red-500/30 transition">Hapus</button>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
