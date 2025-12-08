<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    summary: Object, // Data Real dari Backend
});
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatK = (val) => (val / 1000000).toFixed(1) + " Juta";
// Data Menu (Ditambah fitur Export)
const reportGroups = [
    {
        title: "INVENTORY",
        subtitle: "Stok & Aset",
        color: "text-emerald-600 bg-emerald-50 border-emerald-200", // Icon Box Style
        borderColor: "hover:border-emerald-400",
        items: [
            {
                title: "Kartu Stok (Stock Card)",
                desc: "Audit keluar-masuk barang per item.",
                route: "reports.stock-card",
                canExport: true,
                icon: "📦",
            },
            {
                title: "Valuasi Aset",
                desc: "Total nilai uang dalam bentuk barang.",
                route: "reports.stock-value",
                canExport: true,
                icon: "💰",
            },
            {
                title: "Dead Stock Analysis",
                desc: "Barang macet > 90 hari.",
                route: "reports.dead-stock",
                canExport: false,
                icon: "🕸️",
            },
        ],
    },
    {
        title: "SALES",
        subtitle: "Omzet & Laba",
        color: "text-blue-600 bg-blue-50 border-blue-200",
        borderColor: "hover:border-blue-400",
        items: [
            {
                title: "Laporan Omzet",
                desc: "Rekap pendapatan harian/bulanan.",
                route: "reports.sales-revenue",
                canExport: true,
                icon: "📈",
            },
            {
                title: "Produk Terlaris (Pareto)",
                desc: "20% Barang penyumbang 80% profit.",
                route: "reports.top-products",
                canExport: true,
                icon: "🏆",
            },
            {
                title: "Laba Kotor (Margin)",
                desc: "Analisa keuntungan per transaksi.",
                route: "reports.gross-profit",
                canExport: true,
                icon: "💵",
            },
        ],
    },
    {
        title: "PROCUREMENT",
        subtitle: "Beli & Hutang",
        color: "text-orange-600 bg-orange-50 border-orange-200",
        borderColor: "hover:border-orange-400",
        items: [
            {
                title: "Pembelian Supplier",
                desc: "Volume belanja per vendor.",
                route: "reports.purchase-supplier",
                canExport: true,
                icon: "🚚",
            },
            {
                title: "Buku Hutang (AP)",
                desc: "Jadwal jatuh tempo tagihan.",
                route: "reports.accounts-payable",
                canExport: true,
                icon: "📜",
            },
            {
                title: "Price Watch",
                desc: "Tren kenaikan harga modal.",
                route: "reports.price-watch",
                canExport: false,
                icon: "⚠️",
            },
        ],
    },
    {
        title: "FINANCE",
        subtitle: "Keuangan Utama",
        color: "text-purple-600 bg-purple-50 border-purple-200",
        borderColor: "hover:border-purple-400",
        items: [
            {
                title: "Laba Rugi (P&L)",
                desc: "Net Profit (Omzet - HPP - Beban).",
                route: "reports.profit-loss",
                canExport: true,
                icon: "📊",
            },
            {
                title: "Arus Kas (Cashflow)",
                desc: "Uang masuk vs keluar riil.",
                route: "reports.cash-flow",
                canExport: true,
                icon: "💸",
            },
        ],
    },
];

// Placeholder aksi export (Nanti diintegrasikan ke Backend)
const quickExport = (reportTitle, format) => {
    alert(`Mengekspor ${reportTitle} ke format ${format}...`);
};
</script>

