<script setup>
import { computed } from "vue";

const props = defineProps({
    item: Object,
    isSelected: Boolean,
    isInCart: Boolean,
    cartQty: Number,
    searchTerm: String,
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
const isSedangTren = computed(() => (props.item.sold_last_30_days || 0) >= 15);
const isFastMoving = computed(() => !isSedangTren.value && (props.item.sold_last_30_days || 0) >= 5);
const isDeadStock = computed(() => (props.item.sold_last_90_days || 0) <= 2 && props.item.stock > 10);

const conditionBadge = computed(() => {
    if (isSedangTren.value) return { text: 'Sedang Tren', color: 'bg-red-100 text-red-600 border-red-200 dark:bg-red-900/40 dark:text-red-400 dark:border-red-800', icon: '🚀' };
    if (isFastMoving.value) return { text: 'Laris', color: 'bg-blue-100 text-blue-600 border-blue-200 dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-800', icon: '⚡' };
    if (isDeadStock.value) return { text: 'Stok Mati', color: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700', icon: '📦' };
    return null;
});

const categoryColor = (name) => {
    if (!name) return 'bg-gray-400';
    const num = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    const colors = ['bg-indigo-500', 'bg-emerald-500', 'bg-rose-500', 'bg-amber-500', 'bg-purple-500', 'bg-cyan-500'];
    return colors[num % colors.length];
};

const highlight = (text) => {
    if (!props.searchTerm || !text) return text;
    const regex = new RegExp(`(${props.searchTerm})`, "gi");
    return text.replace(regex, '<mark class="bg-yellow-200 text-gray-900 rounded-px px-0.5">$1</mark>');
};
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
            class="absolute top-0 left-0 z-10 bg-lime-500 text-white text-[10px] font-bold px-2 py-1 rounded-tl-lg shadow-sm"
        >
            {{ cartQty }} di List
        </span>

        <!-- Image Section -->
        <div class="relative w-full overflow-hidden bg-gray-100 aspect-square dark:bg-gray-700 border-b border-gray-100 dark:border-gray-700">
            <!-- Fallback Initials -->
            <div class="absolute inset-0 flex items-center justify-center bg-gray-200 dark:bg-gray-700">
                <span class="font-black text-4xl text-gray-400 dark:text-gray-500 tracking-widest">{{ item.name ? item.name.substring(0, 2).toUpperCase() : 'P' }}</span>
            </div>
            
            <img
                v-if="item.image_url || item.image_path"
                :src="item.image_url || item.image_path"
                loading="lazy"
                class="absolute inset-0 z-10 object-cover w-full h-full transition-transform duration-500 group-hover:scale-110 transition-opacity"
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
            <div class="text-[10px] sm:text-xs text-gray-500 font-mono flex flex-wrap items-center gap-1.5 leading-none">
                <span class="font-bold text-gray-600 dark:text-gray-300">{{ item.brand?.name || '-' }}</span>
                <span>•</span>
                <span class="px-1.5 py-0.5 rounded text-white font-bold" :class="categoryColor(item.category?.name)">{{ item.category?.name || '-' }}</span>
                <span v-if="item.size" class="bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">{{ item.size.name }}</span>
            </div>

            <!-- Name & Price -->
            <div>
                <h3 
                    class="text-sm sm:text-base font-bold leading-tight line-clamp-2 min-h-[2.5em] mb-1" 
                    :title="item.name"
                    v-html="highlight(item.name)"
                ></h3>
                <div class="flex items-center justify-between">
                     <span class="text-sm sm:text-base font-black text-lime-600 dark:text-lime-400">{{ formatRupiah(item.purchase_price) }}</span>
                     <!-- <span class="text-[10px] sm:text-xs text-gray-400 font-mono">#{{ item.code }}</span> -->
                </div>
            </div>
            
            <!-- Sales Stats (30 Days Priority) -->
            <div v-if="item.sold_last_30_days > 0" class="text-[10px] text-gray-500 bg-gray-50 dark:bg-gray-700/50 p-1.5 rounded border border-dashed border-gray-200 flex justify-between items-center group-hover:bg-lime-50 dark:group-hover:bg-lime-900/20 transition-colors">
                <span class="flex items-center gap-1 font-medium">
                    <svg class="w-3 h-3 text-lime-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    Tren (30H):
                </span>
                <strong class="text-gray-800 dark:text-gray-200 group-hover:text-lime-700 dark:group-hover:text-lime-400">{{ item.sold_last_30_days }} {{ item.unit?.name }}</strong>
            </div>

            <!-- Separator -->
            <!-- Stock Info -->
            <div class="flex items-center justify-between text-xs sm:text-sm mt-auto pt-2 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-col leading-none gap-0.5">
                    <span class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Sisa</span>
                    <span class="font-black text-sm sm:text-base" :class="item.stock <= item.min_stock ? 'text-red-500' : 'text-gray-800 dark:text-gray-200'">{{ item.stock }}</span>
                </div>
                <div class="flex flex-col leading-none text-right gap-0.5">
                    <span class="text-[10px] sm:text-xs font-medium text-gray-500 uppercase">Min Stok</span>
                    <span class="font-bold text-sm sm:text-base text-gray-600 dark:text-gray-400">{{ item.min_stock }}</span>
                </div>
            </div>

            <!-- Restock Suggestion (Only if exists) -->
            <div v-if="restockSuggestion > 0" class="mt-2 text-[10px]">
                <div class="flex items-center justify-between p-2 bg-gradient-to-r from-indigo-50 to-blue-50 text-indigo-700 rounded-lg border border-indigo-100/50 shadow-inner group-hover:from-indigo-100 group-hover:to-blue-100 transition-colors">
                    <div class="flex items-center gap-1.5">
                        <span class="text-lg">🤖</span>
                        <span class="font-bold tracking-wide uppercase text-[9px] opacity-80">Saran Restok</span>
                    </div>
                    <div class="font-black text-sm">{{ restockSuggestion }} {{ item.unit?.name || '' }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
