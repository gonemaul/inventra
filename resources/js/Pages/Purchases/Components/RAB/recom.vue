<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";

const props = defineProps({
    show: Boolean,
    supplierId: [Number, String],
    cartItems: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "add-items"]);

// --- STATE ---
const recommendations = ref([]);
const loading = ref(false);
const selectedItems = ref([]);

// --- HELPER ---
const formatRupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);

// Cek Qty yang sudah ada di RAB
const getOrderedQty = (productId) => {
    const item = props.cartItems.find((i) => i.product_id === productId);
    return item ? item.quantity : 0;
};

// Cek apakah item sudah ada di RAB (untuk styling)
const isOrdered = (productId) => {
    return props.cartItems.some((i) => i.product_id === productId);
};

// --- FETCH DATA ---
async function fetchRecommendations() {
    if (!props.supplierId) return;

    loading.value = true;
    try {
        const res = await axios.get(
            route("purchases.recommendations", props.supplierId)
        );
        recommendations.value = res.data;

        // Auto-Select Logic:
        // Pilih yang Critical DAN Belum dipesan
        selectedItems.value = res.data
            .filter((i) => i.is_critical && !isOrdered(i.product_id))
            .map((i) => i.product_id);
    } catch (e) {
        console.error("Gagal fetch rekomendasi:", e);
        recommendations.value = [];
        selectedItems.value = [];
    } finally {
        loading.value = false;
    }
}

// --- WATCHERS ---
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            fetchRecommendations();
            document.body.style.overflow = "hidden"; // Lock scroll body
        } else {
            document.body.style.overflow = ""; // Unlock
            setTimeout(() => {
                recommendations.value = [];
                selectedItems.value = [];
            }, 300);
        }
    }
);

// --- COMPUTED ---
const allChecked = computed({
    get() {
        return (
            recommendations.value.length > 0 &&
            selectedItems.value.length === recommendations.value.filter(i => !isOrdered(i.product_id)).length
        );
    },
    set(value) {
        // Hanya centang yang belum dipesan
        selectedItems.value = value
            ? recommendations.value.filter(i => !isOrdered(i.product_id)).map((i) => i.product_id)
            : [];
    },
});

const criticalCount = computed(() => recommendations.value.filter(i => i.is_critical).length);
const selectedCount = computed(() => selectedItems.value.length);

const totalSelectedCost = computed(() => {
    return recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .reduce((sum, item) => sum + item.purchase_price * item.quantity, 0);
});

// --- ACTIONS ---
const toggleSelection = (id) => {
    // Jika sudah dipesan, mungkin disabled? Atau boleh double?
    // User bilang "sedikit digelapkan", asumsi boleh dipilih lagi untuk nambah qty? 
    // Atau hanya indikator? "bisa dikasih indikator qty dipesan" -> Biasanya warning.
    // Kita allow tapi visual beda.
    const index = selectedItems.value.indexOf(id);
    if (index === -1) selectedItems.value.push(id);
    else selectedItems.value.splice(index, 1);
};

const selectCriticalOnly = () => {
    selectedItems.value = recommendations.value
        .filter((i) => i.is_critical && !isOrdered(i.product_id))
        .map((i) => i.product_id);
};

