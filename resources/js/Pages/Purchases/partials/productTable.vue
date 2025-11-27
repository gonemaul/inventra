<script setup>
import DataTable from "@/Components/DataTable.vue";
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    items: { type: Array, required: true },
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
        key: "status",
        label: "Status",
        sortable: true,
        width: "120px",
        slot: "status",
    },
];
function getItemValidationStatus(item) {
    const qtyOrdered = item.product_snapshot.quantity;
    const qtyReceived = item.quantity;
    const priceOrdered = item.product_snapshot.purchase_price;
    const priceReceived = item.purchase_price;

    // --- LOGIKA 1: STATUS KUANTITAS ---
    if (qtyOrdered === 0) {
        // Jika Qty yang dipesan adalah 0, ini adalah barang baru/tambahan
        // Kita asumsikan ini barang yang tidak ada di PO awal atau item substitusi.
        return "Barang Baru";
    }
    if (qtyReceived === 0) {
        // Jika Qty dipesan > 0 tapi diterima 0
        return "Barang Kosong";
    }
    if (qtyReceived > qtyOrdered) {
        return "Lebih";
    }
    if (qtyReceived < qtyOrdered) {
        return "Kurang";
    }

    // Jika Qty Sesuai (qtyReceived === qtyOrdered), lanjutkan ke pengecekan Harga

    // --- LOGIKA 2: STATUS HARGA (Hanya jika Qty Sesuai) ---
    // Gunakan Number() untuk memastikan perbandingan harga numerik, bukan string
    if (Number(priceReceived) > Number(priceOrdered)) {
        return "Harga Naik";
    }
    if (Number(priceReceived) < Number(priceOrdered)) {
        return "Harga Turun";
    }

    // --- LOGIKA 3: STATUS SEMPURNA ---
    return "Sesuai";
}
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
</script>
<template>
    <div
        class="p-4 space-y-5 bg-white border rounded-lg shadow-md dark:bg-gray-800"
    >
        <h3 class="text-lg font-bold dark:text-white">
            Daftar Produk yang dibeli
        </h3>
        <DataTable
            :columns="columns"
            :serverSide="false"
            :perPageOptions="[5, 10, 20]"
            :data="items"
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
                        'bg-red-700':
                            getComprehensiveValidationStatus(row).includes(
                                'KOSONG'
                            ) ||
                            getComprehensiveValidationStatus(row).includes(
                                'KURANG'
                            ),
                        'bg-purple-500':
                            getComprehensiveValidationStatus(row).includes(
                                'LEBIH'
                            ),
                        'bg-yellow-600':
                            getComprehensiveValidationStatus(row).includes(
                                'HARGA NAIK'
                            ),
                        'bg-yellow-400':
                            getComprehensiveValidationStatus(row).includes(
                                'HARGA TURUN'
                            ),
                        'bg-blue-500':
                            getComprehensiveValidationStatus(row).includes(
                                'BARU'
                            ),
                        'bg-green-500':
                            getComprehensiveValidationStatus(row) ===
                            'SESUAI SEMPURNA',
                    }"
                >
                    {{ (status = getComprehensiveValidationStatus(row)) }}
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
