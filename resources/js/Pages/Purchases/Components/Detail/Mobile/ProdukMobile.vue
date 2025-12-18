<script setup>
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
            class="p-3 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 rounded-xl dark:border-gray-800"
        >
            <div class="flex gap-3 mb-3">
                <div
                    class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center text-[10px] font-bold text-gray-400 shrink-0"
                >
                    {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                </div>
                <div class="overflow-hidden">
                    <h4
                        class="text-sm font-bold text-gray-800 truncate dark:text-gray-100"
                    >
                        {{ item.product?.name }}
                    </h4>
                    <p class="text-[10px] text-gray-500">
                        SKU: {{ item.product?.sku || "-" }} &bull;
                        {{ item.unit?.name }}
                    </p>
                </div>
            </div>

            <div
                class="grid grid-cols-3 gap-px overflow-hidden bg-gray-200 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-700"
            >
                <div class="p-2 text-center bg-gray-50 dark:bg-gray-800">
                    <span
                        class="block text-[9px] text-gray-400 uppercase font-bold tracking-wider"
                        >Order</span
                    >
                    <span
                        class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mt-0.5"
                    >
                        {{ item.quantity }}
                    </span>
                </div>

                <div
                    :class="[
                        'p-2 text-center relative',
                        item.quantity_received < item.quantity
                            ? 'bg-red-50 dark:bg-red-900/20'
                            : 'bg-white dark:bg-gray-800',
                    ]"
                >
                    <span
                        class="block text-[9px] text-gray-400 uppercase font-bold tracking-wider"
                        >Datang</span
                    >
                    <span
                        :class="[
                            'block text-xs font-bold mt-0.5',
                            item.quantity_received < item.quantity
                                ? 'text-red-600'
                                : item.quantity_received > item.quantity
                                ? 'text-yellow-600'
                                : 'text-lime-600',
                        ]"
                    >
                        {{ item.quantity_received ?? "-" }}
                    </span>

                    <span
                        v-if="
                            item.quantity_received &&
                            item.quantity_received != item.quantity
                        "
                        class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-red-500"
                    >
                    </span>
                </div>

                <div class="p-2 text-right bg-gray-50 dark:bg-gray-800">
                    <span
                        class="block text-[9px] text-gray-400 uppercase font-bold tracking-wider"
                        >@ Harga</span
                    >
                    <span
                        class="block text-xs font-medium text-gray-600 dark:text-gray-400 mt-0.5"
                    >
                        {{ rp(item.purchase_price) }}
                    </span>
                </div>
            </div>

            <div
                class="flex items-center justify-between pt-2 mt-3 border-t border-gray-100 border-dashed dark:border-gray-800"
            >
                <div>
                    <span
                        v-if="item.quantity_received < item.quantity"
                        class="text-[10px] text-red-600 bg-red-50 px-1.5 py-0.5 rounded font-medium"
                    >
                        Kurang
                        {{ item.quantity - item.quantity_received }}
                    </span>
                    <span
                        v-else-if="item.quantity_received > item.quantity"
                        class="text-[10px] text-yellow-600 bg-yellow-50 px-1.5 py-0.5 rounded font-medium"
                    >
                        Lebih
                        {{ item.quantity_received - item.quantity }}
                    </span>
                    <span v-else class="text-[10px] text-gray-400"
                        >Sesuai PO</span
                    >
                </div>

                <div class="text-right">
                    <span class="text-[10px] text-gray-400 mr-2"
                        >Subtotal:</span
                    >
                    <span
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                        >{{ rp(item.total_price) }}</span
                    >
                </div>
            </div>
        </div>
    </div>
</template>
