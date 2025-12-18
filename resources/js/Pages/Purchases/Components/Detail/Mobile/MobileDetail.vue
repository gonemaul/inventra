<script setup>
import { ref, computed } from "vue";
import ImageModal from "@/Components/ImageModal.vue";
import HeadMobile from "./HeadMobile.vue";
import InvoiceMobile from "./InvoiceMobile.vue";
import { Link } from "@inertiajs/vue3";
import ProdukMobile from "./ProdukMobile.vue";
import StatusModalMobile from "./StatusModalMobile.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import BottomSheet from "../../../../../Components/BottomSheet.vue";

// --- PROPS ---
const props = defineProps({
    purchase: Object,
    invoices: Array,
    // Actions berisi kumpulan fungsi dari Parent
    actions: {
        type: Object,
        required: true,
        // Struktur: { openCreateInvoiceModal, handleEditInvoice, handleDelete, updateStatus, getActions, ... }
    },
    validationItems: Array,
    allowFinalize: Boolean, // State apakah tombol 'Selesaikan Validasi' aktif
    canEditDeleteInvoice: Boolean,
    isEditing: Boolean,
});

// --- STATE ---
const activeTab = ref("invoices");
const showActionSheet = ref(false); // Menu titik tiga
const showStatusModal = ref(false); // Modal ubah status
const targetStatus = ref("");

const updateStatus = (purchase, newStatus) => {
    const config = {
        data: { status: newStatus },
    };
    targetStatus.value = newStatus;
    showStatusModal.value.open(config);
};
// --- COMPUTED / HELPERS ---
const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          })
        : "-";

const getStatusColor = (status) => {
    const map = {
        draft: "bg-gray-100 text-gray-600",
        ordered: "bg-blue-100 text-blue-700 border-blue-200",
        shipped: "bg-yellow-100 text-yellow-700 border-yellow-200",
        received: "bg-lime-100 text-lime-700 border-lime-200",
        checking: "bg-purple-100 text-purple-700 border-purple-200",
        completed: "bg-green-100 text-green-700 border-green-200",
        cancelled: "bg-red-100 text-red-700 border-red-200",
    };
    return map[status] || "bg-gray-100";
};

const isOpsi = computed(() => {
    return (
        props.isEditing ||
        props.isDeleted ||
        props.purchase.status != "selesai" ||
        props.purchase.status != "dibatalkan"
    );
});
const invoiceCount = computed(() => {
    props.purchase?.invoices.length || 0;
});
</script>

