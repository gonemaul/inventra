<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    purchases: Object, // { total_spend_month: 0, count_pending: 0, recent: [] }
    finance: Object, // { total_debt: 0, due_soon_count: 0, recent_bills: [] }
});

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
    });
</script>

<template>
    <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-2">
        <div
            class="flex flex-col overflow-hidden bg-white border-2 border-blue-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
        >
            <div
                class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700 bg-blue-200/30 dark:bg-blue-900/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="p-2 text-blue-600 bg-blue-200 rounded-lg dark:bg-blue-800 dark:text-blue-200"
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
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100">
                            Pembelian & Stok
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Aktivitas kulakan bulan ini
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('purchases.create')"
                    class="p-2 text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700"
                    title="Tambah Pembelian"
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
                            d="M12 4v16m8-8H4"
                        ></path>
                    </svg>
                </Link>
            </div>

            <div
                class="grid grid-cols-2 border-b border-gray-100 divide-x divide-gray-100 dark:border-gray-700 dark:divide-gray-700"
            >
                <div class="p-4 text-center">
                    <p
                        class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase"
                    >
                        Pengeluaran
                    </p>
                    <p
                        class="text-lg font-bold text-gray-800 truncate dark:text-white"
                    >
                        {{ formatRupiah(purchases.total_spend_month) }}
                    </p>
                </div>
                <div class="p-4 text-center">
                    <p
                        class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase"
                    >
                        Pesanan Proses
                    </p>
                    <p
                        class="text-lg font-bold text-blue-600 dark:text-blue-400"
                    >
                        {{ purchases.count_pending }}
                        <span class="text-xs font-normal text-gray-400"
                            >PO</span
                        >
                    </p>
                </div>
            </div>

            <div class="flex-1 p-0 overflow-hidden">
                <div
                    v-if="purchases.recent.length > 0"
                    class="divide-y divide-gray-50 dark:divide-gray-700"
                >
                    <div
                        v-for="po in purchases.recent"
                        :key="po.id"
                        class="flex items-center justify-between p-4 transition hover:bg-gray-50 dark:hover:bg-gray-700/50"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-2 h-2 rounded-full"
                                :class="
                                    po.status === 'selesai'
                                        ? 'bg-green-500'
                                        : 'bg-yellow-500'
                                "
                            ></div>
                            <div>
                                <Link
                                    :href="route('purchases.show', po.id)"
                                    class="text-sm font-bold text-gray-700 transition dark:hover:text-lime-500 hover:text-lime-500 dark:text-gray-200"
                                >
                                    {{ po.reference_no }}
                                </Link>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    {{ po.supplier?.name }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p
                                class="text-sm font-bold text-gray-800 dark:text-gray-200"
                            >
                                {{ formatRupiah(po.total) }}
                            </p>
                            <span
                                class="text-[10px] px-2 py-0.5 rounded-full uppercase font-bold"
                                :class="
                                    po.status === 'selesai'
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
                                "
                            >
                                {{ po.status }}
                            </span>
                        </div>
                    </div>
                </div>
                <div v-else class="p-6 text-sm text-center text-gray-400">
                    Belum ada pembelian bulan ini.
                </div>
            </div>

            <Link
                :href="route('purchases.index')"
                class="p-3 text-xs font-bold text-center text-gray-500 transition border-t border-gray-100 bg-gray-50 dark:bg-gray-900/50 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 dark:border-gray-700"
            >
                Lihat Semua Riwayat &rarr;
            </Link>
        </div>

        <div
            class="flex flex-col overflow-hidden bg-white border-2 border-red-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
        >
            <div
                class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-gray-700 bg-red-100/30 dark:bg-red-900/10"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="p-2 text-red-600 bg-red-200 rounded-lg dark:bg-red-800 dark:text-red-200"
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
                                d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"
                            ></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800 dark:text-gray-100">
                            Tagihan & Hutang
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Kewajiban pembayaran supplier
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('finance.index')"
                    class="p-2 text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 hover:border-red-300 dark:text-gray-300"
                    title="Ke Menu Keuangan"
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
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                        ></path>
                    </svg>
                </Link>
            </div>

            <div
                class="grid grid-cols-2 border-b border-gray-100 divide-x divide-gray-100 dark:border-gray-700 dark:divide-gray-700"
            >
                <div class="p-4 text-center">
                    <p
                        class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase"
                    >
                        Total Sisa Hutang
                    </p>
                    <p
                        class="text-lg font-bold text-red-600 truncate dark:text-red-400"
                    >
                        {{ formatRupiah(finance.total_debt) }}
                    </p>
                </div>
                <div class="relative p-4 overflow-hidden text-center">
                    <div
                        v-if="finance.due_soon_count > 0"
                        class="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full animate-ping"
                    ></div>
                    <p
                        class="mb-1 text-xs font-bold tracking-wider text-gray-400 uppercase"
                    >
                        Jatuh Tempo (7 Hari)
                    </p>
                    <p class="text-lg font-bold text-gray-800 dark:text-white">
                        {{ finance.due_soon_count }}
                        <span class="text-xs font-normal text-gray-400"
                            >Nota</span
                        >
                    </p>
                </div>
            </div>

            <div class="flex-1 p-0 overflow-hidden">
                <div
                    v-if="finance.recent_bills.length > 0"
                    class="divide-y divide-gray-50 dark:divide-gray-700"
                >
                    <div
                        v-for="bill in finance.recent_bills"
                        :key="bill.id"
                        class="flex items-center justify-between p-4 transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                        @click="$inertia.visit(route('finance.show', bill.id))"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex flex-col items-center justify-center w-10 h-10 text-red-600 border border-red-100 rounded bg-red-50 dark:bg-red-900/30 dark:border-red-800 dark:text-red-400"
                            >
                                <span
                                    class="text-[9px] font-bold uppercase leading-none"
                                    >{{
                                        new Date(bill.due_date).toLocaleString(
                                            "default",
                                            { month: "short" }
                                        )
                                    }}</span
                                >
                                <span class="text-sm font-bold leading-none">{{
                                    new Date(bill.due_date).getDate()
                                }}</span>
                            </div>

                            <div>
                                <Link
                                    :href="route('finance.show', bill.id)"
                                    class="text-sm font-bold text-gray-700 transition hover:text-lime-500 dark:hover:text-lime-500 dark:text-gray-200 line-clamp-1"
                                >
                                    #{{ bill.invoice_number }}
                                </Link>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400 line-clamp-1"
                                >
                                    {{ bill.supplier?.name }}
                                </p>
                            </div>
                        </div>

                        <div class="text-right">
                            <p
                                class="text-sm font-bold text-red-600 dark:text-red-400"
                            >
                                {{
                                    formatRupiah(
                                        bill.total_amount - bill.amount_paid
                                    )
                                }}
                            </p>
                            <p
                                class="text-[10px] text-gray-400"
                                v-if="new Date(bill.due_date) < new Date()"
                            >
                                Lewat Jatuh Tempo!
                            </p>
                            <p class="text-[10px] text-gray-400" v-else>
                                Sisa Tagihan
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center p-6 text-center text-gray-400"
                >
                    <svg
                        class="w-10 h-10 mb-2 opacity-20"
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
                    <p class="text-sm">Tidak ada tagihan mendesak.</p>
                </div>
            </div>

            <Link
                :href="route('finance.index')"
                class="p-3 text-xs font-bold text-center text-gray-500 transition border-t border-gray-100 bg-gray-50 dark:bg-gray-900/50 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 dark:border-gray-700"
            >
                Kelola Hutang & Pembayaran &rarr;
            </Link>
        </div>
    </div>
</template>
