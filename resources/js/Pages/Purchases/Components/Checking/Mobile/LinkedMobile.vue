<script setup>
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
</script>
<template>
    <div>
        <div
            v-if="linkedItems.length === 0"
            class="py-10 text-sm text-center text-gray-400"
        >
            <p>Belum ada item yang ditautkan.</p>
        </div>

        <div
            v-for="item in linkedItems"
            :key="item.id"
            @click="openEditModal(item)"
            class="relative mb-2 transition-all bg-white border rounded-lg shadow-sm cursor-pointer group dark:bg-gray-900 hover:shadow-md active:scale-[0.99]"
            :class="[
                // 1. LOGIKA WARNA GARIS KIRI (Border Strip)
                // Kasus A: Barang Baru (Snapshot Kosong) -> UNGU
                item.product_snapshot.quantity <= 0
                    ? 'border-l-4 border-l-purple-500 border-y border-r border-purple-200 bg-purple-50/40 dark:border-purple-900/50 dark:bg-purple-900/10'
                    : // Kasus B: Barang PO tapi ADA PERUBAHAN (Qty atau Harga beda) -> ORANYE
                    item.quantity !== item.product_snapshot.quantity ||
                      item.purchase_price !==
                          item.product_snapshot.purchase_price
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
                                item.product_snapshot.quantity > 0
                                    ? 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-900/20 dark:text-blue-400'
                                    : 'bg-purple-100 text-purple-600 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400'
                            "
                        >
                            {{
                                item.product_snapshot.quantity > 0
                                    ? "Dari PO"
                                    : "Baru"
                            }}
                        </span>

                        <span
                            v-if="
                                item.product_snapshot.quantity > 0 &&
                                (item.quantity !==
                                    item.product_snapshot.quantity ||
                                    item.purchase_price !==
                                        item.product_snapshot.purchase_price)
                            "
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

                    <div class="flex flex-col">
                        <span class="text-[10px] text-gray-400">Kalkulasi</span>
                        <div
                            class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-400"
                        >
                            <span
                                class="font-bold text-gray-800 dark:text-gray-200"
                                >{{ item.quantity }}</span
                            >
                            <span>x</span>
                            <span>{{ rp(item.purchase_price) }}</span>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-end justify-between pt-2 mt-2 border-t border-gray-100 border-dashed dark:border-gray-700"
                >
                    <span class="text-[10px] text-gray-400"
                        >Total Subtotal</span
                    >
                    <span
                        class="font-mono text-sm font-black text-gray-800 dark:text-gray-100"
                    >
                        {{ rp(item.purchase_price * item.quantity) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
