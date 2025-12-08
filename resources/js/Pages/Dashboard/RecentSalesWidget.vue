<script setup>
import { Link } from "@inertiajs/vue3";

// Menerima data penjualan dari parent
defineProps({
    sales: {
        type: Array,
        default: () => [],
    },
});

// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return {
        full: date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        }),
        time: date.toLocaleTimeString("id-ID", {
            hour: "2-digit",
            minute: "2-digit",
        }),
    };
};
</script>

<template>
    <div
        class="flex flex-col mb-8 overflow-hidden bg-white border border-gray-200 shadow-sm shadow-gray-200 dark:bg-gray-800 dark:border-gray-700 rounded-2xl"
    >
        <div
            class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
        >
            <h3
                class="flex items-center gap-2 font-bold text-gray-800 dark:text-white"
            >
                <div
                    class="flex items-center justify-center w-10 h-10 rounded-lg text-lime-600 bg-lime-50 dark:bg-lime-900/20 dark:text-lime-400"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                        ></path>
                    </svg>
                </div>
                Transaksi Terakhir
            </h3>
            <Link
                :href="route('sales.index')"
                class="text-xs font-bold text-gray-500 bg-gray-50 dark:bg-gray-900/50 hover:text-lime-600 dark:text-lime-400 dark:hover:text-lime-400 transation"
            >
                Lihat Semua &rarr;
            </Link>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead
                    class="text-xs font-bold uppercase border-t-2 border-b-2 text-lime-800 border-lime-200/50 bg-lime-200/20 dark:text-gray-400 dark:bg-gray-700/50 dark:border-gray-700"
                >
                    <tr>
                        <th class="px-6 py-3">Tanggal</th>
                        <th class="px-6 py-3">Summary (Qty)</th>
                        <th class="px-6 py-3 text-right">Total Omzet</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr
                        v-for="sale in sales"
                        :key="sale.id"
                        class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                <span
                                    class="font-bold text-gray-700 dark:text-gray-200"
                                    >{{
                                        formatDate(sale.transaction_date).full
                                    }}</span
                                >
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center flex-shrink-0 w-8 h-8 border rounded-lg border-lime-100 text-lime-600 bg-lime-50 dark:bg-lime-900/30 dark:text-lime-400 dark:border-lime-800"
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
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                        ></path>
                                    </svg>
                                </div>

                                <div>
                                    <p
                                        class="text-xs font-bold text-gray-800 dark:text-gray-100"
                                    >
                                        {{ sale.financial_summary.item_count }}
                                        Jenis
                                        <span class="mx-1 text-gray-400"
                                            >|</span
                                        >
                                        {{ sale.financial_summary.total_qty }}
                                        Pcs
                                    </p>

                                    <p
                                        class="text-[13px] text-gray-400 font-mono mt-0.5"
                                    >
                                        #{{ sale.reference_no }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <span
                                class="font-bold text-gray-900 dark:text-white"
                            >
                                {{
                                    formatRupiah(
                                        sale.total_revenue || sale.total_price
                                    )
                                }}
                            </span>
                        </td>
                    </tr>

                    <tr v-if="sales.length === 0">
                        <td
                            colspan="3"
                            class="px-6 py-10 text-center text-gray-400 dark:text-gray-300"
                        >
                            <div class="flex flex-col items-center gap-2">
                                <svg
                                    class="w-8 h-8 opacity-50"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    ></path>
                                </svg>
                                <span>Belum ada transaksi hari ini.</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
