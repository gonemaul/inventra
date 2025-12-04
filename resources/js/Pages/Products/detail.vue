<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import CardDetail from "./partials/card-detail.vue";
import { computed } from "vue";
import Tabs from "@/Components/Tabs.vue";
import TabForecasting from "./partials/tab-forecasting.vue";
import TabAnalisis from "./partials/tab-analisis.vue";

const props = defineProps({
    product: Object,
    dss: Object, // Data Insight (Deadstock/Restock)
    chart_data: Array, // Data Grafik
    stock_history: Array, // Data Riwayat
    price_trend: Object,
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
    const max = Math.max(...props.chart_data.map((d) => d.qty));
    return max === 0 ? 5 : max;
});
</script>

<template>
    <Head :title="`Detail ${product.name}`" />

    <AuthenticatedLayout headerTitle="Detail Barang">
        <div class="w-full min-h-screen space-y-5">
            <CardDetail :data="product" :dss="dss" :price_trend="price_trend" />

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
                            Performa penjualan produk ini dalam 7 hari terakhir.
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
                                    {{ dss.sales_30_days }}
                                    <span class="text-sm text-gray-500">{{
                                        product.unit?.name
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
                                            dss.sales_30_days *
                                                product.selling_price
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- <div
                            class="flex items-end justify-between h-48 gap-2 px-2 mt-6"
                        >
                            <div
                                v-for="(day, index) in chart_data"
                                :key="index"
                                class="flex flex-col items-center w-full group"
                            >
                                <div
                                    class="px-2 py-1 mb-1 text-xs text-white transition bg-gray-800 rounded opacity-0 group-hover:opacity-100"
                                >
                                    {{ day.qty }} Unit
                                </div>
                                <div
                                    class="relative w-full overflow-hidden transition-all duration-500 bg-blue-100 rounded-t dark:bg-gray-600 hover:bg-blue-200"
                                    :style="{
                                        height:
                                            (day.qty / maxChartValue) * 100 +
                                            '%',
                                    }"
                                >
                                    <div
                                        class="absolute bottom-0 w-full h-full bg-blue-500 opacity-80"
                                    ></div>
                                </div>
                                <span class="text-[10px] text-gray-500 mt-2">{{
                                    day.day
                                }}</span>
                            </div>
                        </div> -->
                    </div>
                </template>

                <template #analisis>
                    <TabAnalisis :inventory="product.inventory" />
                </template>
                <template #forecast>
                    <TabForecasting :inventory="product.inventory" />
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
                                v-for="(log, idx) in stock_history"
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
                                        <span v-if="log.type == 'in'">⬇️</span>
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
                                            })
                                        }}
                                    </p>
                                </div>
                            </li>

                            <li
                                v-if="stock_history.length === 0"
                                class="py-4 text-sm text-center text-gray-400"
                            >
                                Belum ada riwayat transaksi.
                            </li>
                        </ul>
                    </div>
                </template>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
