<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    data: Object,
});
// console.log(props.data);
const emit = defineEmits(["imageClick", "delete", "restore", "forceDelete"]);

// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);

    if (diffInSeconds < 60) return "Baru saja";
    if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)} menit lalu`;
    if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)} jam lalu`;
    if (diffInSeconds < 604800) return `${Math.floor(diffInSeconds / 86400)} hari lalu`;
    
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric"
    }).format(date);
};

// --- LOGIC DSS ---
const isTrending = computed(() =>
    props.data.insights?.some((i) => i.type === "trend")
);
const isDeadStock = computed(() =>
    props.data.insights?.some((i) => i.type === "dead_stock")
);
const isMarginLow = computed(() =>
    props.data.insights?.some((i) => i.type === "margin_alert")
);

// Logic Stok Kritis (Dari Insight atau Manual Min Stock)
const isStockLow = computed(() => {
    const hasInsight = props.data.insights?.some(
        (i) => i.type === "restock" && i.severity === "critical"
    );
    return hasInsight || props.data.stock <= props.data.min_stock;
});

// Image Fallback Logic
const imageError = ref(false);
const handleImageError = () => {
    imageError.value = true;
};

const isTrashed = computed(() => props.data.deleted_at !== null);
</script>

<template>
    <div
        class="relative flex flex-row w-full overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm group dark:bg-gray-800 rounded-2xl dark:border-gray-700 hover:shadow-xl hover:-translate-y-0.5 hover:border-lime-500/30"
        :class="{ 'opacity-60 grayscale': isTrashed }"
    >
        <!-- 1. IMAGE SECTION (Left) -->
        <div
            class="relative w-36 shrink-0 bg-gray-50 dark:bg-gray-900 border-r border-gray-100 dark:border-gray-700 select-none cursor-pointer overflow-hidden"
            @click="
                emit('imageClick', {
                    path: data.image_url,
                    name: data.name,
                })
            "
        >
            <img
                v-if="!imageError"
                :src="data.image_url"
                loading="lazy"
                class="absolute inset-0 object-cover w-full h-full transition-transform duration-500 hover:scale-110"
                @error="handleImageError"
            />
            
            <!-- Default Image Placeholder -->
            <div
                 v-if="imageError || !data.image_url"
                class="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-300"
            >
                <svg class="w-10 h-10 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>

             <!-- Badges (Overlay Top Left) -->
             <div class="absolute top-2 left-2 flex flex-col gap-1 pointer-events-none z-10">
                <span
                    v-for="badge in data.active_badges"
                    :key="badge.type"
                     :class="[
                        'self-start px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-white rounded shadow-sm backdrop-blur-sm',
                        badge.class,
                    ]"
                >
                     {{ badge.label }}
                </span>
            </div>
        </div>

        <!-- 2. CONTENT SECTION (Structured Grid/Flex) -->
        <div class="flex flex-1 items-stretch divide-x divide-gray-50 dark:divide-gray-700">
            
            <!-- Col A: Identity (Name, Brand, Category) -->
            <div class="flex-1 p-4 flex flex-col justify-center min-w-[200px]">
                <div class="flex items-center gap-2 mb-1 text-[10px] font-bold uppercase tracking-wider text-gray-400">
                     <span class="text-lime-600 dark:text-lime-400 truncate max-w-[100px]">{{ data.brand?.name }}</span>
                     <span>•</span>
                     <span class="truncate max-w-[100px]">{{ data.category?.name }}</span>
                </div>
                <Link
                    :href="route('products.show', data.slug)"
                    class="text-base font-bold leading-tight text-gray-900 dark:text-gray-100 hover:text-lime-600 dark:hover:text-lime-400 transition-colors line-clamp-2"
                >
                    {{ data.name }}
                </Link>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                     <span class="text-[10px] bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded text-gray-500 dark:text-gray-400 font-mono border border-gray-200 dark:border-gray-600">
                        {{ data.code }}
                     </span>
                     <span v-if="data.supplier" class="text-[10px] text-gray-500 dark:text-gray-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        {{ data.supplier?.name }}
                     </span>
                     <span class="text-[10px] text-gray-400 italic">
                        Updated {{ formatDate(data.updated_at) }}
                     </span>
                </div>
            </div>

            <!-- Col B: Inventory (Stock, Unit) -->
            <div class="w-32 p-4 flex flex-col justify-center items-center bg-gray-50/50 dark:bg-gray-800/50 transition-colors hover:bg-lime-50/30">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Stok</span>
                <div class="text-center">
                    <span 
                        class="text-lg font-bold block leading-none"
                        :class="isStockLow ? 'text-red-500' : 'text-gray-700 dark:text-gray-200'"
                    >
                        {{ data.stock }}
                    </span>
                    <span class="text-[10px] text-gray-400 uppercase font-medium">{{ data.unit?.name }}</span>
                </div>
            </div>

            <!-- Col C: Financials (HPP, Harga, Margin) -->
            <div class="w-48 p-4 flex flex-col justify-center gap-2">
                 <!-- Selling Price -->
                 <div>
                    <span class="text-[9px] block text-gray-400 uppercase font-bold">Harga Jual</span>
                    <span class="text-base font-black text-gray-900 dark:text-white">
                        {{ formatRupiah(data.selling_price) }}
                    </span>
                 </div>
                 
                 <!-- Modal / Margin -->
                 <div class="flex items-center gap-3">
                     <div>
                        <span class="text-[9px] block text-gray-400 uppercase font-bold">Modal</span>
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                            {{ formatRupiah(data.purchase_price) }}
                        </span>
                     </div>
                     <div class="text-right flex-1">
                        <span class="text-[9px] block text-gray-400 uppercase font-bold">Margin</span>
                         <span 
                            class="text-xs font-bold"
                            :class="data.is_margin_low ? 'text-red-500' : 'text-green-600'"
                        >
                            +{{ data.current_margin["percent"] }}%
                        </span>
                     </div>
                 </div>
            </div>

            <!-- Col D: Actions -->
            <div class="w-auto p-4 flex items-center justify-center gap-1 pr-6">
                <!-- If Normal View -->
                 <template v-if="!isTrashed">
                    <Link
                        :href="route('products.edit', data.slug)"
                        class="p-2 text-gray-500 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-lime-600"
                        title="Edit Produk"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    </Link>
                    <button
                        @click="emit('delete', data)"
                        class="p-2 text-gray-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 hover:text-red-600"
                        title="Hapus Produk"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                 </template>

                 <!-- If Trash View -->
                 <template v-else>
                     <button
                        @click="emit('restore', data)"
                        class="p-2 text-lime-600 transition-colors rounded-lg hover:bg-lime-50"
                        title="Pulihkan"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                    <button
                        @click="emit('forceDelete', data)"
                        class="p-2 text-red-600 transition-colors rounded-lg hover:bg-red-50"
                        title="Hapus Permanen"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                 </template>
            </div>
        </div>
    </div>
</template>
