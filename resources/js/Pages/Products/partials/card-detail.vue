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
        <!-- <div
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
        </div> -->
        <div
            class="flex flex-col w-full border-b border-gray-100 md:w-56 lg:w-64 bg-gray-50 dark:bg-gray-900 md:border-b-0 md:border-r dark:border-gray-700"
        >
            <div
                class="relative flex-1 w-full min-h-[160px] md:h-full flex items-center justify-center p-4 overflow-hidden group"
            >
                <img
                    :src="
                        data.image_path
                            ? '/storage/' + data.image_path
                            : '/no-image.png'
                    "
                    :alt="data.name"
                    class="object-contain max-w-full max-h-full transition-transform duration-500 cursor-pointer group-hover:scale-110"
                    @click="
                        $emit('imageClick', {
                            path: data.image_path,
                            name: data.name,
                        })
                    "
                />

                <div class="absolute z-10 flex flex-col gap-1 top-2 left-2">
                    <span
                        v-if="dss?.is_trending"
                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-purple-600/90 backdrop-blur-sm rounded-lg shadow-sm border border-white/20 animate-pulse flex items-center gap-1.5 transition-all hover:bg-purple-600"
                    >
                        🔥 <span class="inline">Trending</span>
                    </span>
                    <span
                        v-if="dss?.is_dead_stock"
                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-gray-600/90 backdrop-blur-sm rounded-lg shadow-sm border border-white/20 animate-pulse flex items-center gap-1.5"
                    >
                        🐢 <span class="inline">Slow</span>
                    </span>
                </div>

                <div class="absolute z-10 flex flex-wrap gap-1 bottom-2 left-2">
                    <span
                        v-if="dss?.is_margin_low"
                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-yellow-900 bg-yellow-400/90 backdrop-blur-sm rounded-lg shadow-sm border border-white/20 animate-pulse flex items-center gap-1.5"
                    >
                        ⚠️ <span class="inline">Margin</span>
                    </span>
                    <span
                        v-if="isStockLow"
                        class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-white bg-red-500/90 backdrop-blur-sm rounded-lg shadow-sm border border-white/20 flex items-center gap-1.5"
                    >
                        🚨 <span class="inline">Stok</span>
                    </span>
                </div>
            </div>

            <div
                class="py-1.5 text-[10px] font-bold text-center text-white uppercase tracking-wider w-full"
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
                            class="w-3 h-3 sm:w-5 sm:h-5"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                        >
                            <path
                                d="M19.9902 18.9531C20.5471 18.9534 21 19.4124 21 19.9766C21 20.5418 20.5471 20.9998 19.9902 21H14.2793C13.7224 20.9998 13.2695 20.5419 13.2695 19.9766C13.2696 19.4124 13.7224 18.9533 14.2793 18.9531H19.9902ZM12.2412 3.95703C13.1538 2.78531 14.7463 2.67799 16.0303 3.69922L17.5049 4.87109C18.1097 5.34407 18.5134 5.96737 18.6514 6.62305C18.8105 7.34415 18.6403 8.05244 18.1631 8.66504L9.37598 20.0283C8.97274 20.544 8.37869 20.834 7.74219 20.8447L4.24023 20.8877C4.0493 20.8876 3.89009 20.7589 3.84766 20.5762L3.05176 17.125C2.91398 16.4909 3.05194 15.8352 3.45508 15.3301L9.68457 7.26758C9.79068 7.13908 9.98121 7.11856 10.1084 7.21387L12.7295 9.2998C12.8993 9.43955 13.1329 9.51467 13.377 9.48242C13.8969 9.41792 14.2474 8.94469 14.1943 8.43945C14.1625 8.18164 14.0349 7.96685 13.8652 7.80566C13.8122 7.76267 11.3184 5.7627 11.3184 5.7627C11.1593 5.63368 11.1276 5.39743 11.2549 5.2373L12.2412 3.95703Z"
                                fill="currentColor"
                            />
                        </svg>
                        Edit
                    </Link>

                    <button
                        @click="showDelete = true"
                        class="flex items-center justify-center flex-1 gap-2 px-4 py-2 text-sm font-bold text-red-600 transition border border-red-100 rounded-lg sm:flex-none bg-red-50 hover:bg-red-100"
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
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
