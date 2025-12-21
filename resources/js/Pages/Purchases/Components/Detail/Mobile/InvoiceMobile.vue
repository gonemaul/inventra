<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import ImageModal from "@/Components/ImageModal.vue";

const props = defineProps({
    purchase: { type: Array, required: true },
    isCheckingMode: { type: Boolean, default: false }, // Menentukan visibility tombol Aksi
    canEditDelete: Boolean,
    actions: Object,
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
const isDetail = computed(() => {
    return (
        props.purchase.status !== "diterima" &&
        props.purchase.status !== "checking"
    );
});
const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          })
        : "-";
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);
const getValidationColor = (status) => {
    if (status === "validated") {
        return "bg-teal-50 text-teal-700 border-teal-200"; // Teal: Terverifikasi
    }
    return "bg-blue-50 text-blue-700 border-blue-200"; // Biru: Baru Upload
};
const getPaymentColor = (status) => {
    switch (status) {
        case "paid":
            return "bg-green-100 text-green-800"; // Hijau: Lunas
        case "partial":
            return "bg-yellow-100 text-yellow-800"; // Kuning: Cicil
        default: // unpaid
            return "bg-orange-100 text-orange-800"; // Orange: Hutang
    }
};
const isNew = computed(() => {
    return (
        props.purchase.status == "diterima" ||
        props.purchase.status == "checking"
    );
});
</script>
<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedInvoiceCode"
        @close="showImageModal = false"
    />
    <div class="space-y-4">
        <button
            v-if="isNew"
            @click="actions.openCreateInvoiceModal()"
            class="flex items-center justify-center w-full gap-2 py-3 font-bold transition border-2 border-dashed border-lime-300 dark:border-lime-800 bg-lime-50 dark:bg-lime-900/10 text-lime-700 dark:text-lime-400 rounded-xl hover:bg-lime-100"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd"
                />
            </svg>
            Buat Nota Baru
        </button>

        <div
            v-for="inv in purchase.invoices"
            :key="inv.id"
            class="relative p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 rounded-xl dark:border-gray-800"
        >
            <div class="flex items-start justify-between">
                <div>
                    <span
                        class="px-2 py-1 text-xs font-bold text-gray-800 bg-gray-100 rounded dark:text-white dark:bg-gray-800"
                        >#{{ inv.invoice_number }}</span
                    >
                    <p class="text-[10px] text-gray-400 mt-1">
                        {{ formatDate(inv.invoice_date) }}
                    </p>
                    <p class="text-[10px] text-red-400 mt-1 font-medium">
                        {{ inv.due_date ? formatDate(inv.due_date) : "Tunai" }}
                    </p>
                </div>
                <div class="text-right items-end gap-1.5">
                    <div class="flex gap-1">
                        <span
                            :class="[
                                'text-[9px] px-2 py-0.5 rounded font-bold uppercase border tracking-wider',
                                getValidationColor(inv.status),
                            ]"
                        >
                            {{ inv.status }}
                        </span>
                        <span
                            :class="[
                                'text-[9px] px-2 py-0.5 rounded font-bold uppercase',
                                getPaymentColor(inv.payment_status),
                            ]"
                        >
                            {{ inv.payment_status }}
                        </span>
                    </div>
                    <p
                        class="text-sm font-bold text-gray-800 dark:text-gray-200 mt-0.5"
                    >
                        {{ rp(inv.total_amount) }}
                    </p>
                </div>
            </div>

            <div class="h-px my-2 bg-gray-100 dark:bg-gray-800"></div>

            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <button
                        v-if="canEditDelete"
                        @click="actions.handleEditInvoice(inv)"
                        class="p-2 text-blue-600 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                            />
                        </svg>
                    </button>
                    <button
                        v-if="canEditDelete"
                        @click="actions.handleDeleteInvoice(inv)"
                        class="p-2 text-red-600 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                    <button
                        v-if="inv.invoice_image"
                        @click="
                            openImageModal(inv.invoice_url, inv.invoice_number)
                        "
                        class="p-2 text-gray-600 border border-gray-200 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                    </button>
                </div>

                <Link
                    :href="
                        route('purchases.linkInvoiceItems', {
                            purchase: purchase.id,
                            invoice: inv.id,
                        })
                    "
                    class="flex items-center gap-1 px-4 py-2 text-xs font-bold text-white rounded-lg shadow-sm bg-lime-500 hover:bg-lime-600"
                >
                    <span>{{
                        isDetail || inv.status == "validated"
                            ? "Detail"
                            : "Checking"
                    }}</span>
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-3 h-3"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </Link>
            </div>
        </div>

        <div
            v-if="purchase.invoices.length === 0"
            class="py-10 text-sm text-center text-gray-400"
        >
            Belum ada nota.
        </div>
    </div>
</template>
