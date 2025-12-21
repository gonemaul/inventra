<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    purchase: Object,
});
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);
const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          })
        : "-";
</script>
<template>
    <div class="space-y-3">
        <div
            v-for="item in purchase.items"
            :key="item.id"
            :class="[
                'relative p-4 transition-all bg-white border border-gray-100 shadow-sm group dark:bg-gray-900 rounded-2xl dark:border-gray-800 hover:shadow-md',
                item.quantity < item.product_snapshot.quantity
                    ? 'border-2 border-red-500'
                    : item.quantity > item.product_snapshot.quantity
                    ? 'border-2 border-yellow-600'
                    : 'border-2 border-lime-600',
            ]"
        >
            <div class="flex gap-3 mb-3">
                <div
                    class="flex items-center justify-center w-12 h-12 text-xs font-bold text-gray-400 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 shrink-0 dark:border-gray-700"
                >
                    {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                </div>

                <div class="flex-1 overflow-hidden">
                    <div class="flex items-start justify-between">
                        <h4
                            class="text-sm font-bold leading-tight text-gray-800 truncate dark:text-gray-100"
                        >
                            {{ item.product?.name }}
                        </h4>
                        <button
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-4 h-4"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"
                                />
                            </svg>
                        </button>
                    </div>

                    <p class="text-[11px] text-gray-500 font-medium mt-0.5">
                        {{ item.product?.code || "-" }}
                        <span class="mx-1 text-gray-300">|</span>
                        {{ item.product_snapshot.unit }}
                    </p>

                    <div class="flex gap-1 mt-1.5 flex-wrap">
                        <span
                            v-if="item.product_snapshot?.brand"
                            class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                        >
                            {{ item.product_snapshot.brand }}
                        </span>
                        <span
                            v-if="item.product_snapshot?.category"
                            class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                        >
                            {{ item.product_snapshot.category }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="mb-3 overflow-hidden border border-gray-100 bg-gray-50 dark:bg-gray-800/50 rounded-xl dark:border-gray-700"
            >
                <div
                    class="grid grid-cols-2 border-b border-gray-100 dark:border-gray-700"
                >
                    <div
                        class="py-1.5 text-center text-[9px] font-bold text-gray-700 uppercase tracking-wider bg-gray-200 dark:bg-gray-800"
                    >
                        Order (PO)
                    </div>
                    <div
                        class="py-1.5 text-center text-[9px] font-bold text-gray-600 uppercase tracking-wider bg-white dark:bg-gray-900"
                    >
                        Realisasi (Datang)
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 text-xs border-b border-gray-100 dark:border-gray-700"
                >
                    <div
                        class="p-2 text-center text-gray-600 bg-gray-200 border-r border-gray-100 dark:text-gray-400 dark:border-gray-700"
                    >
                        <span class="text-[9px] text-gray-500 block mb-0.5"
                            >Qty</span
                        >
                        {{ item.product_snapshot.quantity }}
                    </div>

                    <div
                        :class="[
                            'p-2 text-center font-bold bg-white dark:bg-gray-900',
                            item.quantity < item.product_snapshot.quantity
                                ? 'text-red-600'
                                : item.quantity > item.product_snapshot.quantity
                                ? 'text-yellow-600'
                                : 'text-lime-600',
                        ]"
                    >
                        <span
                            class="text-[9px] text-gray-400 block mb-0.5 font-normal"
                            >Qty</span
                        >
                        {{ item.quantity ?? "-" }}

                        <span
                            v-if="
                                item.quantity < item.product_snapshot.quantity
                            "
                            class="ml-1 text-[9px]"
                            >↓</span
                        >
                        <span
                            v-else-if="
                                item.quantity > item.product_snapshot.quantity
                            "
                            class="ml-1 text-[9px]"
                            >↑</span
                        >
                    </div>
                </div>

                <div class="grid grid-cols-2 text-xs">
                    <div
                        class="p-2 text-center text-gray-600 bg-gray-200 border-r border-gray-100 dark:text-gray-400 dark:border-gray-700"
                    >
                        <span class="text-[9px] text-gray-400 block mb-0.5"
                            >@ Harga PO</span
                        >
                        {{ rp(item.product_snapshot.purchase_price) }}
                    </div>

                    <div
                        :class="[
                            'p-2 text-center font-bold bg-white dark:bg-gray-900',
                            item.purchase_price >
                            item.product_snapshot.purchase_price
                                ? 'text-red-600'
                                : item.purchase_price <
                                  item.product_snapshot.purchase_price
                                ? 'text-green-600'
                                : 'text-gray-700 dark:text-gray-300',
                        ]"
                    >
                        <span
                            class="text-[9px] text-gray-400 block mb-0.5 font-normal"
                            >@ Harga Nota</span
                        >
                        {{
                            item.purchase_price ? rp(item.purchase_price) : "-"
                        }}

                        <span
                            v-if="
                                item.purchase_price >
                                item.product_snapshot.purchase_price
                            "
                            class="ml-1 text-[9px]"
                            >▲</span
                        >
                        <span
                            v-else-if="
                                item.purchase_price <
                                    item.product_snapshot.purchase_price &&
                                item.purchase_price > 0
                            "
                            class="ml-1 text-[9px]"
                            >▼</span
                        >
                    </div>
                </div>
            </div>

            <div class="flex items-end justify-between">
                <div class="flex flex-col gap-1.5">
                    <div class="flex items-center gap-1.5">
                        <Link
                            @click="
                                route('purchases.linkInvoiceItems', {
                                    purchase: purchase.id,
                                    invoice: item.purchase_invoice_id,
                                })
                            "
                            v-if="item.purchase_invoice_id"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-100 text-[10px] font-bold dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            Terhubung Nota
                        </Link>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 border border-gray-200 text-[10px] font-medium dark:bg-gray-800 dark:border-gray-700"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            Pending Nota
                        </span>
                    </div>

                    <div class="flex flex-wrap gap-1">
                        <span
                            v-if="item.quantity_received < item.quantity"
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 rounded dark:bg-red-900/30 dark:text-red-400"
                        >
                            Qty Kurang
                        </span>
                        <span
                            v-else-if="item.quantity_received > item.quantity"
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-yellow-100 text-yellow-700 rounded dark:bg-yellow-900/30 dark:text-yellow-400"
                        >
                            Qty Lebih
                        </span>

                        <span
                            v-if="item.actual_price > item.purchase_price"
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-orange-100 text-orange-700 rounded dark:bg-orange-900/30 dark:text-orange-400"
                        >
                            Harga Naik
                        </span>
                        <span
                            v-else-if="
                                item.actual_price < item.purchase_price &&
                                item.actual_price > 0
                            "
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-green-100 text-green-700 rounded dark:bg-green-900/30 dark:text-green-400"
                        >
                            Harga Turun
                        </span>

                        <span
                            v-if="
                                item.quantity_received == item.quantity &&
                                (!item.actual_price ||
                                    item.actual_price == item.purchase_price)
                            "
                            class="px-1.5 py-0.5 text-[9px] font-bold bg-lime-100 text-lime-700 rounded dark:bg-lime-900/30 dark:text-lime-400"
                        >
                            Sesuai
                        </span>
                    </div>
                </div>

                <div class="text-right">
                    <span
                        class="block text-[10px] text-gray-400 uppercase tracking-wide font-medium"
                        >Subtotal Akhir</span
                    >
                    <span
                        class="text-sm font-bold text-gray-800 dark:text-white"
                    >
                        {{
                            rp(
                                (item.actual_price || item.purchase_price) *
                                    (item.quantity_received || item.quantity)
                            )
                        }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
