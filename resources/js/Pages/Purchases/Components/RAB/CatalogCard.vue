<script setup>
import { computed } from "vue";

const props = defineProps({
    item: Object,
    isSelected: Boolean, // Penanda jika item ini sedang diedit/dipilih di form
    isInCart: Boolean, // Penanda jika item ini sudah ada di keranjang belanja
    cartQty: Number, // Jumlah qty yang sudah ada di keranjang (opsional)
});

const emit = defineEmits(["select"]);

const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};

// Hitung rekomendasi restok dari data insights
const restockSuggestion = computed(() => {
    const insight = props.item.insights?.find((i) => i.type === "restock");
    return insight?.payload?.suggested_qty || 0;
});
</script>

<template>
    <div
        @click="$emit('select', item)"
        class="relative flex flex-col overflow-hidden transition-all duration-200 border cursor-pointer rounded-xl group hover:shadow-lg"
        :class="[
            isSelected
                ? 'ring-2 ring-lime-500 border-lime-500 bg-lime-50 dark:bg-lime-900/20 z-10'
                : item.stock <= 0
                ? 'ring-1 ring-red-400 bg-red-100 border-red-300 dark:bg-red-900/10 dark:border-red-900/50 hover:border-red-500 dark:hover:border-red-400'
                : 'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:border-lime-400 dark:hover:border-lime-500 hover:shadow-md',
        ]"
    >
        <span
            v-if="isInCart"
            class="absolute top-0 right-0 z-20 bg-lime-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg shadow-sm"
        >
            {{ cartQty }} di List
        </span>

        <span
            v-if="restockSuggestion > 0"
            class="absolute top-0 left-0 z-20 bg-orange-500 text-white text-[10px] font-bold px-2 py-1 rounded-br-lg shadow-sm animate-pulse"
        >
            Restok: {{ restockSuggestion }}
        </span>

        <div
            class="relative w-full overflow-hidden bg-gray-100 aspect-[4/3] dark:bg-gray-700"
        >
            <img
                v-if="item.image_url"
                :src="item.image_url"
                loading="lazy"
                class="absolute inset-0 z-10 object-cover w-full h-full transition-transform duration-500 group-hover:scale-110"
                alt="Product Image"
            />

            <div
                v-else
                class="absolute inset-0 z-0 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500"
            >
                <svg
                    class="w-8 h-8 mb-1 opacity-50"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                    ></path>
                </svg>
                <span class="text-[10px] font-medium">No Image</span>
            </div>

            <div
                class="absolute bottom-0 left-0 right-0 z-10 p-2 pt-6 bg-gradient-to-t from-black/70 to-transparent"
            >
                <div class="flex items-end justify-between text-white">
                    <span class="text-[10px] font-light">Sisa Stok</span>
                    <span :class="['text-xs font-bold']">{{
                        item.stock > 0
                            ? item.stock + " " + item.unit?.name || "Unit"
                            : "Habis"
                    }}</span>
                </div>
            </div>
        </div>

        <div class="flex flex-col flex-1 p-3">
            <div class="flex items-center gap-2 mb-1">
                <span
                    class="text-[10px] font-mono font-semibold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300"
                >
                    {{ item.code }}
                </span>
                <span
                    class="text-[10px] text-gray-400 truncate dark:text-gray-500"
                >
                    {{ item.category?.name || "Umum" }}
                </span>
            </div>

            <h3
                class="text-xs font-bold text-gray-800 dark:text-gray-100 leading-tight mb-2 line-clamp-2 min-h-[2.5em] group-hover:text-lime-600 dark:group-hover:text-lime-400 transition-colors"
            >
                {{ item.name }}
            </h3>

            <div
                class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100 border-dashed dark:border-gray-700"
            >
                <div class="flex flex-col">
                    <span class="text-[10px] text-gray-400 dark:text-gray-500"
                        >Harga Beli</span
                    >
                    <span
                        class="text-sm font-bold text-blue-600 dark:text-blue-400"
                    >
                        {{ formatRupiah(item.purchase_price) }}
                    </span>
                </div>

                <button
                    class="p-1.5 rounded-full transition-colors duration-200"
                    :class="[
                        isSelected
                            ? 'bg-lime-500 text-white shadow-md'
                            : 'bg-gray-100 text-gray-400 hover:bg-lime-500 hover:text-white dark:bg-gray-700 dark:text-gray-500 dark:hover:bg-lime-600 dark:hover:text-white',
                    ]"
                >
                    <svg
                        v-if="isSelected"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
