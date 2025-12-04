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
                    class="absolute z-10 flex flex-col items-start gap-1 top-2 left-2"
                >
                    <span
                        v-if="isTrending"
                        class="px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider bg-purple-600 text-white rounded shadow-sm animate-pulse"
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
                    class="absolute z-10 justify-between hidden gap-1 md:flex bottom-2 left-2"
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
                    <span class="text-[9px] text-gray-400 font-bold uppercase"
                        >Modal (HPP)</span
                    >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="text-xs font-bold text-gray-600 dark:text-gray-300"
                        >
                            {{ formatRupiah(data.purchase_price) }}
                        </span>

                        <div
                            v-if="
                                data.financials.purchase_trend.direction ===
                                'up'
                            "
                            class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-green-50 text-green-600 border border-green-100"
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
                            class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-50 text-red-600 border border-red-100"
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
                    <span class="text-[9px] text-gray-400 font-bold uppercase"
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
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-50 text-blue-600 border border-blue-100"
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
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-green-50 text-green-600 border border-green-100"
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
                                class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-50 text-red-600 border border-red-100"
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
                                class="p-1.5 text-yellow-700 hover:text-yellow-600 bg-yellow-100 hover:bg-yellow-300 border border-gray-200 rounded transition"
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
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    ></path>
                                </svg>
                            </Link>
                            <button
                                v-if="!isTrashed"
                                @click="emit('delete', data)"
                                class="p-1.5 text-red-700 hover:text-red-600 bg-red-200 hover:bg-red-300 border border-red-200 rounded transition"
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
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    ></path>
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
