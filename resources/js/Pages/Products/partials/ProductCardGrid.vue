<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    data: Object,
});

const emit = defineEmits(["imageClick", "delete", "restore", "forceDelete"]);

// Helper
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Logic DSS
const isTrending = computed(() =>
    props.data.insights?.some((i) => i.type === "trend")
);
const isDeadStock = computed(() =>
    props.data.insights?.some((i) => i.type === "dead_stock")
);
const isMarginLow = computed(() =>
    props.data.insights?.some((i) => i.type === "margin_alert")
);
const isStockLow = computed(() => {
    const hasInsight = props.data.insights?.some(
        (i) => i.type === "restock" && i.severity === "critical"
    );
    return hasInsight || props.data.stock <= props.data.min_stock;
});

// Logic Profit
const profitInfo = computed(() => {
    const buy = props.data.purchase_price || 0;
    const sell = props.data.selling_price || 0;
    const profit = sell - buy;
    const percent = buy > 0 ? ((profit / buy) * 100).toFixed(0) : 0;
    return { amount: profit, percent: percent };
});

const isTrashed = computed(() => props.data.deleted_at !== null);
</script>

<template>
    <div
        class="flex flex-col h-full overflow-hidden transition-all duration-300 bg-white border border-gray-200 shadow-sm group dark:bg-gray-800 rounded-2xl dark:border-gray-700 hover:shadow-xl hover:-translate-y-1"
    >
        <div
            class="relative w-full aspect-[4/3] bg-gray-50 dark:bg-gray-900 p-6 flex items-center justify-center overflow-hidden cursor-pointer border-b border-gray-100 dark:border-gray-700"
            @click="
                emit('imageClick', { path: data.image_path, name: data.name })
            "
        >
            <img
                :src="
                    data.image_path
                        ? '/storage/' + data.image_path
                        : '/no-image.png'
                "
                :alt="data.name"
                class="object-contain w-full h-full transition-transform duration-500 group-hover:scale-110"
            />

            <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10">
                <span
                    v-if="isTrending"
                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-purple-600 text-white rounded-md shadow-sm animate-pulse"
                    >🔥 Trending</span
                >
                <span
                    v-if="isDeadStock"
                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-gray-600 text-white rounded-md shadow-sm"
                    >🐢 Slow</span
                >
                <span
                    v-if="isMarginLow"
                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-yellow-400 text-white rounded-md shadow-sm"
                    >⚠️ Margin</span
                >
                <span
                    v-if="isStockLow"
                    class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider bg-red-500 text-white rounded-md shadow-sm"
                    >🚨 Stok</span
                >
            </div>

            <div class="absolute top-3 right-3">
                <span
                    class="block w-3 h-3 border-2 border-white rounded-full shadow-sm"
                    :class="
                        data.status === 'active'
                            ? 'bg-green-500'
                            : 'bg-gray-400'
                    "
                >
                </span>
            </div>
        </div>

        <div class="flex flex-col justify-between flex-1 gap-3 p-4">
            <div>
                <div
                    class="flex items-center justify-between mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase"
                >
                    <span
                        class="truncate bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded border border-gray-100 dark:border-gray-600 text-gray-500 dark:text-gray-100"
                        >{{ data.brand?.name }}</span
                    >
                    <span
                        class="truncate bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded border border-gray-100 dark:border-gray-600 dark:text-gray-100 text-[9px] text-gray-600"
                        >{{ data.category?.name }} |
                        {{ data.product_type?.name }}</span
                    >
                </div>

                <Link
                    :href="route('products.show', data.id)"
                    class="block mb-2 text-lg font-bold leading-tight text-gray-900 transition dark:text-white hover:text-lime-600 dark:hover:text-lime-600 line-clamp-2"
                    :title="data.full_name"
                >
                    {{ data.full_name || data.name }}
                </Link>

                <div
                    class="flex items-center justify-between p-2 mb-3 text-xs text-gray-500 border border-gray-200 rounded-lg shadow-sm dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50"
                >
                    <div class="flex items-center gap-1">
                        <span class="font-bold">Kode:</span> {{ data.code }}
                    </div>
                    <div
                        class="flex items-center gap-1 font-bold"
                        :class="
                            isStockLow
                                ? 'text-red-600'
                                : 'text-gray-700 dark:text-gray-300'
                        "
                    >
                        <span>Stok:</span> {{ data.stock }}
                    </div>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-end justify-between mb-1">
                    <span class="text-[10px] text-gray-400 uppercase font-bold"
                        >Harga Jual</span
                    >
                    <span
                        class="text-[10px] font-bold text-green-600 bg-green-50 px-1.5 py-0.5 rounded"
                    >
                        +{{ profitInfo.percent }}%
                    </span>
                </div>

                <div class="flex items-center justify-between">
                    <div
                        class="text-xl font-black text-gray-900 sm:text-2xl dark:text-white"
                    >
                        {{ formatRupiah(data.selling_price) }}
                    </div>

                    <div class="flex gap-1">
                        <Link
                            v-if="!isTrashed"
                            :href="route('products.edit', data.id)"
                            class="p-2 text-yellow-600 transition bg-yellow-100 rounded-lg hover:bg-yellow-500 hover:text-white"
                        >
                            <svg
                                class="w-3 h-3 sm:w-5 sm:h-5"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                            >
                                <path
                                    d="M19.9902 18.9531C20.5471 18.9534 21 19.4124 21 19.9766C21 20.5418 20.5471 20.9998 19.9902 21H14.2793C13.7224 20.9998 13.2695 20.5419 13.2695 19.9766C13.2696 19.4124 13.7224 18.9533 14.2793 18.9531H19.9902ZM12.2412 3.95703C13.1538 2.78531 14.7463 2.67799 16.0303 3.69922L17.5049 4.87109C18.1097 5.34407 18.5134 5.96737 18.6514 6.62305C18.8105 7.34415 18.6403 8.05244 18.1631 8.66504L9.37598 20.0283C8.97274 20.544 8.37869 20.834 7.74219 20.8447L4.24023 20.8877C4.0493 20.8876 3.89009 20.7589 3.84766 20.5762L3.05176 17.125C2.91398 16.4909 3.05194 15.8352 3.45508 15.3301L9.68457 7.26758C9.79068 7.13908 9.98121 7.11856 10.1084 7.21387L12.7295 9.2998C12.8993 9.43955 13.1329 9.51467 13.377 9.48242C13.8969 9.41792 14.2474 8.94469 14.1943 8.43945C14.1625 8.18164 14.0349 7.96685 13.8652 7.80566C13.8122 7.76267 11.3184 5.7627 11.3184 5.7627C11.1593 5.63368 11.1276 5.39743 11.2549 5.2373L12.2412 3.95703Z"
                                    fill="currentColor"
                                />
                            </svg>
                        </Link>
                        <button
                            :title="'Hapus ' + data.name"
                            v-if="!isTrashed"
                            @click="emit('delete', data)"
                            class="p-2 text-red-600 transition bg-red-100 rounded-lg hover:bg-red-500 hover:text-red-100"
                        >
                            <svg
                                class="w-3 h-3 sm:w-5 sm:h-5"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path
                                    d="M18.9395 8.69727C19.1385 8.69738 19.3191 8.78402 19.4619 8.93066C19.5952 9.08766 19.663 9.28326 19.6436 9.48926C19.6429 9.56521 19.1099 16.2984 18.8057 19.1338C18.6151 20.8747 17.493 21.9319 15.8096 21.9609C14.5151 21.9899 13.2497 22 12.0039 22C10.6812 22 9.3874 21.9899 8.13184 21.9609C6.50488 21.9218 5.38206 20.8457 5.20117 19.1338C4.88816 16.2881 4.36472 9.56385 4.35449 9.48926C4.34477 9.28326 4.41071 9.08766 4.54492 8.93066C4.67715 8.78375 4.86811 8.69731 5.06836 8.69727H18.9395ZM14.0645 2C14.9485 2 15.7382 2.61708 15.9668 3.49707L16.1309 4.22656C16.2631 4.82145 16.778 5.24302 17.3711 5.24316H20.2871C20.676 5.24316 20.9998 5.56576 21 5.97656V6.35742C20.9998 6.75821 20.676 7.09082 20.2871 7.09082H3.71387C3.32402 7.09082 3.00025 6.75821 3 6.35742V5.97656C3.00021 5.56576 3.324 5.24316 3.71387 5.24316H6.62988C7.22203 5.24301 7.7369 4.82143 7.87012 4.22754L8.02344 3.5459C8.26078 2.61698 9.04181 2 9.93555 2H14.0645Z"
                                    fill="currentColor"
                                />
                            </svg>
                        </button>
                        <button
                            :title="'Pulihkan ' + data.name"
                            v-if="isTrashed"
                            @click="emit('restore', data)"
                            class="p-2 transition rounded-lg text-lime-600 bg-lime-100 hover:bg-lime-500 hover:text-lime-100"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                        </button>
                        <button
                            :title="'Hapus Permanen ' + data.name"
                            v-if="isTrashed"
                            @click="emit('forceDelete', data)"
                            class="p-2 text-red-100 transition bg-red-600 rounded-lg hover:text-red-600 hover:bg-red-200"
                        >
                            <svg
                                class="w-3 h-3 sm:w-5 sm:h-5"
                                viewBox="0 0 24 24"
                                fill="none"
                            >
                                <path
                                    d="M18.9395 8.69727C19.1385 8.69738 19.3191 8.78402 19.4619 8.93066C19.5952 9.08766 19.663 9.28326 19.6436 9.48926C19.6429 9.56521 19.1099 16.2984 18.8057 19.1338C18.6151 20.8747 17.493 21.9319 15.8096 21.9609C14.5151 21.9899 13.2497 22 12.0039 22C10.6812 22 9.3874 21.9899 8.13184 21.9609C6.50488 21.9218 5.38206 20.8457 5.20117 19.1338C4.88816 16.2881 4.36472 9.56385 4.35449 9.48926C4.34477 9.28326 4.41071 9.08766 4.54492 8.93066C4.67715 8.78375 4.86811 8.69731 5.06836 8.69727H18.9395ZM14.0645 2C14.9485 2 15.7382 2.61708 15.9668 3.49707L16.1309 4.22656C16.2631 4.82145 16.778 5.24302 17.3711 5.24316H20.2871C20.676 5.24316 20.9998 5.56576 21 5.97656V6.35742C20.9998 6.75821 20.676 7.09082 20.2871 7.09082H3.71387C3.32402 7.09082 3.00025 6.75821 3 6.35742V5.97656C3.00021 5.56576 3.324 5.24316 3.71387 5.24316H6.62988C7.22203 5.24301 7.7369 4.82143 7.87012 4.22754L8.02344 3.5459C8.26078 2.61698 9.04181 2 9.93555 2H14.0645Z"
                                    fill="currentColor"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
