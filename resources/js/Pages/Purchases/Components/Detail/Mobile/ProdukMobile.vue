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

// --- SMART STATUS LOGIC ---
const getUnifiedStatus = (item) => {
    const orderQty = Number(item.product_snapshot?.quantity || 0);
    const orderPrice = Number(item.product_snapshot?.purchase_price || 0);
    const realQty = Number(item.quantity || 0);
    const realPrice = Number(item.purchase_price || 0);

    if (orderQty === 0 && realQty > 0) return "Baru";
    if (orderQty > 0 && realQty === 0) {
        return props.purchase?.status === "selesai" ? "Kosong" : "Menunggu";
    }
    if (orderQty > 0 && realQty > 0) {
        if (orderQty === realQty && orderPrice === realPrice) {
            return "Sesuai";
        } else {
            return "Ada Perubahan";
        }
    }
    return "Menunggu";
};

const getStatusBadgeClass = (status) => {
    switch(status) {
        case 'Ada Perubahan': return 'px-1.5 py-0.5 text-[9px] font-bold bg-orange-100 text-orange-700 rounded border border-orange-200 dark:bg-orange-900/40 dark:border-orange-800 dark:text-orange-400';
        case 'Sesuai': return 'px-1.5 py-0.5 text-[9px] font-bold bg-lime-100 text-lime-700 rounded border border-lime-200 dark:bg-lime-900/40 dark:border-lime-800 dark:text-lime-400';
        case 'Baru': return 'px-1.5 py-0.5 text-[9px] font-bold bg-purple-100 text-purple-700 rounded border border-purple-200 dark:bg-purple-900/40 dark:border-purple-800 dark:text-purple-400';
        case 'Kosong': return 'px-1.5 py-0.5 text-[9px] font-bold bg-red-100 text-red-700 rounded border border-red-200 dark:bg-red-900/40 dark:border-red-800 dark:text-red-400';
        case 'Menunggu': return 'px-1.5 py-0.5 text-[9px] font-bold bg-gray-100 text-gray-500 rounded border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400';
        default: return 'px-1.5 py-0.5 text-[9px] font-bold bg-gray-100 text-gray-600 rounded';
    }
};

const processedItems = computed(() => {
    if (!props.purchase?.items) return [];
    return props.purchase.items.map(item => ({
        ...item,
        unified_status: getUnifiedStatus(item)
    }));
});

// --- SMART SEARCH & FILTERS ---
const searchKeywords = ref("");
const activeFilter = ref("Semua");
const filterOptions = ["Semua", "Ada Perubahan", "Sesuai", "Kosong", "Baru", "Menunggu"];

const searchWords = computed(() =>
    searchKeywords.value.toLowerCase().split(" ").filter((w) => w)
);

const filteredItems = computed(() => {
    let items = processedItems.value;
    
    // Default Fallback
    if (items.length === 0) return [];

    // Filter Chips
    if (activeFilter.value !== "Semua") {
        items = items.filter(item => item.unified_status === activeFilter.value);
    }

    // Keyword Search
    if (searchWords.value.length > 0) {
        items = items.filter((item) => {
            const text = `${(item.product?.name || "").toLowerCase()} ${(item.product?.code || "").toLowerCase()}`;
            return searchWords.value.every((w) => text.includes(w));
        });
    }

    return items;
});

const blurSearchInput = () => {
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        document.activeElement.blur();
    }
};

import { useSmartRAB } from '@/Composable/useSmartRAB';
const { openRabModal } = useSmartRAB();
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

        <!-- SMART FILTERS CHIPS -->
        <div class="flex overflow-x-auto gap-2 pb-1 scrollbar-hide pr-4 mask-fade-right snap-x">
            <button
                v-for="filter in filterOptions"
                :key="filter"
                @click="activeFilter = filter"
                class="snap-start whitespace-nowrap px-3 py-1.5 rounded-full text-[10px] font-bold border transition-all"
                :class="
                    activeFilter === filter
                        ? 'bg-gray-800 text-white border-gray-800 dark:bg-gray-100 dark:text-gray-900 border-transparent shadow-sm'
                        : 'bg-white text-gray-500 border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 hover:bg-gray-50'
                "
            >
                {{ filter }}
            </button>
        </div>
        
        <!-- STATISTIK INFO -->
        <div class="flex items-center justify-between px-1 mb-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                Total: {{ filteredItems.length }} item {{ activeFilter !== 'Semua' ? `(${activeFilter})` : '' }}
            </span>
        </div>

        <p v-if="filteredItems.length === 0" class="py-6 text-xs text-center text-gray-400">
            <span v-if="searchKeywords">Tidak ada produk yang cocok dengan "<strong>{{ searchKeywords }}</strong>"</span>
            <span v-else>Tidak ada produk pada filter "<strong>{{ activeFilter }}</strong>"</span>
        </p>

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
                            <span :class="getStatusBadgeClass(item.unified_status)">
                                {{ item.unified_status }}
                            </span>
                        </div>
                    </div>

                    <!-- Beli Lagi Button & Subtotal Highlighted -->
                    <div class="flex flex-col items-end gap-2 pl-3 border-l-2 border-dashed border-gray-200 dark:border-gray-700">
                        <button 
                            @click="openRabModal(item, purchase?.supplier?.id, item.product_snapshot?.quantity || 1)"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-[10px] font-bold text-white bg-lime-500 rounded-lg hover:bg-lime-600 active:scale-95 transition shadow-sm shadow-lime-500/30"
                        >
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            Beli Lagi
                        </button>
                        <div class="flex flex-col items-end mt-auto">
                            <span class="text-[9px] uppercase tracking-wider font-bold text-gray-400 mb-0.5">Subtotal</span>
                            <span class="text-lg font-black text-gray-900 dark:text-white leading-none">
                                {{ rp((item.actual_price || item.purchase_price) * (item.quantity_received || item.quantity)) }}
                            </span>
                        </div>
                    </div>
            </div>
        </div>
    </div>
</template>
<style scoped>
.mask-fade-right {
    mask-image: linear-gradient(to right, black 90%, transparent 100%);
    -webkit-mask-image: linear-gradient(to right, black 90%, transparent 100%);
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>