<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    linkedItems: Object,
    openEditModal: Object,
});
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);

// --- SMART FILTERS ---
const activeFilter = ref("Semua");
const filterOptions = ["Semua", "Ada Perubahan", "Sesuai", "Baru"];

const getUnifiedStatus = (item) => {
    const orderQty = Number(item.product_snapshot?.quantity || 0);
    const orderPrice = Number(item.product_snapshot?.purchase_price || 0);
    const realQty = Number(item.quantity || 0);
    const realPrice = Number(item.purchase_price || 0);

    if (orderQty === 0 && realQty > 0) return "Baru";
    
    if (orderQty === realQty && orderPrice === realPrice) {
        return "Sesuai";
    } else {
        return "Ada Perubahan";
    }
};

const processedLinkedItems = computed(() => {
    if (!props.linkedItems) return [];
    return props.linkedItems.map(item => ({
        ...item,
        unified_status: getUnifiedStatus(item)
    }));
});

const filteredLinkedItems = computed(() => {
    let items = processedLinkedItems.value;
    
    if (activeFilter.value !== "Semua") {
        items = items.filter(item => item.unified_status === activeFilter.value);
    }
    
    return items;
});
</script>
<template>
        <!-- SMART FILTERS CHIPS -->
        <div v-if="linkedItems && linkedItems.length > 0" class="flex overflow-x-auto gap-2 mb-3 pb-1 custom-scrollbar hide-scrollbar snap-x">
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
        <div v-if="linkedItems && linkedItems.length > 0" class="flex items-center justify-between px-1 mb-2">
            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                Total: {{ filteredLinkedItems.length }} item {{ activeFilter !== 'Semua' ? `(${activeFilter})` : '' }}
            </span>
        </div>

        <div
            v-if="filteredLinkedItems.length === 0 && linkedItems.length > 0"
            class="py-10 text-sm text-center text-gray-400"
        >
            <p>Tidak ada item pada filter "<strong>{{ activeFilter }}</strong>"</p>
        </div>

        <div
            v-if="linkedItems.length === 0"
            class="py-10 text-sm text-center text-gray-400"
        >
            <p>Belum ada item yang ditautkan.</p>
        </div>

        <div
            v-for="item in filteredLinkedItems"
            :key="item.id"
            @click="openEditModal(item)"
            class="relative mb-2 transition-all bg-white border rounded-lg shadow-sm cursor-pointer group dark:bg-gray-900 hover:shadow-md active:scale-[0.99]"
            :class="[
                // 1. LOGIKA WARNA GARIS KIRI (Border Strip)
                // Kasus A: Barang Baru (Snapshot Kosong) -> UNGU
                item.unified_status === 'Baru'
                    ? 'border-l-4 border-l-purple-500 border-y border-r border-purple-200 bg-purple-50/40 dark:border-purple-900/50 dark:bg-purple-900/10'
                    : // Kasus B: Barang PO tapi ADA PERUBAHAN (Qty atau Harga beda) -> ORANYE
                    item.unified_status === 'Ada Perubahan'
                    ? 'border-l-4 border-l-orange-500 border-y border-r border-gray-200 dark:border-gray-700'
                    : // Kasus C: Barang PO dan SESUAI -> HIJAU
                      'border-l-4 border-l-lime-500 border-y border-r border-gray-200 dark:border-gray-700',
            ]"
        >
            <div class="p-3">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <h4
                            class="text-sm font-bold leading-tight text-gray-800 dark:text-gray-100 line-clamp-2"
                        >
                            {{ item.product?.name }}
                        </h4>
                        <span
                            class="text-[10px] font-mono text-gray-400 block mt-0.5"
                        >
                            {{
                                item.product_snapshot?.category +
                                    " " +
                                    item.product_snapshot.productType || "-"
                            }}
                        </span>
                        <span
                            class="text-[10px] font-mono text-gray-400 block mt-0.5"
                        >
                            {{ item.product_snapshot?.size || "-" }}/{{
                                item.product_snapshot?.unit || "-"
                            }}
                        </span>
                    </div>

                    <div class="flex flex-col items-end gap-1">
                        <span
                            class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide rounded-full border"
                            :class="
                                item.unified_status !== 'Baru'
                                    ? 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400'
                                    : 'bg-purple-100 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400'
                            "
                        >
                            {{
                                item.unified_status !== 'Baru'
                                    ? "Dari PO"
                                    : "Baru"
                            }}
                        </span>

                        <span
                            v-if="item.unified_status === 'Ada Perubahan'"
                            class="px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide rounded-full border bg-orange-50 text-orange-600 border-orange-100 dark:bg-orange-900/20 dark:text-orange-400"
                        >
                            Ada Perubahan
                        </span>
                    </div>
                </div>

                <div class="mt-2 space-y-1.5">
                    <div
                        v-if="
                            item.product_snapshot.quantity > 0 &&
                            item.quantity !== item.product_snapshot.quantity
                        "
                        class="flex items-center justify-between p-1.5 text-xs bg-orange-50 rounded border border-orange-100 dark:bg-orange-900/10 dark:border-orange-900/30"
                    >
                        <span class="font-medium text-orange-600"
                            >Qty Berubah</span
                        >
                        <div class="flex items-center gap-2">
                            <span
                                class="text-gray-400 decoration-red-400 decoration-2"
                                >{{ item.product_snapshot.quantity }}</span
                            >
                            <svg
                                class="w-3 h-3 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                                />
                            </svg>
                            <span
                                class="font-bold text-gray-800 dark:text-white"
                                >{{ item.quantity }}</span
                            >
                        </div>
                    </div>

                    <div
                        v-if="
                            item.product_snapshot.quantity > 0 &&
                            item.purchase_price !==
                                item.product_snapshot.purchase_price
                        "
                        class="flex items-center justify-between p-1.5 text-xs bg-orange-50 rounded border border-orange-100 dark:bg-orange-900/10 dark:border-orange-900/30"
                    >
                        <span class="font-medium text-orange-600"
                            >Harga Berubah</span
                        >
                        <div class="flex items-center gap-2">
                            <span
                                class="text-gray-400 decoration-red-400 decoration-2"
                                >{{
                                    rp(item.product_snapshot.purchase_price)
                                }}</span
                            >
                            <svg
                                class="w-3 h-3 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                                />
                            </svg>
                            <span
                                class="font-bold text-gray-800 dark:text-white"
                                >{{ rp(item.purchase_price) }}</span
                            >
                        </div>
                    </div>

                <div class="flex items-center justify-between mt-3 p-2.5 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-inner">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Qty Fisik & Harga</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="font-black text-blue-600 dark:text-blue-400 text-lg">{{ item.quantity }}</span>
                            <span class="text-gray-400 text-[10px] font-bold">x</span>
                            <span class="font-bold text-gray-700 dark:text-gray-200 text-sm">{{ rp(item.purchase_price) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end border-l-2 border-dashed border-gray-200 dark:border-gray-700 pl-3">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Total Fixed</span>
                        <span class="font-black text-xl text-gray-900 dark:text-white leading-none tracking-tight">
                            {{ rp(item.subtotal) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
