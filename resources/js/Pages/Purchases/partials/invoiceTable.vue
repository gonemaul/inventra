<script setup>
import DataTable from "@/Components/DataTable.vue";
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import ImageModal from "@/Components/ImageModal.vue";
// Menggunakan Emit untuk memberi tahu Parent (PurchaseDetail.vue) adanya aksi
const emit = defineEmits(["edit-invoice", "delete-invoice"]);

// Prop: Data Invoice (Array dari PurchaseInvoice)
const props = defineProps({
    purchase: { type: Array, required: true },
    isCheckingMode: { type: Boolean, default: false }, // Menentukan visibility tombol Aksi
    canEditDelete: Boolean,
});
// Gambar Nota
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedInvoiceCode = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedInvoiceCode.value = "Invoice-#" + name;
    showImageModal.value = true;
};

// Kolom yang disesuaikan untuk data PurchaseInvoice
const columns = [
    {
        key: "invoice_number",
        label: "No Nota",
        sortable: true,
        width: "100px",
        slot: "invoice",
    },
    {
        key: "invoice_date",
        label: "Tanggal Nota",
        sortable: true,
        width: "150px",
        format: "tanggal",
    },
    {
        key: "due_date",
        label: "Jatuh Tempo",
        sortable: true,
        width: "150px",
        format: "tanggal",
    },
    {
        key: "total_amount",
        label: "Nominal Total",
        sortable: true,
        width: "200px",
        format: "rupiah",
    },
    {
        key: "payment_status",
        label: "Status Pembayaran",
        sortable: true,
        width: "100px",
        slot: "status_payment",
        class: "text-center",
    },
    {
        key: "status",
        label: "Status",
        sortable: true,
        width: "100px",
        slot: "status",
        class: "text-center",
    },
    {
        key: "aksi",
        label: "Aksi",
        width: "180px",
        slot: "aksi",
        class: "text-right",
    },
];
</script>
<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedInvoiceCode"
        @close="showImageModal = false"
    />
    <div
        class="p-4 space-y-5 bg-white border rounded-lg shadow-md dark:bg-gray-900 dark:border-lime-500"
    >
        <h3 class="text-lg font-bold dark:text-white">Daftar Nota Keuangan</h3>

        <DataTable
            :columns="columns"
            :data="purchase.invoices"
            :serverSide="false"
            :perPageOptions="[5, 10, 20]"
        >
            <template #invoice="{ row }">
                <span
                    class="text-blue-500 cursor-pointer"
                    @click="openImageModal(row.invoice_url, row.invoice_number)"
                    >{{ row.invoice_number }}</span
                >
            </template>
            <template #status_payment="{ row }">
                <span
                    :class="{
                        'px-2 py-1 text-white rounded': true,
                        'bg-green-600': row.payment_status === 'paid',
                        'bg-yellow-600': row.payment_status === 'partial',
                        'bg-red-600': row.payment_status === 'unpaid',
                    }"
                    class="text-xs text-center uppercase"
                >
                    {{ row.payment_status }}
                </span>
            </template>
            <template #status="{ row }">
                <span
                    :class="{
                        'px-2 py-1 rounded': true,
                        'bg-green-300': row.status === 'validated',
                        'bg-yellow-300 text-yellow-900':
                            row.status === 'uploaded',
                    }"
                    class="text-xs font-medium text-center uppercase"
                >
                    {{ row.status }}
                </span>
            </template>

            <template #aksi="{ row }">
                <div class="flex flex-wrap justify-center gap-2">
                    <Link
                        :href="
                            route('purchases.linkInvoiceItems', {
                                purchase: purchase.id,
                                invoice: row.id,
                            })
                        "
                        class="px-2 py-1 text-xs text-white bg-orange-500 rounded hover:bg-orange-600"
                    >
                        Detail
                    </Link>
                    <button
                        v-if="canEditDelete"
                        @click="$emit('edit-invoice', row)"
                        class="px-2 py-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600"
                    >
                        Edit
                    </button>
                    <button
                        v-if="canEditDelete"
                        @click="$emit('delete-invoice', row)"
                        class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600"
                    >
                        Hapus
                    </button>
                    <Link
                        :href="route('finance.show', row.id)"
                        v-if="
                            row.payment_status !== 'paid' &&
                            purchase.status === 'selesai'
                        "
                        class="px-2 py-1 text-xs text-white rounded bg-lime-600 hover:bg-lime-700"
                    >
                        Bayar
                    </Link>
                </div>
            </template>
        </DataTable>
    </div>
</template>