<!-- <template>
    <Head title="Command Center" />

    <AuthenticatedLayout headerTitle="Executive Report Hub">
        <div class="min-h-screen pb-20 space-y-8">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div
                    class="relative p-6 overflow-hidden text-white transition shadow-lg bg-gradient-to-br from-gray-800 to-gray-900 dark:from-lime-700 dark:to-lime-950 rounded-2xl"
                >
                    <div class="relative z-10">
                        <p
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Total Aset Stok
                        </p>
                        <h3 class="mt-1 text-2xl font-black">Rp 1.2M</h3>
                        <p
                            class="flex items-center gap-1 mt-2 text-xs text-lime-400"
                        >
                            <span>▲ 2.5%</span> <span>bulan ini</span>
                        </p>
                    </div>
                    <div class="absolute bottom-0 right-0 p-4 opacity-10">
                        <svg
                            class="w-20 h-20"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Omzet Bulan Ini
                    </p>
                    <h3
                        class="mt-1 text-2xl font-black text-gray-800 dark:text-white"
                    >
                        Rp 850jt
                    </h3>
                    <div
                        class="w-full bg-gray-200 rounded-full h-1.5 mt-4 dark:bg-gray-700"
                    >
                        <div
                            class="bg-blue-500 h-1.5 rounded-full"
                            style="width: 70%"
                        ></div>
                    </div>
                    <p class="mt-1 text-[10px] text-gray-400 text-right">
                        Target 1M
                    </p>
                </div>

                <div
                    class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Hutang Jatuh Tempo
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-red-500">
                        Rp 45jt
                    </h3>
                    <p class="mt-2 text-xs text-gray-500">
                        Dalam 7 hari ke depan
                    </p>
                </div>

                <div
                    class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <p
                        class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Laba Bersih (Est)
                    </p>
                    <h3 class="mt-1 text-2xl font-black text-lime-600">
                        Rp 120jt
                    </h3>
                    <p class="mt-2 text-xs text-gray-500">
                        Margin rata-rata: 18%
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 xl:grid-cols-2">
                <div
                    v-for="(group, idx) in reportGroups"
                    :key="idx"
                    class="p-6 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-3xl dark:border-gray-700"
                >
                    <div class="flex items-center gap-4 mb-6">
                        <div
                            class="flex items-center justify-center w-12 h-12 text-white shadow-md rounded-2xl bg-gradient-to-br"
                            :class="group.color"
                        >
                            <svg
                                v-if="group.icon === 'box'"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                            <svg
                                v-if="group.icon === 'chart'"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            <svg
                                v-if="group.icon === 'truck'"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v9h1m8-9h2.414a1 1 0 01.707.293l2.586 2.586a1 1 0 01.293.707V16h-1.5m-4.5 0h3"
                                />
                            </svg>
                            <svg
                                v-if="group.icon === 'wallet'"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div>
                            <h4
                                class="text-lg font-black tracking-wide text-gray-800 uppercase dark:text-white"
                            >
                                {{ group.title }}
                            </h4>
                            <p class="text-xs font-medium text-gray-500">
                                {{ group.subtitle }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, i) in group.items"
                            :key="i"
                            class="flex items-center justify-between p-3 transition-all duration-200 border border-gray-100 group rounded-xl hover:border-lime-300 bg-gray-50/50 hover:bg-white dark:bg-gray-700/30 dark:hover:bg-gray-700 dark:border-gray-600"
                        >
                            <Link
                                :href="
                                    item.route !== '#' ? route(item.route) : '#'
                                "
                                class="flex flex-col flex-1 cursor-pointer"
                            >
                                <span
                                    class="text-sm font-bold text-gray-700 transition dark:text-gray-200 group-hover:text-lime-600"
                                >
                                    {{ item.title }}
                                </span>
                                <span
                                    class="text-[10px] text-gray-400 mt-0.5"
                                    >{{ item.desc }}</span
                                >
                            </Link>

                            <div
                                class="flex items-center gap-1 transition-opacity opacity-0 group-hover:opacity-100"
                            >
                                <button
                                    v-if="item.canExport"
                                    @click="quickExport(item.title, 'PDF')"
                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded bg-white border border-gray-200 shadow-sm"
                                    title="Export PDF"
                                >
                                    <span class="text-[10px] font-bold"
                                        >PDF</span
                                    >
                                </button>
                                <button
                                    v-if="item.canExport"
                                    @click="quickExport(item.title, 'XLS')"
                                    class="p-1.5 text-green-600 hover:bg-green-50 rounded bg-white border border-gray-200 shadow-sm"
                                    title="Export Excel"
                                >
                                    <span class="text-[10px] font-bold"
                                        >XLS</span
                                    >
                                </button>
                                <Link
                                    :href="
                                        item.route !== '#'
                                            ? route(item.route)
                                            : '#'
                                    "
                                    class="p-1.5 text-gray-400 hover:text-lime-600"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> -->
