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
                : 'bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700 hover:border-lime-400 dark:hover:border-lime-500 hover:shadow-md',
        ]"
    >
        <span
            v-if="isInCart"
            class="absolute top-0 right-0 z-20 bg-lime-500 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg shadow-sm"
        >
            {{ cartQty }} di List
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
                v-if="item.stock <= 0"
                class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/60 backdrop-blur-[1px]"
            >
                <span
                    class="px-2 py-1 text-xs font-bold text-white transform border-2 border-white rounded bg-red-600/90 -rotate-12"
                    >KOSONG</span
                >
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

        <!-- <div class="flex flex-col flex-1 p-3">
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
        </div> -->
        <div
            class="relative flex flex-col flex-1 p-3 overflow-hidden transition-all duration-200"
            :class="[
                // LOGIC WARNA KARTU:
                // Stok Kritis (<= 2): Background merah sangat muda, Border kiri Merah Tebal
                item.stock <= 2
                    ? 'bg-red-50/60 dark:bg-red-900/10 border-l-4 border-l-red-500'
                    : 'bg-white dark:bg-gray-800 border-l-4 border-l-gray-200 dark:border-l-gray-700 opacity-90 hover:opacity-100',
            ]"
        >
            <div class="flex items-center justify-between mb-1.5">
                <span
                    class="text-[9px] font-bold tracking-wider text-gray-400 uppercase truncate max-w-[80px]"
                    :title="item.brand?.name"
                >
                    {{ item.brand?.name || "NO BRAND" }}
                </span>
                <span
                    class="text-[9px] font-mono text-gray-400 truncate max-w-[60px]"
                    :title="item.code"
                >
                    #{{ item.code }}
                </span>
            </div>

            <h3
                class="text-xs font-bold leading-tight mb-2 line-clamp-2 min-h-[2.5em] transition-colors"
                :class="
                    item.stock <= 2
                        ? 'text-gray-800 dark:text-red-100'
                        : 'text-gray-700 dark:text-gray-200'
                "
                :title="item.name"
            >
                {{ item.name }}
            </h3>

            <div class="flex flex-col gap-2 mb-3">
                <div class="flex items-start">
                    <span
                        v-if="item.size || item.unit"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border"
                        :class="
                            item.stock <= 2
                                ? 'bg-white border-red-200 text-gray-600 dark:bg-gray-900 dark:border-red-900 dark:text-gray-300'
                                : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400'
                        "
                    >
                        <svg
                            class="w-3 h-3 mr-1 opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 10h16M4 14h16M4 18h16"
                            ></path>
                        </svg>

                        {{ item.size?.name || "-" }}
                        <span class="mx-1 opacity-40">/</span>
                        {{ item.unit?.name }}
                    </span>
                </div>

                <div class="flex flex-col gap-2 mb-3">
                    <div class="flex items-start">
                        <span
                            v-if="item.size || item.unit"
                            class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold border"
                            :class="
                                item.restock_qty > 0
                                    ? 'bg-white border-red-200 text-gray-600 dark:bg-gray-900 dark:border-red-900 dark:text-gray-300'
                                    : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400'
                            "
                        >
                            <svg
                                class="w-3 h-3 mr-1 opacity-50"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                ></path>
                            </svg>

                            {{ item.size?.name || "-" }}
                            <span class="mx-1 opacity-40">/</span>
                            {{ item.unit?.name }}
                        </span>
                    </div>

                    <div>
                        <div
                            v-if="restockSuggestion > 0"
                            class="flex items-center justify-between"
                        >
                            <div
                                class="flex items-center gap-1.5 text-red-600 dark:text-red-400"
                            >
                                <span class="relative flex w-2 h-2">
                                    <span
                                        class="absolute inline-flex w-full h-full bg-red-400 rounded-full opacity-75 animate-ping"
                                    ></span>
                                    <span
                                        class="relative inline-flex w-2 h-2 bg-red-500 rounded-full"
                                    ></span>
                                </span>
                                <span
                                    class="text-[10px] font-bold uppercase tracking-tight"
                                >
                                    Saran Order
                                </span>
                            </div>

                            <span
                                class="text-[10px] font-mono font-bold text-white bg-red-500 px-1.5 py-0.5 rounded shadow-sm"
                            >
                                +{{ restockSuggestion }}
                            </span>
                        </div>

                        <div
                            v-else
                            class="flex items-center justify-between opacity-40 grayscale"
                        >
                            <div
                                class="flex items-center gap-1.5 text-gray-500"
                            >
                                <div
                                    class="w-1.5 h-1.5 rounded-full bg-green-500"
                                ></div>
                                <span class="text-[10px] font-medium"
                                    >Stok Aman</span
                                >
                            </div>
                            <span class="text-[10px] font-mono text-gray-400">
                                {{ item.stock }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-between pt-2 mt-auto border-t border-dashed"
                :class="
                    item.stock <= 2
                        ? 'border-red-200 dark:border-red-900/30'
                        : 'border-gray-100 dark:border-gray-700'
                "
            >
                <div class="flex flex-col">
                    <span class="text-[9px] text-gray-400 uppercase"
                        >Harga Beli</span
                    >
                    <span
                        class="text-xs font-black text-gray-800 dark:text-gray-200"
                    >
                        {{ formatRupiah(item.purchase_price) }}
                    </span>
                </div>

                <button
                    class="p-1.5 rounded-lg transition-all active:scale-95 border"
                    :class="[
                        isSelected
                            ? 'bg-lime-500 text-white border-lime-500 shadow-md'
                            : 'bg-white border-gray-200 text-gray-300 hover:border-lime-500 hover:text-lime-600 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-500',
                    ]"
                >
                    <svg
                        v-if="isSelected"
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="3"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
