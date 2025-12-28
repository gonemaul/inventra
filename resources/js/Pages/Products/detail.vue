<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Deferred, Head } from "@inertiajs/vue3";
import CardDetail from "./partials/card-detail.vue";
import { computed } from "vue";
import Tabs from "@/Components/Tabs.vue";
import TabForecasting from "./partials/tab-forecasting.vue";
import TabAnalisis from "./partials/tab-analisis.vue";

const props = defineProps({
    detail: Array,
});

const tabs = [
    { key: "ringkasan", label: "Ringkasan Penjualan" },
    { key: "analisis", label: "Analisis Pergerakan" },
    { key: "forecast", label: "Forecasting Restock" },
    { key: "riwayat", label: "Riwayat Stock" },
];

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Helper Chart Max Value (Agar grafik proporsional)
const maxChartValue = computed(() => {
    const max = Math.max(...props.detail.chart_data.map((d) => d.qty));
    return max === 0 ? 5 : max;
});
</script>

<template>
    <Head :title="`Detail`" />

    <AuthenticatedLayout headerTitle="Detail Barang">
        <Deferred :data="['detail']">
            <template #fallback>
                <div class="w-full min-h-screen space-y-5">
                    <div
                        class="flex flex-col overflow-hidden bg-white border-t border-l-4 border-gray-200 shadow-md md:flex-row dark:bg-gray-800 rounded-xl dark:border-gray-700 animate-pulse"
                    >
                        <div
                            class="flex flex-col w-full border-b border-gray-100 md:w-56 lg:w-64 bg-gray-50 dark:bg-gray-900 md:border-b-0 md:border-r dark:border-gray-700"
                        >
                            <div
                                class="relative flex-1 w-full min-h-[160px] md:h-full flex items-center justify-center bg-gray-200 dark:bg-gray-800"
                            >
                                <svg
                                    class="w-10 h-10 text-gray-300 dark:text-gray-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <div
                                    class="absolute w-16 h-5 bg-gray-300 rounded-lg top-2 left-2 dark:bg-gray-700"
                                ></div>
                            </div>

                            <div
                                class="w-full h-8 bg-gray-300 dark:bg-gray-700"
                            ></div>
                        </div>

                        <div
                            class="flex flex-col justify-between flex-1 gap-4 p-4 sm:p-5"
                        >
                            <div>
                                <div
                                    class="flex flex-wrap items-center justify-between gap-2 mb-2"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-16 h-5 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <span
                                            class="text-gray-200 dark:text-gray-700"
                                            >|</span
                                        >
                                        <div
                                            class="w-20 h-5 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <span
                                            class="text-gray-200 dark:text-gray-700"
                                            >|</span
                                        >
                                        <div
                                            class="h-5 bg-gray-200 rounded w-14 dark:bg-gray-700"
                                        ></div>
                                    </div>
                                    <div
                                        class="hidden w-24 h-5 bg-gray-200 rounded dark:bg-gray-700 md:block"
                                    ></div>
                                </div>

                                <div
                                    class="w-3/4 h-8 mb-4 bg-gray-200 rounded sm:h-9 md:h-10 dark:bg-gray-700"
                                ></div>

                                <div
                                    class="flex flex-wrap items-center gap-4 pb-3 mb-3 border-b border-gray-100 dark:border-gray-700"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-3 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-12 h-6 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-3 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-12 h-6 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="w-8 h-3 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-10 h-6 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-1 gap-4 mb-4 sm:grid-cols-2"
                            >
                                <div
                                    class="p-3 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <div
                                        class="w-20 h-3 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                                    ></div>
                                    <div
                                        class="w-32 h-8 mb-2 bg-gray-300 rounded dark:bg-gray-600"
                                    ></div>
                                    <div
                                        class="w-24 h-5 bg-gray-200 rounded dark:bg-gray-700"
                                    ></div>
                                </div>

                                <div
                                    class="p-3 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <div class="flex justify-between mb-2">
                                        <div
                                            class="w-20 h-3 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                        <div
                                            class="w-16 h-4 bg-gray-200 rounded dark:bg-gray-700"
                                        ></div>
                                    </div>
                                    <div
                                        class="w-32 h-8 mb-2 bg-gray-300 rounded dark:bg-gray-600"
                                    ></div>
                                    <div
                                        class="w-24 h-5 bg-gray-200 rounded dark:bg-gray-700"
                                    ></div>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-700"
                            >
                                <div
                                    class="w-full h-10 bg-gray-200 rounded-lg sm:w-24 dark:bg-gray-700"
                                ></div>

                                <div class="flex w-full gap-2 sm:w-auto">
                                    <div
                                        class="flex-1 w-full h-10 bg-gray-300 rounded-lg sm:flex-none sm:w-24 dark:bg-gray-600"
                                    ></div>
                                    <div
                                        class="flex-1 w-full h-10 bg-gray-200 rounded-lg sm:flex-none sm:w-24 dark:bg-gray-700"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full overflow-x-auto animate-pulse">
                        <div
                            class="flex border-b-2 border-gray-200 min-w-max flex-nowrap dark:border-gray-700"
                        >
                            <div
                                class="flex items-center gap-2 px-4 py-3 bg-gray-100 border-b-2 border-gray-300 rounded-t-lg dark:bg-gray-800 dark:border-gray-600"
                            >
                                <div
                                    class="w-16 h-4 bg-gray-300 rounded dark:bg-gray-600"
                                ></div>
                                <div
                                    class="w-5 h-5 bg-gray-300 rounded-full dark:bg-gray-600"
                                ></div>
                            </div>

                            <div
                                class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent"
                            >
                                <div
                                    class="w-24 h-4 bg-gray-200 rounded dark:bg-gray-700"
                                ></div>
                            </div>

                            <div
                                class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent"
                            >
                                <div
                                    class="w-20 h-4 bg-gray-200 rounded dark:bg-gray-700"
                                ></div>
                                <div
                                    class="w-5 h-5 bg-gray-200 rounded-full dark:bg-gray-700"
                                ></div>
                            </div>

                            <div
                                class="flex items-center gap-2 px-4 py-3 border-b-2 border-transparent"
                            >
                                <div
                                    class="w-16 h-4 bg-gray-200 rounded dark:bg-gray-700"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div class="w-full min-h-screen space-y-5">
                <CardDetail
                    :data="detail.product"
                    :dss="detail.dss"
                    :price_trend="detail.price_trend"
                />

                <Tabs :tabs="tabs" defaultTab="ringkasan">
                    <template #ringkasan>
                        <div
                            class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                        >
                            <h2
                                class="text-lg font-semibold text-gray-900 dark:text-white"
                            >
                                Ringkasan Penjualan
                            </h2>
                            <p
                                class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                            >
                                Performa penjualan produk ini dalam 7 hari
                                terakhir.
                            </p>

                            <div class="grid grid-cols-2 gap-4 mt-4">
                                <div
                                    class="p-3 border border-blue-100 rounded bg-blue-50 dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <p
                                        class="text-xs text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Terjual (30 Hari)
                                    </p>
                                    <p
                                        class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                    >
                                        {{ detail.dss.sales_30_days }}
                                        <span class="text-sm text-gray-500">{{
                                            detail.product.unit?.name
                                        }}</span>
                                    </p>
                                </div>
                                <div
                                    class="p-3 border border-green-100 rounded bg-green-50 dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <p
                                        class="text-xs text-gray-500 uppercase dark:text-gray-400"
                                    >
                                        Estimasi Omzet (30 Hari)
                                    </p>
                                    <p
                                        class="text-lg font-bold text-green-600 dark:text-green-400"
                                    >
                                        {{
                                            formatRupiah(
                                                detail.dss.sales_30_days *
                                                    detail.product.selling_price
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #analisis>
                        <TabAnalisis
                            :inventory="detail.product.inventory"
                            :product="detail.product"
                            :chart_data="detail.chart_data"
                        />
                    </template>
                    <template #forecast>
                        <TabForecasting :inventory="detail.product.inventory" />
                    </template>

                    <template #riwayat>
                        <div
                            class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h2
                                        class="text-lg font-semibold text-gray-900 dark:text-white"
                                    >
                                        Riwayat Stock
                                    </h2>
                                    <p
                                        class="text-sm text-gray-600 dark:text-gray-300"
                                    >
                                        Mutasi keluar masuk barang.
                                    </p>
                                </div>
                            </div>

                            <ul
                                class="mt-4 divide-y divide-gray-100 dark:divide-gray-700"
                            >
                                <li
                                    v-for="(log, idx) in detail.stock_history"
                                    :key="idx"
                                    class="flex items-center justify-between py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center w-8 h-8 rounded-full"
                                            :class="
                                                log.type == 'in'
                                                    ? 'bg-green-100 text-green-600'
                                                    : 'bg-red-100 text-red-600'
                                            "
                                        >
                                            <span v-if="log.type == 'in'"
                                                >⬇️</span
                                            >
                                            <span v-else>⬆️</span>
                                        </div>
                                        <div>
                                            <p
                                                class="text-sm font-bold text-gray-800 dark:text-gray-200"
                                            >
                                                {{
                                                    log.type == "in"
                                                        ? "Barang Masuk"
                                                        : "Barang Keluar"
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                {{ log.note }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="font-bold"
                                            :class="
                                                log.type == 'in'
                                                    ? 'text-green-600'
                                                    : 'text-red-600'
                                            "
                                        >
                                            {{ log.type == "in" ? "+" : "-" }}
                                            {{ log.qty }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{
                                                new Date(
                                                    log.date
                                                ).toLocaleDateString("id-ID", {
                                                    day: "numeric",
                                                    month: "short",
                                                    year: "numeric",
                                                })
                                            }}
                                        </p>
                                    </div>
                                </li>

                                <li
                                    v-if="detail.stock_history.length === 0"
                                    class="py-4 text-sm text-center text-gray-400"
                                >
                                    Belum ada riwayat transaksi.
                                </li>
                            </ul>
                        </div>
                    </template>
                </Tabs>
            </div>
        </Deferred>
    </AuthenticatedLayout>
</template>