<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedInvoiceCode"
        @close="showImageModal = false"
    />
    <div
        class="absolute top-0 left-0 min-h-screen font-sans text-gray-800 bg-gray-100 dark:bg-gray-950 dark:text-gray-100 pb-28"
    >
        <header
            class="sticky top-0 z-30 flex items-center justify-between px-4 py-3 mb-3 bg-white border-b border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800"
        >
            <div class="flex items-center gap-3">
                <Link
                    :href="route('purchases.index')"
                    class="p-2 -ml-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-sm font-bold leading-none">
                        {{ purchase.reference_no || "PO-####" }}
                    </h1>
                    <span
                        class="text-[10px] text-gray-500 dark:text-gray-400"
                        >{{ formatDate(purchase.transaction_date) }}</span
                    >
                </div>
            </div>
            <span
                :class="[
                    'px-3 py-1 rounded-full text-[10px] uppercase font-bold border dark:text-gray-900 text-gray-200',
                    getStatusColor(purchase.status),
                ]"
            >
                {{ purchase.status }}
            </span>
        </header>

        <main class="mx-3 space-y-4">
            <HeadMobile :purchase="purchase" :invoice-count="invoiceCount" />

            <div class="flex bg-gray-200 rounded-lg dark:bg-gray-800">
                <button
                    @click="activeTab = 'invoices'"
                    :class="[
                        'flex-1 py-2 text-sm font-bold rounded-md transition',
                        activeTab === 'invoices'
                            ? 'bg-white dark:bg-gray-700 text-lime-600 shadow'
                            : 'text-gray-500',
                    ]"
                >
                    Invoice ({{ purchase?.invoices?.length }})
                </button>
                <button
                    @click="activeTab = 'products'"
                    :class="[
                        'flex-1 py-2 text-sm font-bold rounded-md transition',
                        activeTab === 'products'
                            ? 'bg-white dark:bg-gray-700 text-lime-600 shadow'
                            : 'text-gray-500',
                    ]"
                >
                    Produk ({{ purchase.items?.length || 0 }})
                </button>
            </div>

            <InvoiceMobile
                v-if="activeTab === 'invoices'"
                :purchase="purchase"
                :isCheckingMode="isCheckingMode"
                :canEditDelete="canEditDeleteInvoice"
                :actions="actions"
            />
            <ProdukMobile
                v-if="activeTab === 'products'"
                :purchase="purchase"
            />
        </main>

        <div
            class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 p-4 pb-6 shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.1)] z-40"
        >
            <div class="flex gap-3">
                <button
                    v-if="isOpsi"
                    @click="showActionSheet = true"
                    class="p-3.5 rounded-xl bg-gray-200 shadow-md dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>

                <button
                    v-if="allowFinalize"
                    @click="actions.openFinalizeModal()"
                    class="flex-1 py-3.5 rounded-xl bg-lime-500 text-white font-bold text-sm shadow-lg shadow-lime-500/30 flex items-center justify-center gap-2"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    Selesaikan Validasi
                </button>

                <button
                    v-else-if="purchase.status == 'draft'"
                    @click="actions.openImageModal"
                    class="flex-1 py-3.5 rounded-xl bg-green-600 text-white font-bold text-sm shadow-lg shadow-green-500/30 flex items-center justify-center gap-2"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.466c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"
                        />
                    </svg>
                    Order WA
                </button>
                <a
                    v-else
                    :href="route('purchases.print', purchase.id)"
                    target="_blank"
                    class="flex-1 py-3.5 rounded-xl bg-gray-600 text-white font-bold text-sm shadow-lg shadow-gray-500/30 flex items-center justify-center gap-2"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Cetak
                </a>
            </div>
        </div>

        <BottomSheet
            :show="showActionSheet"
            @close="showActionSheet = false"
            title="Aksi Lainnya"
        >
            <div class="space-y-2">
                <button
                    v-for="(action, index) in actions.getActions(purchase)"
                    :key="index"
                    @click="
                        showActionSheet = false;
                        // showStatusModal = true;
                        updateStatus(purchase, action.newStatus);
                        // actions.updateStatus(purchase, action.newStatus);
                    "
                    class="flex items-center w-full gap-3 p-3 text-left rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <div class="p-2 text-purple-600 bg-purple-100 rounded-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v3.276a1 1 0 01-2 0V13.116a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <div class="">
                        <span
                            class="font-medium text-gray-800 dark:text-gray-200"
                            >Ubah Status Purchase</span
                        >
                        <p class="text-xs text-red-800">
                            {{ action.label }}
                        </p>
                    </div>
                </button>

                <Link
                    v-if="isEditing"
                    :href="route('purchases.edit', purchase.id)"
                    @click="showActionSheet = false"
                    class="flex items-center w-full gap-3 p-3 text-left rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <div class="p-2 text-blue-600 bg-blue-100 rounded-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                            />
                        </svg>
                    </div>
                    <span class="font-medium">Edit Informasi PO</span>
                </Link>

                <!-- <button
                    @click="
                        showActionSheet = false;
                        $emit('print-order');
                    "
                    class="flex items-center w-full gap-3 p-3 text-left rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800"
                >
                    <div class="p-2 text-gray-600 bg-gray-100 rounded-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span class="font-medium">Cetak / Download PDF</span>
                </button> -->

                <button
                    v-if="isDeleted"
                    @click="
                        showActionSheet = false;
                        actions.handleDelete();
                    "
                    class="flex items-center w-full gap-3 p-3 text-left text-red-600 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/10"
                >
                    <div class="p-2 text-red-600 bg-red-100 rounded-lg">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                    <span class="font-medium">Hapus Purchase</span>
                </button>
            </div>
        </BottomSheet>
        <StatusModalMobile
            ref="showStatusModal"
            :purchase="purchase"
            v-bind:target-status="targetStatus"
        />
    </div>
</template>
<style scoped>
/* Animasi Slide Up untuk Modal */
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}
</style>
