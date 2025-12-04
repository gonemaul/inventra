<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    data: Object,
});
console.log(props.data);
const emit = defineEmits(["imageClick", "delete", "restore", "forceDelete"]);

// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

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

const isTrashed = computed(() => props.data.deleted_at !== null);
</script>

<template>
    <div
        class="flex flex-col h-full overflow-hidden transition-all duration-300 bg-white border border-gray-300 shadow-md md:flex-row group dark:bg-gray-800 rounded-xl dark:border-gray-700 hover:shadow-md"
    >
        <div
            class="relative flex flex-col flex-shrink-0 w-32 mx-auto border-r border-gray-100 sm:w-36 dark:border-gray-700 bg-gray-50 dark:bg-gray-900"
        >
            <div
                class="relative flex items-center justify-center flex-1 p-2 overflow-hidden cursor-pointer"
                @click="
                    emit('imageClick', {
                        path: data.image_path,
                        name: data.name,
                    })
                "
            >
                <img
                    :src="
                        data.image_path
                            ? '/storage/' + data.image_path
                            : '/no-image.png'
                    "
                    :alt="data.name"
                    class="object-contain w-full h-full transition-transform duration-500 max-h-28 group-hover:scale-110"
                />

                <div
                    class="absolute z-10 flex flex-col items-start gap-1 top-1 left-1 right-1"
                >
                    <span
                        v-if="isTrending"
                        class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-purple-600 text-white rounded shadow-sm animate-pulse"
                    >
                        🔥 Trending
                    </span>
                    <span
                        v-if="isDeadStock"
                        class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-gray-600 text-white rounded shadow-sm"
                    >
                        🐢 Slow
                    </span>
                </div>
                <div
                    class="absolute z-10 justify-between hidden gap-1 md:flex bottom-2 left-2 right-2"
                >
                    <span
                        v-if="isMarginLow"
                        class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-opacity-50 bg-yellow-400 text-yellow-900 rounded shadow-sm"
                    >
                        ⚠️ Margin
                    </span>
                    <span
                        v-if="isStockLow"
                        class="px-1.5 py-0.5 text-[9px] flex flex-col font-bold uppercase tracking-wider bg-red-500 bg-opacity-50 text-white rounded shadow-sm"
                    >
                        🚨 Stok
                    </span>
                </div>
            </div>

            <div
                class="py-1 text-[10px] rounded-br rounded-bl font-bold text-center text-white uppercase tracking-wider"
                :class="
                    data.status === 'active' ? 'bg-lime-500' : 'bg-gray-400'
                "
            >
                {{ data.status === "active" ? "Aktif" : "Non-Aktif" }}
            </div>
        </div>

        <div
            class="flex flex-col justify-between flex-1 min-w-0 p-3 bg-white dark:bg-gray-800"
        >
            <div>
                <div class="flex items-start justify-between mb-1">
                    <div
                        class="flex flex-wrap items-center gap-1.5 text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                    >
                        <span
                            class="truncate bg-gray-200 dark:bg-gray-700 px-1.5 py-0.5 rounded border border-gray-100 dark:border-gray-600 text-gray-500 dark:text-gray-100"
                            >{{ data.brand?.name }}</span
                        >
                        <span class="hidden md:block">•</span>
                        <span class="hidden truncate md:flex text-lime-600">{{
                            data.category?.name
                        }}</span>
                        <span class="hidden md:block">|</span>
                        <span
                            class="truncate hidden md:flex max-w-[70px] text-lime-600"
                            >{{ data.product_type?.name }}</span
                        >
                    </div>
                    <span
                        class="text-[11px] font-mono text-gray-500 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded border border-gray-100 dark:border-gray-600 dark:text-gray-100"
                    >
                        {{ data.code }}
                    </span>
                </div>

                <Link
                    :href="route('products.show', data.id)"
                    class="block mb-2 text-sm font-bold leading-snug text-gray-900 transition sm:text-base dark:text-white hover:text-lime-600 line-clamp-1"
                    :title="data.full_name"
                >
                    {{ data.full_name || data.name }}
                </Link>

                <div
                    class="flex items-center justify-center gap-3 pb-2 mb-2 text-xs text-gray-500 border-b md:justify-start dark:text-gray-400"
                >
                    <div class="flex flex-col items-center gap-1 md:flex-row">
                        <span
                            class="text-[10px] font-bold uppercase text-gray-400"
                            >Size</span
                        >
                        <span
                            class="font-semibold text-gray-700 rounded px-1.5 py-0.5 bg-gray-100 border-gray-100 dark:text-gray-200 dark:bg-gray-700 dark:border-gray-700"
                            >{{ data.size?.name || "-" }}</span
                        >
                    </div>
                    <div class="flex flex-col items-center gap-1 md:flex-row">
                        <span
                            class="text-[10px] font-bold uppercase text-gray-400"
                            >Unit</span
                        >
                        <span
                            class="font-semibold text-gray-700 bg-gray-100 px-1.5 py-0.5 dark:bg-gray-700 border-gray-100 rounded dark:text-gray-200 dark:border-gray-700"
                            >{{ data.unit?.name || "Pcs" }}</span
                        >
                    </div>
                    <div class="flex flex-col items-center gap-1 md:flex-row">
                        <span
                            class="text-[10px] font-bold uppercase"
                            :class="
                                isStockLow ? 'text-red-500' : 'text-gray-400'
                            "
                            >Stok</span
                        >
                        <span
                            class="font-bold px-1.5 py-0.5 rounded"
                            :class="
                                isStockLow
                                    ? 'bg-red-50 dark:bg-red-100 !text-red-600 ring-1 ring-red-100'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                            "
                            >{{ data.stock }}</span
                        >
                    </div>
                </div>
            </div>

            <div class="space-y-2.5">
                <div class="flex flex-col gap-1">
                    <span class="text-[11px] text-gray-400 font-bold uppercase"
                        >Modal (HPP)</span
                    >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-sm font-bold text-gray-600 dark:text-gray-300"
                        >
                            {{ formatRupiah(data.purchase_price) }}
                        </span>

                        <div
                            v-if="
                                data.financials.purchase_trend.direction ===
                                'up'
                            "
                            class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-600 border border-green-100"
                            :title="
                                `Modal Naik : ` +
                                formatRupiah(
                                    data.financials.purchase_trend.diff_rp
                                )
                            "
                        >
                            <span
                                >↗
                                {{
                                    data.financials.purchase_trend.percent
                                }}%</span
                            >
                        </div>
                        <div
                            v-if="
                                data.financials.purchase_trend.direction ===
                                'down'
                            "
                            class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 border border-red-100"
                            :title="
                                `Modal Turun : ` +
                                formatRupiah(
                                    data.financials.purchase_trend.diff_rp
                                )
                            "
                        >
                            <span
                                >↘ ({{
                                    data.financials.purchase_trend.percent
                                }}%)</span
                            >
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-[11px] text-gray-400 font-bold uppercase"
                        >Harga Jual</span
                    >

                    <div class="flex items-center justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="text-lg font-black leading-none text-gray-900 dark:text-white"
                            >
                                {{ formatRupiah(data.selling_price) }}
                            </span>
                            <div
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-100"
                                :title="
                                    `Margin : ` +
                                    formatRupiah(data.financials.margin.rp)
                                "
                            >
                                <span
                                    >+{{
                                        data.financials.margin.percent
                                    }}%</span
                                >
                            </div>
                            <div
                                v-if="
                                    data.financials.selling_trend.direction ===
                                    'up'
                                "
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-50 text-green-600 border border-green-100"
                                :title="
                                    `Harga Jual Naik : ` +
                                    formatRupiah(
                                        data.financials.selling_trend.diff_rp
                                    )
                                "
                            >
                                <span
                                    >↗
                                    {{
                                        data.financials.selling_trend.percent
                                    }}%</span
                                >
                            </div>
                            <div
                                v-if="
                                    data.financials.selling_trend.direction ===
                                    'down'
                                "
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-50 text-red-600 border border-red-100"
                                :title="
                                    `Harga Jual Turun : ` +
                                    formatRupiah(
                                        data.financials.selling_trend.diff_rp
                                    )
                                "
                            >
                                <span
                                    >↘
                                    {{
                                        data.financials.selling_trend.percent
                                    }}%</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link
                                v-if="!isTrashed"
                                :href="route('products.edit', data.id)"
                                class="p-1.5 text-yellow-700 hover:text-yellow-100 bg-yellow-100 hover:bg-yellow-300 border border-gray-200 rounded transition"
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
                                v-if="!isTrashed"
                                @click="emit('delete', data)"
                                class="p-1.5 text-red-700 hover:text-red-100 bg-red-200 hover:bg-red-300 border border-red-200 rounded transition"
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
                                v-if="isTrashed"
                                @click="emit('restore', data)"
                                class="p-1.5 text-green-500 hover:bg-green-50 rounded border"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
