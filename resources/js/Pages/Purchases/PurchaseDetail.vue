<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Tabs from "@/Components/Tabs.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import FinalizeModal from "./partials/FinalizeModal.vue";
import { Link } from "@inertiajs/vue3";
// Komponen Anak
import InvoiceForm from "./partials/InvoiceForm.vue";
import InvoiceTransactionTable from "./partials/invoiceTable.vue"; // Menggunakan nama yang sudah diperbaiki
import ItemValidationTable from "./partials/productTable.vue"; // Menggunakan nama yang sesuai dengan fungsinya
import HeaderDetail from "./partials/HeaderDetail.vue";
// Logic
import { ref, computed, onMounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";

const props = defineProps({
    purchase: Object, // Data transaksi utama (purchase, items, supplier)
    invoices: Object, // Koleksi invoices yang sudah ada (Dipisahkan dari objek purchase di BE untuk kejelasan)
    invoice: Object, // Nota pertama/terakhir (atau Nota baru)
    paymentStatuses: Array,
    isCheckingMode: Boolean, // [FIX] Mengubah tipe prop menjadi Boolean
});

// State untuk Invoice CRUD Modal
const deleteModalRef = ref(null);
const showInvoiceModal = ref(false);
const editingInvoiceData = ref(null);
const showFinalizeModal = ref(false);

onMounted(() => {
    // Kunci logikanya: Jika status 'received' DAN belum ada ID invoice (props.invoice.id == 0 atau null)
    if (props.purchase.status === "diterima" && !props.invoice.id) {
        // [AKSI OTOMATIS] Buka modal Create Invoice
        openCreateInvoiceModal();
    }
});
// --- 2. STATE UNTUK ITEM (Validasi Rejected Qty) ---
const validationItems = ref(
    props.purchase.items.map((item) => ({
        ...item,
        rejected_quantity: item.rejected_quantity || 0,
        is_valid: item.quantity - (item.rejected_quantity || 0) > 0,
    }))
);

// --- STATE KONDISI UTAMA (v-if) ---
const allowFinalize = computed(() => {
    const hasInvoice = props.purchase.invoices.length > 0;
    return hasInvoice && props.purchase.status === "checking";
});
const canEditDeleteInvoice = computed(() =>
    ["diterima", "checking"].includes(props.purchase.status)
);

const openFinalizeModal = () => {
    showFinalizeModal.value = true;
};

// --- HANDLER AKSI INVOICE TABLE ---
const openCreateInvoiceModal = () => {
    editingInvoiceData.value = null;
    showInvoiceModal.value = true;
};
const handleEditInvoice = (invoice) => {
    editingInvoiceData.value = invoice;
    showInvoiceModal.value = true;
};
const handleDeleteInvoice = (invoice) => {
    // [LOGIC DEFERRED] Panggil deleteModalRef.value.open()
    const config = {
        title: "Hapus Nota Keuangan",
        message: `Hapus Nota No. ${invoice.invoice_number}? Ini akan menghapus file foto dan mengembalikan status transaksi ke RECEIVED jika tidak ada nota lain.`,
        itemName: invoice.invoice_number,
        url: route("purchases.destroyInvoice", {
            purchase: props.purchase.id, // ID Transaksi Induk
            invoice: invoice.id, // ID Nota yang akan dihapus
        }),
    };

    deleteModalRef.value.open(config);
};

const handleInvoiceSaved = () => {
    showInvoiceModal.value = false;
    // Reload props untuk mendapatkan daftar invoice terbaru dari BE
    router.reload({
        only: ["purchase", "invoice", "flash"],
        preserveScroll: true,
    });
};

// Tabs definition
const tabs = [
    { key: "invoices", label: "Invoice & Pembayaran" }, // Label disesuaikan
    { key: "products", label: "Validasi Item" },
];
</script>
<template>
    <Head :title="`Pembelian - ${purchase.reference_no}`"></Head>
    <DeleteConfirm ref="deleteModalRef" @success="handleInvoiceSaved" />
    <FinalizeModal
        :show="showFinalizeModal"
        :purchase="purchase"
        @close="showFinalizeModal = false"
    />
    <AuthenticatedLayout :showSidebar="false" :showHeader="false">
        <div
            class="flex items-center gap-2 mb-3 text-sm text-gray-500 dark:text-gray-300"
        >
            <Link
                :href="route('purchases.index')"
                class="flex items-center transition hover:text-indigo-600"
            >
                <svg
                    class="w-4 h-4 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    ></path>
                </svg>
                Riwayat
            </Link>
            <span>/</span>
            <span>Detail Pembelian # {{ purchase.reference_no }}</span>
        </div>
        <HeaderDetail :data="props.purchase" :mode="type" />
        <InvoiceForm
            :show="showInvoiceModal"
            :purchaseId="purchase.id"
            :editingInvoice="editingInvoiceData"
            :paymentStatuses="paymentStatuses"
            @close="showInvoiceModal = false"
            @invoice-saved="handleInvoiceSaved"
        />
        <div
            v-if="purchase.status === 'checking'"
            class="flex justify-end mt-4"
        >
            <PrimaryButton
                :disable="!allowFinalize"
                @click="openFinalizeModal"
                :class="{ 'opacity-50 cursor-not-allowed': !allowFinalize }"
            >
                Selesaikan & Masukkan Stok (Completed)
            </PrimaryButton>
        </div>

        <div class="w-full mt-4 space-y-6">
            <Tabs :tabs="tabs" defaultTab="invoices">
                <template #invoices>
                    <div
                        v-if="canEditDeleteInvoice"
                        class="flex justify-end mb-4"
                    >
                        <PrimaryButton @click="openCreateInvoiceModal">
                            + Tambah Nota Baru
                        </PrimaryButton>
                    </div>

                    <InvoiceTransactionTable
                        :purchase="purchase"
                        :isCheckingMode="isCheckingMode"
                        :canEditDelete="canEditDeleteInvoice"
                        @edit-invoice="handleEditInvoice"
                        @delete-invoice="handleDeleteInvoice(invoice)"
                    />
                </template>
                <template #products>
                    <ItemValidationTable :items="validationItems" />
                    <!-- <div
                        v-else
                        class="p-6 text-center text-gray-500 bg-white rounded-lg dark:bg-gray-800"
                    >
                        Validasi item akan muncul setelah nota berhasil
                        diunggah.
                    </div> -->
                </template>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