<template>
    <Head title="Command Center" />

    <AuthenticatedLayout headerTitle="Executive Report Hub">
        <div class="min-h-screen pb-20 space-y-8 bg-gray-50 dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-5 md:grid-cols-4">
                <div
                    class="relative p-6 overflow-hidden text-white border border-gray-700 shadow-xl bg-gradient-to-br from-gray-700 to-gray-900 dark:from-lime-600 dark:to-lime-950 rounded-2xl"
                >
                    <div class="relative z-10">
                        <p
                            class="text-[10px] font-bold tracking-widest text-gray-400 dark:text-gray-100 uppercase"
                        >
                            Total Aset Stok (HPP)
                        </p>
                        <h3
                            class="mt-2 text-2xl font-black truncate"
                            :title="formatRupiah(summary.total_asset)"
                        >
                            {{ formatK(summary.total_asset) }}
                        </h3>
                        <p
                            class="mt-2 text-xs text-gray-400 dark:text-gray-200"
                        >
                            Nilai modal di gudang
                        </p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 opacity-10">
                        <svg
                            class="w-24 h-24"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                            />
                        </svg>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-300 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-600"
                >
                    <div class="flex items-start justify-between">
                        <p
                            class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
                        >
                            Omzet Bulan Ini
                        </p>
                        <span
                            class="text-[10px] px-2 py-0.5 rounded-full font-bold"
                            :class="
                                summary.revenue_progress >= 100
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700'
                            "
                        >
                            {{ summary.revenue_progress }}%
                        </span>
                    </div>
                    <h3
                        class="mt-2 text-2xl font-black text-gray-800 truncate dark:text-white"
                        :title="formatRupiah(summary.current_revenue)"
                    >
                        {{ formatK(summary.current_revenue) }}
                    </h3>

                    <div
                        class="w-full h-2 mt-4 overflow-hidden bg-gray-100 border border-gray-200 rounded-full dark:bg-gray-700"
                    >
                        <div
                            class="h-2 transition-all duration-1000 bg-blue-600 rounded-full"
                            :style="{
                                width:
                                    Math.min(summary.revenue_progress, 100) +
                                    '%',
                            }"
                        ></div>
                    </div>
                    <p class="mt-2 text-[10px] text-gray-400 text-right">
                        Target (Rata-rata): {{ formatK(summary.avg_revenue) }}
                    </p>
                </div>

                <div
                    class="p-6 bg-white border border-gray-300 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-600"
                >
                    <p
                        class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Hutang Jatuh Tempo
                    </p>
                    <h3
                        class="mt-2 text-2xl font-black text-red-600 truncate"
                        :title="formatRupiah(summary.debt_due_soon)"
                    >
                        {{ formatK(summary.debt_due_soon) }}
                    </h3>
                    <div
                        class="flex items-center gap-2 mt-4 text-xs font-medium text-red-500"
                    >
                        <svg
                            class="w-4 h-4 animate-pulse"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <span>Dalam 7 hari ke depan</span>
                    </div>
                </div>

                <div
                    class="p-6 bg-white border border-gray-300 shadow-lg rounded-2xl dark:bg-gray-800 dark:border-gray-600"
                >
                    <p
                        class="text-[10px] font-bold tracking-widest text-gray-500 uppercase"
                    >
                        Est. Laba Bersih
                    </p>
                    <h3
                        class="mt-2 text-2xl font-black truncate text-lime-600"
                        :title="formatRupiah(summary.net_profit)"
                    >
                        {{ formatK(summary.net_profit) }}
                    </h3>
                    <p class="mt-4 text-xs text-gray-500">
                        Margin Bersih:
                        <span class="font-bold text-lime-700"
                            >{{ summary.net_margin }}%</span
                        >
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="(group, idx) in reportGroups"
                    :key="idx"
                    class="space-y-4"
                >
                    <div class="flex items-center gap-2 px-1">
                        <h3
                            class="text-sm font-black tracking-wider text-gray-400 uppercase"
                        >
                            {{ group.title }}
                        </h3>
                        <div
                            class="flex-1 h-px bg-gray-200 dark:bg-gray-700"
                        ></div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <Link
                            v-for="(item, i) in group.items"
                            :key="i"
                            :href="item.route !== '#' ? route(item.route) : '#'"
                            class="relative p-5 transition-all duration-300 bg-white border border-gray-300 shadow-md group dark:bg-gray-800 rounded-xl dark:border-gray-600 hover:shadow-xl hover:-translate-y-1"
                            :class="[
                                group.borderColor,
                                item.route === '#'
                                    ? 'opacity-60 grayscale cursor-not-allowed'
                                    : 'cursor-pointer',
                            ]"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div
                                    class="flex items-center justify-center w-10 h-10 text-xl transition-colors border rounded-lg group-hover:scale-110"
                                    :class="group.color"
                                >
                                    {{ item.icon }}
                                </div>

                                <div
                                    v-if="item.route !== '#'"
                                    class="text-gray-300 transition-colors group-hover:text-gray-600 dark:group-hover:text-white"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5l7 7-7 7"
                                        ></path>
                                    </svg>
                                </div>
                            </div>

                            <h4
                                class="font-bold text-gray-800 transition-colors dark:text-white group-hover:text-blue-600"
                            >
                                {{ item.title }}
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400"
                            >
                                {{ item.desc }}
                            </p>
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
