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
// Konfigurasi Tampilan Berdasarkan Type
const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    }).format(date);
};

const getLogConfig = (log) => {
    const type = log.type;
    const qty = Number(log.quantity);

    let config = {
        label: "Transaksi",
        icon: "📝",
        dotClass: "bg-gray-400",
        badgeClass: "bg-gray-100 text-gray-600 border-gray-200",
        isPositive: qty > 0,
        sign: qty > 0 ? "+" : "",
    };

    switch (type) {
        case "initial":
            config.label = "Stok Awal";
            config.icon = "🏁";
            config.dotClass = "bg-blue-500 shadow-blue-500/50";
            config.badgeClass = "bg-blue-50 text-blue-600 border-blue-100";
            config.isPositive = true;
            break;

        case "purchase":
            config.label = "Pembelian";
            config.icon = "🚛";
            config.dotClass = "bg-green-500 shadow-green-500/50";
            config.badgeClass = "bg-green-50 text-green-600 border-green-100";
            config.isPositive = true;
            break;

        case "sale":
            config.label = "Penjualan";
            config.icon = "🛒";
            config.dotClass = "bg-red-500 shadow-red-500/50";
            config.badgeClass = "bg-red-50 text-red-600 border-red-100";
            config.isPositive = false;
            break;

        case "adjustment_in":
            config.label = "Koreksi (+)";
            config.icon = "🔧";
            config.dotClass = "bg-emerald-500";
            config.badgeClass = "bg-emerald-50 text-emerald-600 border-emerald-100";
            config.isPositive = true;
            break;

        case "adjustment_out":
            config.label = "Koreksi (-)";
            config.icon = "📉";
            config.dotClass = "bg-orange-500";
            config.badgeClass = "bg-orange-50 text-orange-600 border-orange-100";
            config.isPositive = false;
            break;

        case "adjustment_opname":
            config.label = "Stok Opname";
            config.icon = "📋";
            config.isPositive = qty >= 0;
            config.dotClass = config.isPositive ? "bg-purple-500" : "bg-pink-500";
            config.badgeClass = config.isPositive 
                ? "bg-purple-50 text-purple-600 border-purple-100" 
                : "bg-pink-50 text-pink-600 border-pink-100";
            break;

        case "return_in":
            config.label = "Retur Customer";
            config.icon = "↩️";
            config.dotClass = "bg-teal-500";
            config.badgeClass = "bg-teal-50 text-teal-600 border-teal-100";
            config.isPositive = true;
            break;

        case "return_out":
            config.label = "Retur Supplier";
            config.icon = "↪️";
            config.dotClass = "bg-rose-500";
            config.badgeClass = "bg-rose-50 text-rose-600 border-rose-100";
            config.isPositive = false;
            break;
    }

    return config;
};
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
                        <div class="space-y-6">
                            <!-- Stats Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    class="p-4 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-50 text-blue-600 rounded-lg dark:bg-blue-900/20 dark:text-blue-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                        </div>
                                        <div>
                                             <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Terjual (30 Hari)
                                            </p>
                                            <p class="text-2xl font-black text-gray-900 dark:text-white">
                                                {{ detail.dss.sales_30_days }}
                                                <span class="text-sm font-medium text-gray-500">{{ detail.product.unit?.name }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="p-4 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-green-50 text-green-600 rounded-lg dark:bg-green-900/20 dark:text-green-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </div>
                                        <div>
                                             <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                                Estimasi Omzet
                                            </p>
                                            <p class="text-2xl font-black text-gray-900 dark:text-white">
                                                {{ formatRupiah(detail.dss.sales_30_days * detail.product.selling_price) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description & Specs -->
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                <!-- Main Info -->
                                <div class="lg:col-span-2 p-5 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" /></svg>
                                        Deskripsi Produk
                                    </h3>
                                    <div class="prose prose-sm max-w-none text-gray-600 dark:text-gray-300">
                                        <p v-if="detail.product.description">{{ detail.product.description }}</p>
                                        <p v-else class="italic text-gray-400">Tidak ada deskripsi untuk produk ini.</p>
                                    </div>
                                </div>

                                <!-- Specs Sidebar -->
                                <div class="p-5 bg-white border border-gray-100 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wider border-b border-gray-100 dark:border-gray-700 pb-2">
                                        Spesifikasi
                                    </h3>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">Min. Stok</span>
                                            <span class="font-bold text-gray-900 dark:text-white">{{ detail.product.min_stock }} {{ detail.product.unit?.name }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">Max. Stok</span>
                                            <span class="font-bold text-gray-900 dark:text-white">{{ detail.product.max_stock || '∞' }}</span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">Berat</span>
                                            <span class="font-bold text-gray-900 dark:text-white">
                                                 {{ detail.product.weight ? detail.product.weight + ' gr' : '-' }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center text-sm">
                                            <span class="text-gray-500 dark:text-gray-400">SKU/Barcode</span>
                                            <span class="font-mono text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-700 dark:text-gray-300">
                                                {{ detail.product.barcode || '-' }}
                                            </span>
                                        </div>
                                    </div>
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
                                class="divide-y divide-gray-100 dark:divide-gray-700"
                            >
                                <li
                                    v-for="(log, idx) in detail.stock_history"
                                    :key="idx"
                                    class="flex items-start justify-between px-2 py-4 transition rounded-lg group hover:bg-gray-50 dark:hover:bg-gray-800/50"
                                >
                                    <div
                                        class="flex gap-3"
                                        :class="{
                                            'opacity-50':
                                                log.type === 'deleted',
                                        }"
                                    >
                                        <div
                                            class="flex items-center justify-center w-10 h-10 rounded-full shadow-sm shrink-0"
                                            :class="
                                                getLogConfig(log).colorClass
                                            "
                                        >
                                            <span class="text-lg">{{
                                                getLogConfig(log).icon
                                            }}</span>
                                        </div>

                                        <div class="flex flex-col">
                                            <h4
                                                class="text-sm font-bold text-gray-800 dark:text-gray-200"
                                            >
                                                {{ getLogConfig(log).label }}
                                            </h4>

                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 space-y-0.5"
                                            >
                                                <p
                                                    v-if="log.reference_number"
                                                    class="font-mono text-[10px] text-gray-400 uppercase tracking-wide"
                                                >
                                                    #{{ log.reference_number }}
                                                </p>
                                                <p
                                                    v-if="log.description"
                                                    class="line-clamp-1"
                                                >
                                                    {{ log.description }}
                                                </p>
                                                <p
                                                    v-if="
                                                        log.type !== 'initial'
                                                    "
                                                    class="text-[10px] text-gray-400 flex items-center gap-1 mt-1"
                                                >
                                                    <span
                                                        class="px-1 bg-gray-100 rounded dark:bg-gray-700"
                                                        >{{
                                                            log.stock_before
                                                        }}</span
                                                    >
                                                    <span>➜</span>
                                                    <span
                                                        class="px-1 font-bold text-gray-600 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300"
                                                        >{{
                                                            log.stock_after
                                                        }}</span
                                                    >
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ml-2 text-right shrink-0">
                                        <p
                                            class="text-sm font-bold"
                                            :class="
                                            log.type === 'adjustment_opname' ? 'text-blue-600' :
                                                getLogConfig(log).isPositive
                                                    ? 'text-green-600'
                                                    : 'text-red-600'
                                            "
                                        >
                                            {{ log.type === 'adjustment_opname' ? '~ ' : getLogConfig(log).isPositive
                                                    ? "+ "
                                                    : "- "
                                            }}{{ log.type === 'adjustment_opname' ? log.stock_after : log.quantity }}
                                        </p>
                                        <p
                                            class="text-[10px] text-gray-400 mt-1"
                                        >
                                            {{
                                                formatDate(
                                                    log.created_at || log.date
                                                )
                                            }}
                                            WIB
                                        </p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </template>
                </Tabs>
            </div>
        </Deferred>
    </AuthenticatedLayout>
</template>
