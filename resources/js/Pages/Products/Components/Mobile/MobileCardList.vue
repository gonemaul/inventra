<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    data: Object,
});

const emit = defineEmits(["click", "imageClick"]);

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const isStockLow = computed(() => {
    return props.data.stock <= props.data.min_stock;
});

// Image Fallback Logic
const imageError = ref(false);
const handleImageError = () => {
    imageError.value = true;
};
</script>

<template>
    <div
        class="flex w-full h-28 overflow-hidden bg-white border border-gray-100 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700 active:scale-[0.98] transition-all"
        :class="{ 'ring-1 ring-red-500/50': data.stock <= 0 }"
        @click="emit('click', data)"
    >
        <!-- Image Section (Fixed Width) -->
        <div
            class="relative w-28 h-full shrink-0 bg-gray-50 dark:bg-gray-900"
            @click.stop="
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
                class="object-cover w-full h-full"
                @error="handleImageError"
            />
            
            <!-- Default Image Placeholder (Shows if error or loading) -->
            <div
                v-if="imageError || !data.image_url"
                class="absolute inset-0 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-800 text-gray-400"
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

            <!-- Badges Overlay -->
             <div class="absolute top-1 left-1 flex flex-col gap-0.5 z-10 w-full pr-1">
                <span
                    v-if="data.stock <= 0"
                    class="px-1.5 py-0.5 text-[8px] font-bold text-white uppercase bg-red-600/90 rounded shadow-sm self-start"
                >
                    Habis
                </span>
                <div class="flex flex-wrap gap-0.5">
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
            </div>
        </div>

        <!-- Content Section -->
        <div class="flex flex-col flex-1 p-3 justify-between min-w-0 relative">
            <!-- Top: Brand & Name -->
            <div>
                <div class="flex items-center gap-1.5 mb-1 text-[9px]">
                    <span
                        class="font-bold uppercase tracking-wider text-lime-600 dark:text-lime-400 truncate max-w-[80px]"
                    >
                        {{ data.brand?.name }}
                    </span>
                    <span class="text-gray-300">•</span>
                     <span
                        class="font-medium text-gray-400 truncate max-w-[80px]"
                    >
                        {{ data.category?.name }}
                    </span>
                </div>
                <h3
                    class="text-sm font-bold leading-snug text-gray-800 dark:text-gray-100 line-clamp-2 mb-1"
                >
                    {{ data.name }}
                </h3>
                 <div class="flex items-center gap-2 text-[9px] text-gray-400">
                    <span class="bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded font-mono">{{ data.code }}</span>
                    <span v-if="data.supplier" class="truncate max-w-[100px] hidden xs:block">
                        <i class="mr-0.5 fa-solid fa-truck-field"></i>{{ data.supplier?.name }}
                    </span>
                </div>
            </div>

            <!-- Bottom: Price & Details -->
            <div class="flex items-end justify-between mt-2">
                <div>
                     <!-- Margin Alert or Sales Info -->
                     <div class="text-[9px] mb-0.5 flex gap-2">
                        <span v-if="data.is_margin_low" class="text-red-500 font-bold">
                            Margin Rendah
                        </span>
                        <span v-if="data.insights?.some(i => i.type === 'trend')" class="text-purple-600 font-bold flex items-center gap-0.5">
                            🔥 Laris manis
                        </span>
                     </div>
                    <div class="text-sm font-bold text-lime-600 mb-2 dark:text-lime-400">
                        {{ formatRupiah(data.selling_price) }}
                    </div>
                </div>

                <!-- Stock Info -->
                <div class="flex flex-col items-end text-[10px] text-gray-500 font-medium bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded border border-gray-100 dark:border-gray-700">
                     <span>Stok: <strong :class="isStockLow ? 'text-red-500' : 'text-gray-700 dark:text-gray-200'">{{ data.stock }}</strong></span>
                     <span class="text-[8px] text-gray-400 uppercase">{{ data.unit?.name }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
