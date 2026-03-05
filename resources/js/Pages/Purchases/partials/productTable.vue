<script setup>
import DataTable from "@/Components/DataTable.vue";
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    items: { type: Array, required: true },
});

// --- SMART SEARCH ---
const searchKeywords = ref("");
const searchWords = computed(() =>
    searchKeywords.value.toLowerCase().split(" ").filter((w) => w)
);
const filteredItems = computed(() => {
    if (searchWords.value.length === 0) return props.items;
    return props.items.filter((item) => {
        const text = `${(item.product_snapshot?.name || "").toLowerCase()} ${(item.product_snapshot?.code || "").toLowerCase()}`;
        return searchWords.value.every((w) => text.includes(w));
    });
});

const columns = [
    {
        key: "product.name",
        label: "Nama",
        sortable: true,
        slot: "productDetail",
    },
    { key: "product_snapshot.quantity", label: "Qty Order", sortable: true },
    {
        key: "quantity",
        label: "Qty Diterima",
        sortable: true,
        width: "120px",
    },
    {
        key: "product_snapshot.purchase_price",
        label: "Harga Lama",
        sortable: true,
        width: "120px",
        format: "rupiah",
    },

    {
        key: "purchase_price",
        label: "Harga Diterima",
        sortable: true,
        width: "120px",
        format: "rupiah",
    },
    {
        key: "invoice.invoice_number",
        sortable: true,
        label: "Invoice",
        width: "120px",
        slot: "invoice",
    },
    {
        key: "item_status",
        label: "Status",
        sortable: true,
        width: "120px",
        slot: "status",
    },
];
function getComprehensiveValidationStatus(item) {
    const qtyOrdered = item.product_snapshot.quantity;
    const qtyReceived = item.quantity;
    const priceOrdered = item.product_snapshot.purchase_price;
    const priceReceived = item.purchase_price;

    let qtyLabel = "Sesuai";
    let priceLabel = "Tetap";

    // --- 1. Tentukan Status Kuantitas (Qty) ---

    if (qtyOrdered === 0) {
        qtyLabel = "BARANG BARU";
    } else if (qtyReceived === 0) {
        qtyLabel = "KOSONG";
    } else if (qtyReceived > qtyOrdered) {
        qtyLabel = "LEBIH QTY";
    } else if (qtyReceived < qtyOrdered) {
        qtyLabel = "KURANG QTY";
    }

    // --- 2. Tentukan Status Harga (Price) ---

    if (Number(priceReceived) > Number(priceOrdered)) {
        priceLabel = "HARGA NAIK";
    } else if (Number(priceReceived) < Number(priceOrdered)) {
        priceLabel = "HARGA TURUN";
    }

    // --- 3. GABUNGKAN HASIL (KUNCI LOGIKA) ---

    // Jika Keduanya TIDAK Sesuai (Kurang/Lebih & Naik/Turun)
    if (qtyLabel !== "Sesuai" && priceLabel !== "Tetap") {
        return `${qtyLabel} / ${priceLabel}`; // Contoh: KURANG QTY / HARGA NAIK
    }

    // Jika hanya Qty yang Discrepancy
    if (qtyLabel !== "Sesuai") {
        return qtyLabel; // Contoh: QTY LEBIH
    }

    // Jika hanya Harga yang Discrepancy
    if (priceLabel !== "Tetap") {
        return priceLabel; // Contoh: HARGA TURUN
    }

    // Keduanya Sesuai
    return "SESUAI SEMPURNA";
}
const statuses = ref({
    pending: "Pending",
    fulfilled: "Sesuai",
    partial: "Partial",
    canceled: "Dibatalkan",
    extra: "Susulan",
    over: "Kelebihan",
});
</script>
<template>
    <div
        class="p-4 space-y-5 bg-white border rounded-lg shadow-md dark:bg-gray-800"
    >
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold dark:text-white">
                Daftar Produk yang dibeli
            </h3>
            <!-- SMART SEARCH -->
            <div class="relative w-64">
                <svg class="absolute w-4 h-4 text-gray-400 -translate-y-1/2 left-3 top-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input
                    v-model="searchKeywords"
                    type="text"
                    placeholder="Cari nama / kode produk..."
                    class="w-full pl-9 pr-8 py-2 text-xs bg-gray-50 border border-gray-200 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all"
                />
                <button v-if="searchKeywords" @click="searchKeywords = ''" class="absolute w-4 h-4 text-gray-400 -translate-y-1/2 right-2.5 top-1/2 hover:text-gray-600">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>
        <DataTable
            :columns="columns"
            :serverSide="false"
            :perPageOptions="[5, 10, 20]"
            :data="filteredItems"
        >
            <!-- Slot aksi -->
            <template #invoice="{ row }">
                <Link
                    :href="
                        route('purchases.linkInvoiceItems', {
                            purchase: row.purchase_id,
                            invoice: row.purchase_invoice_id,
                        })
                    "
                    v-if="row.invoice"
                    class="p-2 font-medium text-blue-800 rounded bg-lime-300 hover:underline"
                    >{{ row.invoice.invoice_number }}</Link
                >
                <span v-else class="text-gray-500">Tidak Terhubung</span>
            </template>
            <template #status="{ row }">
                <span
                    :class="{
                        'px-2 py-1 rounded-md text-xs font-medium text-white': true,
                        // Prioritas Warna berdasarkan String Konten
                        'bg-gray-500': row.item_status === 'pending',
                        'bg-green-500': row.item_status === 'fulfilled',
                        'bg-yellow-600': row.item_status === 'partial',
                        'bg-red-700': row.item_status === 'canceled',
                        'bg-blue-500': row.item_status === 'extra',
                        'bg-purple-500': row.item_status === 'over',
                    }"
                >
                    {{ statuses[row.item_status] }}
                </span>
            </template>
            <template #productDetail="{ row }">
                <div class="flex flex-col">
                    <span class="font-bold text-gray-800 dark:text-white">
                        {{ row.product_snapshot.name }}
                    </span>
                    <span class="text-xs text-gray-500">
                        {{ row.product_snapshot.brand }} |
                        {{ row.product_snapshot.code }} |
                        {{ row.product_snapshot.category }}
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ row.product_snapshot.size }} -
                        {{ row.product_snapshot.unit }}
                    </span>
                </div>
            </template>
        </DataTable>
    </div>
</template>
