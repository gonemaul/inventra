<script setup>
import { computed } from "vue";

const props = defineProps({
    item: Object,
    isSelected: Boolean,
    isInCart: Boolean,
    cartQty: Number,
});

const emit = defineEmits(["select"]);

const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};

// Hitung rekomendasi restok
const restockInsight = computed(() => {
    return props.item.insights?.find((i) => i.type === "restock");
});

const restockSuggestion = computed(() => {
    return restockInsight.value?.payload?.suggested_qty || 0;
});

// Logic Badges (Frontend Calculation based on stats provided by Backend)
const isBestSeller = computed(() => (props.item.sold_last_90_days || 0) >= 50); 
const isFastMoving = computed(() => !isBestSeller.value && (props.item.sold_last_90_days || 0) >= 20);
const isDeadStock = computed(() => (props.item.sold_last_90_days || 0) <= 2 && props.item.stock > 10);

const conditionBadge = computed(() => {
    if (isBestSeller.value) return { text: 'Best Seller', color: 'bg-orange-100 text-orange-600 border-orange-200', icon: '🔥' };
    if (isFastMoving.value) return { text: 'Laris', color: 'bg-blue-100 text-blue-600 border-blue-200', icon: '⚡' };
    if (isDeadStock.value) return { text: 'Stok Mati', color: 'bg-gray-100 text-gray-600 border-gray-200', icon: '📦' };
    return null;
});
</script>

<template>
    <div
        @click="$emit('select', item)"
        class="relative flex flex-col overflow-hidden transition-all duration-200 border cursor-pointer rounded-xl group hover:shadow-lg bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-lime-400 dark:hover:border-lime-500"
        :class="{ 'ring-2 ring-lime-500 border-lime-500 bg-lime-50 dark:bg-lime-900/20 z-10': isSelected }"
    >
        <!-- In Cart Badge (Bottom Right - like Recom) -->
        <span
            v-if="isInCart"
            class="absolute top-0 left-0 z-20 bg-lime-500 text-white text-[10px] font-bold px-2 py-1 rounded-tl-lg shadow-sm"
        >
            {{ cartQty }} di List
        </span>

        <!-- Image Section -->
        <div class="relative w-full overflow-hidden bg-gray-100 aspect-square dark:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
            <!-- Fallback Icon -->
            <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                <svg class="w-10 h-10 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            
            <img
                v-if="item.image_url || item.image_path"
                :src="item.image_url || item.image_path"
                loading="lazy"
                class="absolute inset-0 z-10 object-contain w-full h-full p-2 transition-transform duration-500 group-hover:scale-110 mix-blend-multiply transition-opacity"
                alt="Product"
                onload="this.style.opacity='1'"
                onerror="this.style.display='none'"
            />

            <!-- Status Badges (Top Right) -->
            <div class="absolute top-2 right-2 z-10 flex flex-col items-end gap-1">
                <span v-if="item.stock <= 0" class="px-2 py-0.5 text-[10px] font-black tracking-wide text-white bg-red-600 rounded shadow-sm">HABIS</span>
                <span v-else-if="item.stock <= item.min_stock" class="px-2 py-0.5 text-[10px] font-bold text-white bg-yellow-500 rounded shadow-sm">MENIPIS</span>
            </div>
            
            <!-- Condition Badge (Top Left) -->
            <div v-if="conditionBadge" class="absolute top-2 left-2 z-10">
                <span class="px-2 py-0.5 text-[10px] font-bold rounded shadow-sm border flex items-center gap-1" :class="conditionBadge.color">
                    <span>{{ conditionBadge.icon }}</span>
                    {{ conditionBadge.text }}
                </span>
            </div>
        </div>

        <!-- Content Section -->
        <div class="flex flex-col flex-1 p-3 gap-2">
            <!-- Meta Info -->
            <div class="text-[10px] text-gray-500 font-mono flex flex-wrap gap-1 leading-none">
                <span class="font-bold text-gray-600">{{ item.brand?.name || '-' }}</span>
                <span>•</span>
                <span>{{ item.category?.name || '-' }}</span>
                <span v-if="item.size" class="bg-gray-100 px-1 rounded">{{ item.size.name }}</span>
            </div>

            <!-- Name & Price -->
            <div>
                <h3 class="text-xs font-bold leading-tight line-clamp-2 min-h-[2.5em] mb-1" :title="item.name">
                    {{ item.name }}
                </h3>
                <div class="flex items-center justify-between">
                     <span class="text-xs font-bold text-lime-600">{{ formatRupiah(item.purchase_price) }}</span>
                     <span class="text-[10px] text-gray-400">#{{ item.code }}</span>
                </div>
            </div>
            
            <!-- Sales Stats (From Recom) -->
            <div v-if="item.sold_last_90_days > 0" class="text-[10px] text-gray-500 bg-gray-50 dark:bg-gray-700/50 p-1 rounded border border-dashed border-gray-200 flex justify-between">
                <span>Terjual (90H):</span>
                <strong class="text-gray-800 dark:text-gray-200">{{ item.sold_last_90_days }} {{ item.unit?.name }}</strong>
            </div>

            <!-- Separator -->
            <!-- Stock Info -->
            <div class="flex items-center justify-between text-[11px] mt-auto pt-2 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-col leading-none gap-0.5">
                    <span class="text-[9px] text-gray-400 uppercase">Sisa</span>
                    <span class="font-bold" :class="item.stock <= item.min_stock ? 'text-red-500' : 'text-gray-700'">{{ item.stock }}</span>
                </div>
                <div class="flex flex-col leading-none text-right gap-0.5">
                    <span class="text-[9px] text-gray-400 uppercase">Min</span>
                    <span class="font-bold text-gray-600">{{ item.min_stock }}</span>
                </div>
            </div>

            <!-- Restock Suggestion (Only if exists) -->
            <div v-if="restockSuggestion > 0" class="mt-2 text-[10px]">
                <div class="flex items-start gap-1 p-1.5 bg-blue-50 text-blue-700 rounded border border-blue-100">
                    <svg class="w-3 h-3 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="leading-tight">
                        <span class="font-bold">Saran: {{ restockSuggestion }} {{ item.unit?.name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
