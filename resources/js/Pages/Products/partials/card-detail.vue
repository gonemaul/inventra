<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Delete from "./modalDelete.vue";

const props = defineProps({
    data: Object,
    dss: Object,
    price_trend: Object,
});

console.log(props.data);
console.log(props.dss);
const showDelete = ref(false);
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const isStockLow = computed(() => {
    const hasInsight = props.data.insights?.some(
        (i) => i.type === "restock" && i.severity === "critical"
    );
    return hasInsight || props.data.stock <= props.data.min_stock;
});
</script>

<template>
    <Delete :show="showDelete" @close="showDelete = false" />
    <div
        class="flex flex-col overflow-hidden bg-white border-l-4 shadow-md md:flex-row dark:bg-gray-800 rounded-xl border-lime-500"
    >
        <div
            class="relative flex items-center justify-center w-full p-6 border-b border-gray-100 md:w-56 lg:w-64 bg-gray-50 dark:bg-gray-900 md:border-b-0 md:border-r dark:border-gray-700"
        >
            <div
                class="relative flex flex-col flex-shrink-0 w-full h-40 md:h-full"
            >
                <img
                    :src="
                        data.image_path
                            ? '/storage/' + data.image_path
                            : '/no-image.png'
                    "
                    :alt="data.name"
                    class="object-contain max-w-full max-h-full transition-transform cursor-pointer hover:scale-105"
                    @click="
                        $emit('imageClick', {
                            path: data.image_path,
                            name: data.name,
                        })
                    "
                />
                <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10">
                    <span
                        v-if="dss?.is_trending"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-purple-600 text-white rounded shadow-sm animate-pulse flex items-center gap-1"
                    >
                        🔥 <span class="hidden sm:inline">Trending</span>
                    </span>
                    <span
                        v-if="dss?.is_dead_stock"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-gray-600 text-white rounded shadow-sm flex items-center gap-1"
                    >
                        🐢 <span class="hidden sm:inline">Slow</span>
                    </span>
                </div>
                <div
                    class="absolute z-10 justify-between gap-1 md:flex bottom-3 left-2"
                >
                    <span
                        v-if="dss?.is_margin_low"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-yellow-400/80 text-yellow-900 rounded shadow-sm flex items-center gap-1"
                    >
                        ⚠️ <span class="hidden sm:inline">Margin</span>
                    </span>
                    <span
                        v-if="isStockLow"
                        class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider bg-red-500/80 text-white rounded shadow-sm flex items-center gap-1"
                    >
                        🚨 <span class="hidden sm:inline">Stok</span>
                    </span>
                </div>
            </div>
            <div
                class="py-1 text-[10px] font-bold text-center text-white uppercase tracking-wider"
                :class="
                    data.status === 'active' ? 'bg-lime-500' : 'bg-gray-400'
                "
            >
                {{ data.status === "active" ? "Aktif" : "Non-Aktif" }}
            </div>
        </div>

        <div class="flex flex-col justify-between flex-1 gap-4 p-4 sm:p-5">
            <div>
                <div
                    class="flex flex-wrap items-start justify-between gap-2 mb-2"
                >
                    <div
                        class="flex flex-wrap items-center gap-2 text-xs font-bold tracking-wider text-gray-500 uppercase"
                    >
                        <div class="flex justify-between w-full md:w-auto">
                            <span
                                class="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-700 dark:text-gray-300"
                            >
                                {{ data.brand?.name || "No Brand" }}
                            </span>
                            <span
                                class="font-mono md:hidden text-[10px] sm:text-xs text-gray-500 bg-gray-100 dark:text-gray-300 dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600"
                            >
                                {{ data.code }}
                            </span>
                        </div>
                        <span class="hidden text-gray-300 md:block">|</span>
                        <span class="truncate text-lime-600 dark:text-lime-400">
                            {{ data.category?.name }}
                        </span>
                        <span class="text-gray-300">|</span>
                        <span
                            class="text-gray-600 dark:text-gray-400 truncate max-w-[100px]"
                        >
                            {{ data.product_type?.name }}
                        </span>
                    </div>
                    <span
                        class="font-mono hidden md:block text-[10px] sm:text-xs text-gray-500 bg-gray-100 dark:text-gray-300 dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-200 dark:border-gray-600"
                    >
                        {{ data.code }}
                    </span>
                </div>

                <h1
                    class="mb-3 text-xl font-black leading-tight text-gray-800 sm:text-2xl md:text-3xl dark:text-white"
                >
                    {{ data.full_name || data.name }}
                </h1>

                <div
                    class="flex flex-wrap items-center justify-center gap-3 pb-3 mb-3 text-sm text-gray-600 border-b border-gray-100 md:justify-start dark:text-gray-300 dark:border-gray-700"
                >
                    <div class="flex flex-col md:flex-row items-center gap-1.5">
                        <span
                            class="text-[10px] text-gray-400 uppercase font-bold"
                            >Size</span
                        >
                        <span
                            class="font-semibold bg-gray-200 dark:bg-gray-700 px-1.5 rounded border border-gray-100 dark:border-gray-600"
                        >
                            {{ data.size?.name || "-" }}
                        </span>
                    </div>

                    <div class="flex flex-col md:flex-row items-center gap-1.5">
                        <span
                            class="text-[10px] text-gray-400 uppercase font-bold"
                            >Unit</span
                        >
                        <span
                            class="font-semibold bg-gray-200 dark:bg-gray-700 px-1.5 rounded border border-gray-100 dark:border-gray-600"
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

            <div class="grid grid-cols-1 gap-4 mb-4 sm:grid-cols-2">
                <div
                    class="relative p-3 border rounded-lg border-lime-100 bg-lime-50 dark:bg-lime-900/50 dark:border-lime-800"
                >
                    <span
                        class="text-[10px] font-bold text-lime-400 uppercase block mb-1"
                        >Modal (HPP)</span
                    >

                    <div
                        class="text-2xl font-black text-gray-700 dark:text-gray-200 leading-none mb-1.5"
                    >
                        {{ formatRupiah(data.purchase_price) }}
                    </div>

                    <div
                        v-if="data.financials.purchase_trend.direction === 'up'"
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-red-600 bg-white px-2 py-0.5 rounded shadow-sm border border-red-100"
                    >
                        <span
                            >📈 Naik
                            {{
                                formatRupiah(
                                    data.financials.purchase_trend.diff_rp
                                )
                            }}</span
                        >
                        <span class="text-red-400"
                            >({{
                                data.financials.purchase_trend.percent
                            }}%)</span
                        >
                    </div>
                    <div
                        v-else-if="
                            data.financials.purchase_trend.direction === 'down'
                        "
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-green-600 bg-white px-2 py-0.5 rounded shadow-sm border border-green-100"
                    >
                        <span
                            >📉 Turun
                            {{
                                formatRupiah(
                                    data.financials.purchase_trend.diff_rp
                                )
                            }}</span
                        >
                        <span class="text-green-400"
                            >({{
                                data.financials.purchase_trend.percent
                            }}%)</span
                        >
                    </div>
                    <div v-else class="text-[10px] text-gray-400 font-medium">
                        Stabil
                    </div>
                </div>
                <div
                    class="relative p-3 border border-blue-100 rounded-lg bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800"
                >
                    <div class="flex items-center justify-between mb-1">
                        <span
                            class="text-[10px] font-bold text-blue-400 uppercase"
                            >Harga Jual</span
                        >

                        <div
                            class="flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-white text-blue-700 shadow-sm border border-blue-100"
                            title="Keuntungan Bersih"
                        >
                            <span
                                >+{{
                                    formatRupiah(data.financials.margin.rp)
                                }}</span
                            >
                            <span class="text-gray-300">|</span>
                            <span class="text-green-600"
                                >{{ data.financials.margin.percent }}%</span
                            >
                        </div>
                    </div>

                    <div
                        class="text-2xl font-black text-blue-700 dark:text-blue-300 leading-none mb-1.5"
                    >
                        {{ formatRupiah(data.selling_price) }}
                    </div>

                    <div
                        v-if="data.financials.selling_trend.direction === 'up'"
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-green-600 bg-white px-2 py-0.5 rounded shadow-sm border border-green-100"
                    >
                        <span
                            >🚀 Naik
                            {{
                                formatRupiah(
                                    data.financials.selling_trend.diff_rp
                                )
                            }}
                        </span>
                    </div>
                    <div
                        v-else-if="
                            data.financials.selling_trend.direction === 'down'
                        "
                        class="inline-flex items-center gap-1 text-[10px] font-bold text-red-500 bg-white px-2 py-0.5 rounded shadow-sm border border-red-100"
                    >
                        <span
                            >🔻 Turun
                            {{
                                formatRupiah(
                                    data.financials.selling_trend.diff_rp
                                )
                            }}</span
                        >
                    </div>
                    <div v-else class="text-[10px] text-blue-300 font-medium">
                        Stabil
                    </div>
                </div>
            </div>
            <div
                class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700"
            >
                <Link
                    :href="route('products.index')"
                    class="order-2 w-full px-4 py-2 text-sm font-bold text-center text-gray-500 transition bg-white border border-gray-200 rounded-lg hover:text-gray-800 hover:bg-gray-50 sm:w-auto sm:order-1"
                >
                    Kembali
                </Link>

                <div class="flex order-1 w-full gap-2 sm:w-auto sm:order-2">
                    <Link
                        :href="route('products.edit', data.id)"
                        class="flex items-center justify-center flex-1 gap-2 px-5 py-2 text-sm font-bold text-white transition bg-yellow-500 rounded-lg shadow-sm sm:flex-none hover:bg-yellow-600"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                            />
                        </svg>
                        Edit
                    </Link>

                    <button
                        @click="showDelete = true"
                        class="flex items-center justify-center flex-1 gap-2 px-4 py-2 text-sm font-bold text-red-600 transition border border-red-100 rounded-lg sm:flex-none bg-red-50 hover:bg-red-100"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
