<script setup>
import { computed } from "vue";
const props = defineProps({
    data: Object,
});

const emit = defineEmits(["click", "imageClick"]);

const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};
const isStockLow = computed(() => {
    const hasInsight = props.data.insights?.some((i) => i.type === "restock");
    return hasInsight;
});
</script>

<template>
    <div
        class="relative flex flex-col h-full overflow-hidden transition-all duration-300 bg-white border border-gray-100 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700 active:scale-95 hover:shadow-md"
        :class="{ 'ring-2 ring-red-500/50': data.stock <= 0 }"
    >
        <!-- Image Container (1:1 Aspect Ratio) -->
        <div
            @click="
                emit('imageClick', {
                    path: data.image_url,
                    name: data.name,
                })
            "
            class="relative w-full bg-gray-50 dark:bg-gray-900 aspect-square group"
        >
            <!-- Placeholder / No Image -->
            <div
                class="absolute inset-0 flex flex-col items-center justify-center text-gray-300"
            >
                <svg
                    class="w-8 h-8 opacity-50"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                </svg>
            </div>

            <!-- Actual Image -->
            <img
                :src="data.image_url"
                loading="lazy"
                class="absolute inset-0 object-cover w-full h-full transition-opacity duration-300 opacity-0"
                onload="this.classList.remove('opacity-0')"
                onerror="this.style.display='none'"
            />

            <!-- Badges (Top Left) -->
            <div class="absolute top-1.5 left-1.5 flex flex-wrap gap-1 z-10">
                <span
                    v-for="badge in data.active_badges"
                    :key="badge.type"
                    :class="[
                        'px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wider text-white rounded shadow-sm',
                        badge.class,
                    ]"
                >
                    {{ badge.label }}
                </span>
            </div>

            <!-- Stock Overlay (If Empty) -->
            <div
                v-if="data.stock <= 0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-black/40 backdrop-blur-[1px]"
            >
                <span
                    class="px-2 py-0.5 text-[10px] font-bold text-white uppercase border border-white rounded transform -rotate-6 bg-red-600/90"
                >
                    Habis
                </span>
            </div>
            
            <!-- Supplier info (Subtle Gradient Bottom) -->
            <div
                class="absolute bottom-0 left-0 right-0 p-2 pt-6 bg-gradient-to-t from-black/50 to-transparent"
                v-if="data.supplier"
            >
                <p class="text-[9px] text-white/90 truncate font-medium drop-shadow-sm">
                    {{ data.supplier.name }}
                </p>
            </div>
        </div>

        <!-- Info Content -->
        <div
            @click="emit('click', data)"
            class="flex flex-col flex-1 p-2.5 space-y-1.5"
        >
            <!-- Meta Info (Brand / Category) -->
            <div class="flex items-center gap-1 text-[9px] font-semibold tracking-wide text-gray-400 uppercase">
                <span class="text-lime-600 dark:text-lime-400 truncate max-w-[45%]">
                     {{ data.brand?.name }}
                </span>
                <span class="text-gray-300">•</span>
                <span class="truncate text-gray-500 dark:text-gray-400 max-w-[45%]">
                    {{ data.category?.name }}
                </span>
            </div>

            <!-- Product Name -->
            <h3 class="text-xs font-semibold leading-relaxed text-gray-800 dark:text-gray-100 line-clamp-2 min-h-[2.25em]">
                {{ data.name }}
            </h3>

            <!-- Price & Stock Row -->
            <div class="flex items-end justify-between pt-1 mt-auto">
                <div class="flex flex-col">
                     <!-- Margin Alert (If any) -->
                    <span 
                        v-if="data.is_margin_low" 
                        class="text-[8px] text-red-500 font-bold mb-0.5"
                    >
                        Margin Rendah
                    </span>
                    <span class="text-sm font-bold text-lime-600 dark:text-lime-400">
                        {{ formatRupiah(data.selling_price) }}
                    </span>
                </div>

                <div class="flex flex-col items-end gap-0.5">
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 font-medium">
                        {{ data.stock }} {{ data.unit?.name || 'Unit' }}
                    </span>
                 </div>
            </div>
        </div>
    </div>
</template>
