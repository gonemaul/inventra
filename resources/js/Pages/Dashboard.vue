<script setup>
import { Head, Link, router } from "@inertiajs/vue3";
import { computed } from "vue";
// Gunakan Layout Utama Anda
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    stats: Object,
    health: Object,
    cashflow: Object,
    insights: Array,
    chart_data: Array,
    recent_sales: Array,
});

// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
    });

// Scale Chart Bar
const maxChartValue = computed(() => {
    const max = Math.max(...props.chart_data.map((d) => d.value));
    return max === 0 ? 1 : max;
});

// Action Handler
const handleInsight = (url) => {
    if (url) router.visit(url);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8 bg-gray-50">
            <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-12">
                <div
                    class="grid grid-cols-1 gap-4 md:col-span-8 sm:grid-cols-2"
                >
                    <div
                        class="relative p-6 overflow-hidden text-white shadow-lg bg-gradient-to-br from-emerald-600 to-green-500 rounded-2xl group"
                    >
                        <div class="relative z-10">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p
                                        class="mb-1 text-sm font-medium text-green-100"
                                    >
                                        Profit Bersih Hari Ini
                                    </p>
                                    <h2
                                        class="text-3xl font-bold tracking-tight"
                                    >
                                        {{ formatRupiah(stats.profit) }}
                                    </h2>
                                </div>
                                <div
                                    class="p-2 rounded-lg bg-white/20 backdrop-blur-sm"
                                >
                                    <svg
                                        class="w-6 h-6 text-white"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-2 mt-4 text-sm text-green-50"
                            >
                                <span
                                    class="bg-white/20 px-2 py-0.5 rounded text-xs font-bold"
                                    >{{ stats.transactions }} Transaksi</span
                                >
                                <span
                                    >Omzet:
                                    {{ formatRupiah(stats.revenue) }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="absolute w-40 h-40 transition duration-700 bg-white rounded-full -right-10 -bottom-10 opacity-10 blur-2xl group-hover:opacity-20"
                        ></div>
                    </div>

                    <div
                        class="flex flex-col justify-between p-6 bg-white border border-gray-100 shadow-sm rounded-2xl"
                    >
                        <div>
                            <p class="mb-1 text-sm font-medium text-gray-500">
                                Proyeksi Cashflow (7 Hari)
                            </p>
                            <h2
                                class="text-2xl font-bold tracking-tight"
                                :class="
                                    cashflow.balance >= 0
                                        ? 'text-gray-800'
                                        : 'text-red-600'
                                "
                            >
                                {{ formatRupiah(cashflow.balance) }}
                            </h2>
                        </div>
                        <div class="mt-4">
                            <div
                                class="w-full h-2 mb-2 overflow-hidden bg-gray-100 rounded-full"
                            >
                                <div
                                    class="h-2 bg-blue-500 rounded-full"
                                    style="width: 70%"
                                ></div>
                            </div>
                            <p
                                class="inline-block px-2 py-1 text-xs font-medium text-gray-600 bg-gray-100 rounded"
                            >
                                {{ cashflow.message }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="relative flex flex-col items-center justify-center p-6 overflow-hidden text-center bg-white border border-gray-100 shadow-sm md:col-span-4 rounded-2xl"
                >
                    <h3
                        class="mb-4 text-xs font-bold tracking-widest text-gray-400 uppercase"
                    >
                        Skor Kesehatan Toko
                    </h3>

                    <div class="relative w-32 h-32">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                stroke="currentColor"
                                stroke-width="12"
                                fill="transparent"
                                class="text-gray-100"
                            />
                            <circle
                                cx="64"
                                cy="64"
                                r="56"
                                stroke="currentColor"
                                stroke-width="12"
                                fill="transparent"
                                :stroke-dasharray="351"
                                :stroke-dashoffset="
                                    351 - (351 * health.score) / 100
                                "
                                class="transition-all duration-1000 ease-out"
                                :class="health.color"
                                stroke-linecap="round"
                            />
                        </svg>
                        <div
                            class="absolute top-0 left-0 flex flex-col items-center justify-center w-full h-full"
                        >
                            <span class="text-4xl font-black text-gray-800">{{
                                health.score
                            }}</span>
                        </div>
                    </div>

                    <p class="mt-4 text-sm font-bold" :class="health.color">
                        {{ health.status }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-3">
                <div
                    class="p-6 bg-white border border-gray-100 shadow-sm lg:col-span-2 rounded-2xl"
                >
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-gray-800">
                            Tren Penjualan Minggu Ini
                        </h3>
                    </div>

                    <div class="flex items-end justify-between h-56 gap-3">
                        <div
                            v-for="(data, index) in chart_data"
                            :key="index"
                            class="relative flex flex-col items-center w-full group"
                        >
                            <div
                                class="absolute -top-10 opacity-0 group-hover:opacity-100 transition bg-gray-900 text-white text-[10px] py-1 px-2 rounded pointer-events-none z-10"
                            >
                                {{ formatRupiah(data.value) }}
                            </div>

                            <div
                                class="w-full max-w-[40px] bg-blue-50 rounded-t-xl relative overflow-hidden transition-all duration-300 group-hover:bg-blue-100"
                                :style="{
                                    height:
                                        (data.value / maxChartValue) * 100 +
                                        '%',
                                }"
                            >
                                <div
                                    class="absolute bottom-0 w-full h-full transition bg-blue-500 opacity-80 rounded-t-xl group-hover:opacity-100"
                                ></div>
                            </div>

                            <p class="mt-3 text-xs font-medium text-gray-400">
                                {{ data.day }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-col h-full bg-white border border-gray-100 shadow-sm rounded-2xl"
                >
                    <div
                        class="flex items-center justify-between p-5 border-b border-gray-100 bg-gray-50/50 rounded-t-2xl"
                    >
                        <h3
                            class="flex items-center gap-2 font-bold text-gray-800"
                        >
                            <span class="relative flex w-2 h-2">
                                <span
                                    class="absolute inline-flex w-full h-full bg-red-400 rounded-full opacity-75 animate-ping"
                                ></span>
                                <span
                                    class="relative inline-flex w-2 h-2 bg-red-500 rounded-full"
                                ></span>
                            </span>
                            Smart Assistant
                        </h3>
                        <span
                            class="px-2 py-1 text-xs font-bold text-gray-600 bg-gray-200 rounded-full"
                            >{{ insights.length }}</span
                        >
                    </div>

                    <div
                        class="p-4 flex-1 overflow-y-auto max-h-[300px] space-y-3 custom-scrollbar"
                    >
                        <div
                            v-for="insight in insights"
                            :key="insight.id"
                            @click="handleInsight(insight.action_url)"
                            class="relative p-3 overflow-hidden transition-all border cursor-pointer group rounded-xl hover:shadow-md"
                            :class="{
                                'bg-red-50 border-red-100 hover:border-red-300':
                                    insight.severity === 'critical',
                                'bg-yellow-50 border-yellow-100 hover:border-yellow-300':
                                    insight.severity === 'warning',
                                'bg-blue-50 border-blue-100 hover:border-blue-300':
                                    insight.severity === 'info',
                            }"
                        >
                            <div class="flex gap-3">
                                <div class="mt-1">
                                    <span
                                        v-if="insight.severity === 'critical'"
                                        class="text-xl"
                                        >🚨</span
                                    >
                                    <span
                                        v-else-if="
                                            insight.severity === 'warning'
                                        "
                                        class="text-xl"
                                        >⚡</span
                                    >
                                    <span v-else class="text-xl">💡</span>
                                </div>
                                <div>
                                    <h4
                                        class="text-sm font-bold"
                                        :class="
                                            insight.severity === 'critical'
                                                ? 'text-red-800'
                                                : 'text-gray-800'
                                        "
                                    >
                                        {{ insight.title }}
                                    </h4>
                                    <p
                                        class="mt-1 text-xs leading-relaxed text-gray-600"
                                    >
                                        {{ insight.message }}
                                    </p>
                                    <div
                                        class="mt-2 text-[10px] font-bold uppercase tracking-wider opacity-60 group-hover:opacity-100 transition"
                                    >
                                        Klik untuk proses &rarr;
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="insights.length === 0"
                            class="flex flex-col items-center justify-center h-full py-8 text-center opacity-50"
                        >
                            <svg
                                class="w-12 h-12 mb-2 text-gray-300"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            <p class="text-sm font-medium">
                                Semua aman terkendali
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden bg-white border border-gray-100 shadow-sm rounded-2xl"
            >
                <div
                    class="flex items-center justify-between p-6 border-b border-gray-100"
                >
                    <h3 class="font-bold text-gray-800">Transaksi Terakhir</h3>
                    <Link
                        :href="route('sales.index')"
                        class="text-sm font-medium text-blue-600 hover:underline"
                        >Lihat Semua</Link
                    >
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs font-semibold text-gray-500 uppercase bg-gray-50"
                        >
                            <tr>
                                <th class="px-6 py-4">Tanggal</th>
                                <th class="px-6 py-4">Pelanggan</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="sale in recent_sales"
                                :key="sale.id"
                                class="transition hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    {{ formatDate(sale.transaction_date) }}
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    {{ sale.customer_name || "Umum" }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-2 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-full"
                                        >Selesai</span
                                    >
                                </td>
                                <td
                                    class="px-6 py-4 font-bold text-right text-gray-800"
                                >
                                    {{ formatRupiah(sale.total_revenue) }}
                                </td>
                            </tr>
                            <tr v-if="recent_sales.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-8 text-center text-gray-400"
                                >
                                    Belum ada transaksi hari ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scrollbar Halus untuk Widget Assistant */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>