function handleAdd() {
    const itemsToAdd = recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .map((item) => ({
            product_id: item.product_id,
            code: item.code,
            name: item.name,
            unit: item.unit, // unit string dari backend
            size: item.size_name, // Backend kirim size_name
            brand: item.brand,
            category: item.category,
            purchase_price: item.purchase_price,
            quantity: item.quantity,
            subtotal: item.purchase_price * item.quantity,
            current_stock: item.current_stock,
            min_stock: item.min_stock, // Ensure min_stock passed
            image_url: item.image_url,
            image_path: item.image_path,
        }));

    if (itemsToAdd.length === 0) return;
    emit("add-items", itemsToAdd);
    emit("close");
}
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform translate-y-10 opacity-0"
        enter-to-class="transform translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100"
        leave-to-class="transform translate-y-10 opacity-0"
    >
        <div v-if="show" class="fixed inset-0 z-50 flex flex-col bg-gray-50 dark:bg-gray-900">
            
            <!-- HEADER (Sticky Top) -->
            <div class="flex-none px-4 py-3 bg-white border-b shadow-sm dark:bg-gray-800 dark:border-gray-700 z-20">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="$emit('close')" class="p-2 -ml-2 text-gray-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </button>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                Rekomendasi Restock
                                <span class="px-2.5 py-0.5 rounded-full bg-lime-100 text-lime-700 text-xs font-extra-bold" v-if="recommendations.length > 0">{{ recommendations.length }}</span>
                            </h1>
                            <p class="text-xs text-gray-500 hidden sm:block">Analisis stok menipis berdasarkan data inventory.</p>
                        </div>
                    </div>
                    
                    <!-- Quick Actions (Desktop) -->
                    <div class="hidden sm:flex items-center gap-3">
                         <button 
                            @click="selectCriticalOnly"
                            class="px-3 py-1.5 rounded-lg border border-red-200 bg-red-50 text-red-600 text-xs font-bold hover:bg-red-100 transition flex items-center gap-1.5"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                            Pilih Kritis ({{ criticalCount }})
                        </button>
                    </div>
                </div>
            </div>

            <!-- FILTERS BAR (Sticky below Header on Mobile) -->
            <div class="flex-none bg-white/80 backdrop-blur border-b dark:bg-gray-800/80 dark:border-gray-700 z-10 sticky top-0 sm:hidden">
                 <div class="px-4 py-2 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-200">
                         <input type="checkbox" v-model="allChecked" class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                         Pilih Semua
                    </label>
                    <button 
                        @click="selectCriticalOnly"
                        class="text-xs font-bold text-red-600 bg-red-50 px-2 py-1 rounded border border-red-100"
                    >
                        Kritis ({{ criticalCount }})
                    </button>
                 </div>
            </div>

            <!-- MAIN CONTENT (Scrollable) -->
            <div class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-4 custom-scroll">
                <div class="max-w-7xl mx-auto">
                    
                    <!-- Loading -->
                    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                         <div class="animate-spin rounded-full h-12 w-12 border-4 border-lime-500 border-t-transparent mb-4"></div>
                         <p class="text-gray-500 font-medium">Menganalisa data stok...</p>
                    </div>

                    <!-- Empty -->
                    <div v-else-if="recommendations.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-20 h-20 bg-lime-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-10 h-10 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Stok Aman!</h3>
                        <p class="text-gray-500 mt-2">Semua stok produk aman, tidak ada rekomendasi restock.</p>
                        <button @click="$emit('close')" class="mt-6 text-lime-600 font-bold hover:underline">Kembali</button>
                    </div>

                    <!-- List Grid -->
                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 pb-20">
                         <!-- Select All (Desktop Only) -->
                         <div class="col-span-full hidden sm:flex justify-end mb-2">
                            <label class="flex items-center gap-2 text-sm font-bold text-gray-600 cursor-pointer hover:bg-white px-3 py-1 rounded transition">
                                <input type="checkbox" v-model="allChecked" class="w-4 h-4 rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                                Pilih Semua Data
                            </label>
                         </div>

                         <div 
                            v-for="item in recommendations" 
                            :key="item.product_id"
                            @click="toggleSelection(item.product_id)"
                            class="relative group bg-white dark:bg-gray-800 rounded-xl overflow-hidden border transition-all cursor-pointer hover:shadow-lg select-none"
                            :class="[
                                selectedItems.includes(item.product_id) 
                                    ? 'border-lime-500 ring-1 ring-lime-500 bg-lime-50/5' 
                                    : 'border-gray-200 dark:border-gray-700 hover:border-lime-300',
                                isOrdered(item.product_id) ? 'opacity-70 bg-gray-50 dark:bg-gray-900' : ''
                            ]"
                        >   
                            <!-- Ordered Badge (Moved to Bottom Right) -->
                            <div v-if="isOrdered(item.product_id)" class="absolute bottom-0 right-0 z-10 bg-blue-500 text-white text-[10px] font-bold px-2 py-1 rounded-tl-lg shadow-sm">
                                Sudah Dipesan: {{ getOrderedQty(item.product_id) }}
                            </div>

                            <div class="flex p-3 gap-3">
                                <!-- Checkbox Column -->
                                <div class="pt-1">
                                    <div class="w-5 h-5 rounded border flex items-center justify-center transition-colors shadow-sm"
                                        :class="selectedItems.includes(item.product_id) ? 'bg-lime-500 border-lime-500' : 'bg-white border-gray-300'"
                                    >
                                        <svg v-if="selectedItems.includes(item.product_id)" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </div>

                                <!-- Image -->
                                <div class="w-24 h-24 bg-gray-100 rounded-lg flex-shrink-0 flex items-center justify-center overflow-hidden border border-gray-100 relative">
                                    <!-- Fallback Icon (Background) -->
                                    <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                                         <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Actual Image -->
                                    <img 
                                        v-if="item.image_url || item.image_path"
                                        loading="lazy"
                                        :src="item.image_url || item.image_path" 
                                        class="absolute inset-0 w-full h-full object-contain p-1 mix-blend-multiply z-10 transition-opacity duration-300" 
                                        alt="img"
                                        onload="this.style.opacity='1'"
                                        onerror="this.style.display='none'"
                                    >
                                </div>

                                <!-- Content -->
                                <div class="flex-1 min-w-0 flex flex-col justify-between">
                                    <div>
                                        <div class="flex justify-between items-start gap-2">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white leading-tight line-clamp-2" :title="item.name">{{ item.name }}</h4>
                                             <span v-if="item.is_critical" class="px-1.5 py-0.5 rounded text-[9px] font-black bg-red-100 text-red-600 border border-red-200 tracking-wide">KRITIS</span>
                                        </div>
                                        <div class="text-[10px] text-gray-500 font-mono mt-0.5 flex flex-wrap gap-1">
                                            <span>{{ item.brand }}</span>
                                            <span>•</span>
                                            <span>{{ item.category }}</span>
                                            <span v-if="item.size !== '-'" class="bg-gray-100 px-1 rounded">{{ item.size }}</span>
                                        </div>
                                    </div>

                                    <!-- Sales Stats -->
                                    <div class="mt-2 text-[10px] text-gray-500 bg-gray-50 dark:bg-gray-700/50 p-1.5 rounded border border-dashed border-gray-200 flex justify-between">
                                        <span>Terjual (90H):</span>
                                        <strong class="text-gray-800 dark:text-gray-200">{{ item.sold_90d }} {{ item.unit }}</strong>
                                    </div>

                                    <div class="flex items-end justify-between mt-2">
                                        <div class="flex flex-col gap-0.5">
                                            <div class="text-[10px] text-gray-400 flex items-center gap-1">
                                                <span>Sisa: <strong class="text-gray-700">{{ item.current_stock }}</strong></span>
                                                <span class="text-gray-300">|</span>
                                                <span>Min: <strong class="text-gray-700">{{ item.min_stock }}</strong></span>
                                            </div>
                                            <div class="text-[10px] text-gray-500">
                                                @ {{ formatRupiah(item.purchase_price) }}
                                            </div>
                                        </div>
                                         <div class="flex flex-col text-right">
                                            <span class="text-[10px] text-gray-400">Nilai Order</span>
                                            <span class="text-sm font-bold text-lime-600">{{ formatRupiah(item.purchase_price * item.quantity) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Insight Strip -->
                            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-100 dark:border-gray-700 text-xs flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-600 dark:text-gray-300">Saran: {{ item.quantity }} {{ item.unit }}</span>
                                    <span class="w-px h-3 bg-gray-300"></span>
                                    <span class="truncate text-gray-500 italic">{{ item.reason || 'Restock Reguler' }}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER (Sticky Bottom) -->
            <div class="flex-none bg-white dark:bg-gray-800 border-t dark:border-gray-700 p-4 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-30">
                <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                    <div class="hidden sm:block">
                        <span class="text-sm text-gray-500">Terpilih: <strong class="text-gray-900 dark:text-white">{{ selectedCount }} Item</strong></span>
                    </div>

                    <div class="flex-1 flex justify-end items-center gap-4">
                         <div class="text-right">
                             <span class="block text-[10px] uppercase tracking-wider text-gray-400">Estimasi Total</span>
                             <span class="block text-lg font-black text-lime-600 leading-none">{{ formatRupiah(totalSelectedCost) }}</span>
                         </div>
                         <button 
                            @click="handleAdd"
                            :disabled="selectedCount === 0"
                            class="px-6 py-3 bg-lime-500 hover:bg-lime-600 active:bg-lime-700 text-white font-bold rounded-xl shadow-lg shadow-lime-500/30 transition-all disabled:opacity-50 disabled:grayscale disabled:shadow-none flex items-center gap-2 min-w-[160px] justify-center"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span class="hidden sm:inline">Masukkan ke RAB</span>
                            <span class="sm:hidden">Masuk RAB ({{ selectedCount }})</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
