<script setup>
import { ref, computed } from "vue";
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

// --- SMART SEARCH ---
const searchKeywords = ref("");
const searchWords = computed(() =>
    searchKeywords.value.toLowerCase().split(" ").filter((w) => w)
);
const filteredItems = computed(() => {
    if (!props.purchase?.items) return [];
    if (searchWords.value.length === 0) return props.purchase.items;
    return props.purchase.items.filter((item) => {
        const text = `${(item.product?.name || "").toLowerCase()} ${(item.product?.code || "").toLowerCase()}`;
        return searchWords.value.every((w) => text.includes(w));
    });
});

const blurSearchInput = () => {
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        document.activeElement.blur();
    }
};
</script>
<template>
    <div class="space-y-3" @touchmove="blurSearchInput">
        <!-- SMART SEARCH BAR -->
        <div class="relative">
            <svg class="absolute w-4 h-4 text-gray-400 -translate-y-1/2 left-3 top-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input
                v-model="searchKeywords"
                type="text"
                placeholder="Cari nama / kode produk..."
                class="w-full pl-10 pr-9 py-2.5 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition-all"
            />
            <button v-if="searchKeywords" @click="searchKeywords = ''" class="absolute w-5 h-5 text-gray-400 -translate-y-1/2 right-3 top-1/2 hover:text-gray-600">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <p v-if="searchKeywords && filteredItems.length === 0" class="py-6 text-xs text-center text-gray-400">Tidak ada produk yang cocok dengan "<strong>{{ searchKeywords }}</strong>"</p>

        <div
            v-for="item in filteredItems"
            :key="item.id"
            :class="[
                'overflow-hidden transition-all bg-white shadow-sm group dark:bg-gray-900 rounded-2xl hover:shadow-md',
                item.purchase_invoice_id
                    ? item.quantity < item.product_snapshot?.quantity
                        ? 'border-2 border-red-500'
                        : item.quantity > item.product_snapshot?.quantity
                        ? 'border-2 border-yellow-500'
                        : 'border-2 border-lime-500'
                    : 'border border-gray-100 dark:border-gray-800',
            ]"
        >
            <!-- HEADER: Gambar 1:1 + Info Produk -->
            <div class="flex gap-3 p-3 pb-0">
                <!-- 1:1 image -->
                <div class="relative w-16 shrink-0 aspect-square overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                    <img
                        v-if="item.product?.image_url"
                        :src="item.product.image_url"
                        :alt="item.product?.name"
                        class="object-cover w-full h-full"
                    />
                    <div
                        v-else
                        class="flex items-center justify-center w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700"
                    >
                        <span class="text-lg font-black text-gray-300 dark:text-gray-600 uppercase select-none">
                            {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                        </span>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="flex-1 overflow-hidden pt-1">
                    <h4 class="text-sm font-bold leading-tight text-gray-800 line-clamp-2 dark:text-gray-100">
                        {{ item.product?.name }}
                    </h4>
                    <p class="text-[11px] text-gray-500 font-medium mt-1">
                        {{ item.product?.code || "-" }}
                        <span class="mx-1 text-gray-300">|</span>
                        {{ item.product_snapshot?.unit }}
                    </p>
                    <div class="flex gap-1 mt-1 flex-wrap">
                        <span
                            v-if="item.product_snapshot?.brand"
                            class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                        >
                            {{ item.product_snapshot?.brand }}
                        </span>
                        <span
                            v-if="item.product_snapshot?.category"
                            class="text-[9px] px-1.5 py-0.5 bg-gray-100 text-gray-600 rounded border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
                        >
                            {{ item.product_snapshot?.category }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- BODY: Data tabel PO vs Realisasi -->
            <div class="mt-3 mx-3 overflow-hidden border border-gray-100 bg-gray-50 dark:bg-gray-800/50 rounded-xl dark:border-gray-700">
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
                        {{ item.product_snapshot?.quantity }}
                    </div>

                    <div
                        :class="[
                            'p-2 text-center font-bold bg-white dark:bg-gray-900',
                            item.purchase_invoice_id
                                ? item.quantity <
                                  item.product_snapshot?.quantity
                                    ? 'text-red-600'
                                    : item.quantity >
                                      item.product_snapshot?.quantity
                                    ? 'text-yellow-600'
                                    : 'text-lime-600'
                                : 'text-gray-800 dark:text-gray-200',
                        ]"
                    >
                        <span
                            class="text-[9px] text-gray-400 block mb-0.5 font-normal"
                            >Qty</span
                        >
                        {{ purchase.received_at ? item.quantity : "-" }}

                        <span
                            v-if="
                                purchase.received_at &&
                                item.quantity < item.product_snapshot?.quantity
                            "
                            class="ml-1 text-[9px]"
                            >↓</span
                        >
                        <span
                            v-else-if="
                                purchase.received_at &&
                                item.quantity > item.product_snapshot?.quantity
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
                        {{ rp(item.product_snapshot?.purchase_price) }}
                    </div>

                    <div
                        :class="[
                            'p-2 text-center font-bold bg-white dark:bg-gray-900',
                            item.purchase_price >
                            item.product_snapshot?.purchase_price
                                ? 'text-red-600'
                                : item.purchase_price <
                                  item.product_snapshot?.purchase_price
                                ? 'text-green-600'
                                : 'text-gray-700 dark:text-gray-300',
                        ]"
                    >
                        <span
                            class="text-[9px] text-gray-400 block mb-0.5 font-normal"
                            >@ Harga Nota</span
                        >
                        {{
                            purchase.received_at && item.purchase_price
                                ? rp(item.purchase_price)
                                : "-"
                        }}

                        <span
                            v-if="
                                purchase.received_at &&
                                item.purchase_price >
                                    item.product_snapshot?.purchase_price
                            "
                            class="ml-1 text-[9px]"
                            >▲</span
                        >
                        <span
                            v-else-if="
                                purchase.received_at &&
                                item.purchase_price <
                                    item.product_snapshot?.purchase_price &&
                                item.purchase_price > 0
                            "
                            class="ml-1 text-[9px]"
                            >▼</span
                        >
                    </div>
                </div>
            </div>

            <!-- Status badges + Subtotal -->
            <div class="flex items-end justify-between mt-2 mx-3 mb-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <Link
                                :href="
                                    route('purchases.linkInvoiceItems', {
                                        purchase: purchase.id,
                                        invoice: item.purchase_invoice_id,
                                    })
                                "
                                v-if="item.purchase_invoice_id"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-lime-50 text-lime-700 border border-lime-100 text-[10px] font-bold dark:bg-lime-900/20 dark:border-lime-800 dark:text-lime-300"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                                Terhubung Nota
                            </Link>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 text-gray-500 border border-gray-200 text-[10px] font-medium dark:bg-gray-800 dark:border-gray-700"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                Pending Nota
                            </span>
                        </div>

                        <div class="flex flex-wrap gap-1">
                            <span v-if="item.quantity_received < item.quantity" class="px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 rounded dark:bg-red-900/30 dark:text-red-400">Qty Kurang</span>
                            <span v-else-if="item.quantity_received > item.quantity" class="px-1.5 py-0.5 text-[9px] font-bold bg-yellow-100 text-yellow-700 rounded dark:bg-yellow-900/30 dark:text-yellow-400">Qty Lebih</span>
                            <span v-if="item.actual_price > item.purchase_price" class="px-1.5 py-0.5 text-[9px] font-bold bg-orange-100 text-orange-700 rounded dark:bg-orange-900/30 dark:text-orange-400">Harga Naik</span>
                            <span v-else-if="item.actual_price < item.purchase_price && item.actual_price > 0" class="px-1.5 py-0.5 text-[9px] font-bold bg-green-100 text-green-700 rounded dark:bg-green-900/30 dark:text-green-400">Harga Turun</span>
                            <span v-if="item.quantity_received == item.quantity && (!item.actual_price || item.actual_price == item.purchase_price)" class="px-1.5 py-0.5 text-[9px] font-bold bg-lime-100 text-lime-700 rounded dark:bg-lime-900/30 dark:text-lime-400">Sesuai</span>
                        </div>
                    </div>

                    <!-- Subtotal Highlighted -->
                    <div class="flex flex-col items-end pl-3 border-l-2 border-dashed border-gray-200 dark:border-gray-700">
                        <span class="text-[9px] uppercase tracking-wider font-bold text-gray-400 mb-0.5">Subtotal</span>
                        <span class="text-lg font-black text-gray-900 dark:text-white leading-none">
                            {{ rp((item.actual_price || item.purchase_price) * (item.quantity_received || item.quantity)) }}
                        </span>
                    </div>
            </div>
        </div>
    </div>
</template>
