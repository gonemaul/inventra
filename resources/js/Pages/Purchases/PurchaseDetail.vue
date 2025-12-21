<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
import FinalizeModal from "./Components/Detail/FinalizeModal.vue";
import OrderImageModal from "./Components/OrderImageModal.vue";
import InvoiceForm from "./Components/Detail/InvoiceForm.vue";

// Import Anak
import DesktopDetail from "./Components/Detail/Desktop/DesktopDetail.vue";
import MobileDetail from "./Components/Detail/Mobile/MobileDetail.vue";

const props = defineProps({
    purchase: Object, // Data transaksi utama (purchase, items, supplier)
    invoices: Object, // Koleksi invoices yang sudah ada (Dipisahkan dari objek purchase di BE untuk kejelasan)
    invoice: Object, // Nota pertama/terakhir (atau Nota baru)
    paymentStatuses: Array,
    isCheckingMode: Boolean, // [FIX] Mengubah tipe prop menjadi Boolean
});

// State untuk Invoice CRUD Modal
const showImageModal = ref(false);
const deleteModalRef = ref(null);
const showInvoiceModal = ref(false);
const editingInvoiceData = ref(null);
const showFinalizeModal = ref(false);
const showConfirmModal = ref(false);

// State Deteksi Layar
const isMobile = ref(window.innerWidth < 1024);
const updateScreenSize = () => {
    isMobile.value = window.innerWidth < 1024;
};

onMounted(() => {
    window.addEventListener("resize", updateScreenSize);
    // Kunci logikanya: Jika status 'received' DAN belum ada ID invoice (props.invoice.id == 0 atau null)
    if (props.purchase.status === "diterima" && !props.invoice.id) {
        // [AKSI OTOMATIS] Buka modal Create Invoice
        openCreateInvoiceModal();
    }
});
onUnmounted(() => window.removeEventListener("resize", updateScreenSize));

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
    const p = props.purchase;
    if (p.status !== "checking") return false;
    const hasPendingItems = p.items.some(
        (item) => item.item_status === "pending"
    );
    if (hasPendingItems) return false;
    if (p.invoices.length === 0) return false;
    const allInvoicesValid = p.invoices.every((inv) => {
        const isValid = inv.status === "validated";
        const hasItems = inv.items && inv.items.length > 0;
        return isValid && hasItems;
    });
    return allInvoicesValid;
});
const canEditDeleteInvoice = computed(() =>
    ["diterima", "checking"].includes(props.purchase.status)
);
const isEditing = computed(
    () =>
        props.purchase.status === "draft" || props.purchase.status === "dipesan"
);
const isDeleted = computed(() => props.purchase.status === "draft");

const openFinalizeModal = () => {
    if (!allowFinalize.value) return;
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
        preserveScroll: true,
        preserveState: true,
    });
};

// Fungsi Delete Order
const handleDelete = () => {
    const config = {
        title: "Hapus Pembelian",
        message: `Apakah Anda yakin ingin menghapus pembelian #${props.purchase.reference_no}? Tindakan ini tidak dapat dibatalkan.`,
        // itemName: props.purchase.reference_no,
        url: route("purchases.destroy", props.purchase.id),
    };

    deleteModalRef.value.open(config);
};

const updateStatus = (purchase, newStatus) => {
    const config = {
        title: `Konfirmasi Perubahan Status`,
        message: `Anda yakin ingin mengubah status transaksi ${
            purchase.reference_no
        } menjadi ${newStatus.toUpperCase()}?`,
        itemName: purchase.reference_no,
        url: route("purchases.update-status", { purchase: purchase.id }),
        method: "put",
        // Tambahkan data status yang akan dikirim
        data: { status: newStatus },
    };
    showConfirmModal.value.open(config);
};

const getActions = (row) => {
    const actions = [];
    switch (row.status) {
        case "draft":
            actions.push({
                label: "Tandai Dipesan",
                icon: "play",
                type: "status",
                newStatus: "dipesan",
            });
            break;

        case "dipesan":
            actions.push({
                label: "Tandai Dikirim",
                icon: "truck",
                type: "status",
                newStatus: "dikirim",
            });
            break;

        case "dikirim":
            actions.push({
                label: "Tandai Diterima",
                icon: "receive",
                type: "status",
                newStatus: "diterima",
            });
            break;

        case "diterima":
            actions.push({
                label: "Batalkan Terima",
                icon: "undo",
                type: "status",
                newStatus: "dikirim",
            }); // Kembali ke shipped
            break;

        case "checking":
        case "completed":
        case "cancelled":
            break;
    }

    return actions;
};

const actions = {
    openCreateInvoiceModal,
    handleEditInvoice,
    handleDeleteInvoice,
    handleDelete,
    updateStatus,
    openFinalizeModal,
    getActions, // Method helper status button
    openImageModal: () => (showImageModal.value = true),
};
</script>
<template>
    <Head :title="`Detail - ${purchase.reference_no}`"></Head>

    <DeleteConfirm ref="deleteModalRef" @success="handleInvoiceSaved" />
    <ConfirmModal ref="showConfirmModal" @success="handleInvoiceSaved" />
    <FinalizeModal
        :show="showFinalizeModal"
        :purchase="purchase"
        @close="showFinalizeModal = false"
    />
    <OrderImageModal
        :show="showImageModal"
        :purchase="purchase"
        :store-name="$page.props.auth.user.store_name || 'INVENTRA STORE'"
        @close="showImageModal = false"
    />
    <InvoiceForm
        :show="showInvoiceModal"
        :purchaseId="purchase.id"
        :editingInvoice="editingInvoiceData"
        :paymentStatuses="paymentStatuses"
        @close="showInvoiceModal = false"
        @invoice-saved="handleInvoiceSaved"
    />
    <AuthenticatedLayout
        :showSidebar="!isMobile"
        :showHeader="!isMobile"
        :showBottomBar="!isMobile"
        :headerTitle="`Detail - ${purchase.reference_no}`"
    >
        <div v-if="isMobile">
            <MobileDetail
                v-bind="props"
                :actions="actions"
                :validationItems="validationItems"
                :allowFinalize="allowFinalize"
                :canEditDeleteInvoice="canEditDeleteInvoice"
                :isEditing="isEditing"
                :isDeleted="isDeleted"
            />
        </div>
        <div v-else>
            <DesktopDetail
                v-bind="props"
                :actions="actions"
                :validationItems="validationItems"
                :allowFinalize="allowFinalize"
                :canEditDeleteInvoice="canEditDeleteInvoice"
                :isEditing="isEditing"
                :isDeleted="isDeleted"
            />
        </div>
    </AuthenticatedLayout>
</template>
