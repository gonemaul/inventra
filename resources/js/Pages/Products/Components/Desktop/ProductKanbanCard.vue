<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    data: Object,
});

const emit = defineEmits(["click"]);

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Image Fallback
const imageError = ref(false);
const handleImageError = () => {
    imageError.value = true;
};

const isStockLow = computed(() => {
    return props.data.stock <= props.data.min_stock;
});
</script>

<template>
    <div
        class="group relative flex flex-col bg-white dark:bg-gray-800 p-2 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-lg hover:border-lime-500/50 transition-all cursor-pointer select-none active:scale-[0.98]"
        @click="emit('click')"
    >
        <div class="flex gap-2.5">
            <!-- Image (Fixed 64px) -->
            <div
                class="relative w-16 h-16 shrink-0 bg-gray-50 dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-100 dark:border-gray-700"
            >
                <img
                    v-if="!imageError"
                    :src="data.image_url"
                    loading="lazy"
                    class="object-cover w-full h-full transform transition-transform group-hover:scale-110"
                    @error="handleImageError"
                />
                 <!-- Placeholder -->
                <div
                    v-if="imageError || !data.image_url"
                    class="absolute inset-0 flex items-center justify-center text-gray-300 dark:text-gray-600 bg-gray-50 dark:bg-gray-800"
                >
                    <svg class="w-6 h-6 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                 <div>
                     <!-- Brand/Category -->
                     <div class="flex items-center gap-1.5 mb-0.5 text-[9px] text-gray-400">
                         <span class="font-bold uppercase tracking-wider text-lime-600 dark:text-lime-400 truncate max-w-[80px]">
                            {{ data.brand?.name }}
                         </span>
                     </div>
                     <h4 class="text-xs font-bold leading-tight text-gray-800 dark:text-gray-100 line-clamp-2 group-hover:text-lime-600 transition-colors">
                        {{ data.name }}
                     </h4>
                 </div>
                 
                 <div class="flex items-center justify-between mt-1">
                     <span class="text-[11px] font-bold text-gray-900 dark:text-gray-200">
                        {{ formatRupiah(data.selling_price) }}
                     </span>
                     <span class="text-[9px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-gray-500 dark:text-gray-400">
                        {{ data.stock }} {{ data.unit?.name }}
                     </span>
                 </div>
            </div>
        </div>

        <!-- Badges/Insights Row -->
        <div class="mt-2 flex flex-wrap gap-1.5 border-t border-gray-50 dark:border-gray-700/50 pt-1.5" v-if="data.active_badges?.length || isStockLow">
             <span
                v-if="isStockLow"
                class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-md flex items-center gap-0.5"
            >
                🚨 Stok Tipis
            </span>
             <span
                v-for="badge in data.active_badges"
                :key="badge.type"
                :class="[
                    'px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded-md border',
                    badge.class.includes('red') ? 'text-red-600 border-red-100 bg-red-50 dark:bg-red-900/20 dark:border-red-800' : 
                    badge.class.includes('purple') ? 'text-purple-600 border-purple-100 bg-purple-50 dark:bg-purple-900/20 dark:border-purple-800' :
                    'text-gray-600 border-gray-100 bg-gray-50'
                ]"
            >
                {{ badge.label }}
            </span>
        </div>
    </div>
</template>
