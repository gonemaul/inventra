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
        :class="data.stock == 0 ? 'animate-pulse shadow-lg shadow-red-400' : ''"
        class="flex flex-col h-full overflow-hidden transition-transform duration-200 bg-white border border-gray-100 rounded-lg shadow-sm cursor-pointer dark:bg-gray-800 dark:border-gray-700 active:scale-95"
    >
        <div class="relative w-full aspect-square bg-gray-50 dark:bg-gray-900">
            <img
                @click="
                    emit('imageClick', {
                        path: data.image_url,
                        name: data.name,
                    })
                "
                :src="data.image_url"
                loading="lazy"
                decoding="async"
                class="absolute inset-0 z-10 object-cover w-full h-full opacity-0"
                onerror="this.style.display='none'"
                onload="this.classList.remove('opacity-0')"
            />
            <div
                class="absolute inset-0 z-0 flex flex-col items-center justify-center w-full h-full text-gray-300"
            >
                <svg
                    class="w-8 h-8 mb-1"
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
                <span class="text-[9px] font-bold">No Image</span>
            </div>

            <span
                v-if="data.stock > 0"
                class="bg-red-500 top-1 left-1 absolute z-20 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm"
            >
                Stok {{ data.stock || 0 }}
            </span>
            <div
                v-if="data.stock <= 0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/60 backdrop-blur-[1px]"
            >
                <span
                    class="px-2 py-1 text-xs font-bold text-white transform border-2 border-white rounded bg-red-600/90 -rotate-12"
                    >KOSONG</span
                >
            </div>
            <div
                class="absolute bottom-0 left-0 right-0 z-20 p-1 pt-4 bg-gradient-to-t from-black/60 to-transparent"
            >
                <p class="text-[9px] text-white truncate font-medium">
                    {{ data.supplier?.name }}
                </p>
            </div>
        </div>

        <div
            @click="emit('click', data)"
            class="flex flex-col flex-1 gap-1 p-2"
        >
            <div class="text-[9px] text-gray-400 truncate uppercase">
                {{ data.brand?.name || "-" }}
            </div>
            <h3
                class="text-xs font-medium leading-snug text-gray-800 dark:text-gray-100 line-clamp-2"
            >
                {{ data.name }}
            </h3>

            <div class="pt-1 mt-auto">
                <div class="text-sm font-bold text-lime-600 dark:text-lime-400">
                    {{ formatRupiah(data.selling_price) }}
                </div>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-[9px] text-gray-400">
                        {{ data.size?.name || "-" }} |
                        {{ data.stock }}
                        {{ data.unit?.name || "-" }}
                    </span>
                    <span
                        class="text-[9px] font-bold px-1 rounded"
                        :class="
                            data.is_margin_low
                                ? 'bg-red-100 text-red-700'
                                : 'bg-green-100 text-green-700'
                        "
                    >
                        {{ data.current_margin["percent"] }}%
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
