<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    purchase: Object,
    invoices: Object,
    invoice: Object,
    paymentStatuses: Array,
    isCheckingMode: Boolean,
    // Props dari Parent
    actions: Object,
    validationItems: Array,
    allowFinalize: Boolean,
    canEditDeleteInvoice: Boolean,
    isEditing: Boolean,
});

// State Tab Mobile
const activeTab = ref("invoices"); // 'invoices' or 'products'

// Helper Format Rupiah
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);

// Helper Status Color
const getStatusColor = (status) => {
    const map = {
        draft: "bg-gray-100 text-gray-600",
        dipesan: "bg-blue-100 text-blue-600",
        dikirim: "bg-yellow-100 text-yellow-700",
        diterima: "bg-orange-100 text-orange-700",
        checking: "bg-purple-100 text-purple-700",
        completed: "bg-green-100 text-green-700",
    };
    return map[status] || "bg-gray-100";
};
</script>

<template>
    <div class="min-h-screen pb-32 font-sans bg-gray-100 dark:bg-gray-900">
        <div class="sticky top-0 z-30 bg-white shadow-sm dark:bg-gray-800">
            <div
                class="flex items-center justify-between px-4 py-3 border-b dark:border-gray-700"
            >
                <Link
                    :href="route('purchases.index')"
                    class="p-2 -ml-2 text-gray-600 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-300"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        ></path>
                    </svg>
                </Link>
                <div class="text-center">
                    <div
                        class="text-[10px] uppercase font-bold text-gray-400 tracking-wider"
                    >
                        Purchase Order
                    </div>
                    <div
                        class="text-sm font-black text-gray-800 dark:text-white"
                    >
                        #{{ purchase.reference_no }}
                    </div>
                </div>
                <div class="relative">
                    <span
                        :class="getStatusColor(purchase.status)"
                        class="px-2 py-1 rounded text-[10px] font-bold uppercase"
                    >
                        {{ purchase.status }}
                    </span>
                </div>
            </div>

            <div class="px-4 py-3 text-sm bg-gray-50 dark:bg-gray-900/50">
                <div class="flex justify-between mb-1">
                    <span class="text-gray-500">Supplier</span>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{
                        purchase.supplier?.name || "-"
                    }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal</span>
                    <span
                        class="font-medium text-gray-800 dark:text-gray-200"
                        >{{ purchase.transaction_date }}</span
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 p-2 bg-white dark:bg-gray-800">
                <button
                    @click="activeTab = 'invoices'"
                    :class="
                        activeTab === 'invoices'
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300'
                    "
                    class="py-2.5 rounded-xl text-xs font-bold uppercase transition-all"
                >
                    📄 Invoice ({{ purchase.invoices?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'products'"
                    :class="
                        activeTab === 'products'
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300'
                    "
                    class="py-2.5 rounded-xl text-xs font-bold uppercase transition-all"
                >
                    📦 Validasi Item
                </button>
            </div>
        </div>

        <div class="p-4 space-y-4">
            <div v-if="activeTab === 'invoices'" class="space-y-3">
                <div
                    v-if="!purchase.invoices.length"
                    class="py-10 text-center bg-white border-2 border-gray-200 border-dashed dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <p class="mb-2 text-sm text-gray-400">
                        Belum ada nota yang diinput
                    </p>
                    <button
                        v-if="canEditDeleteInvoice"
                        @click="actions.openCreateInvoiceModal"
                        class="text-sm font-bold text-indigo-600 underline"
                    >
                        + Input Nota Sekarang
                    </button>
                </div>

                <div
                    v-for="inv in purchase.invoices"
                    :key="inv.id"
                    class="relative p-4 overflow-hidden bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <div
                        class="absolute left-0 top-0 bottom-0 w-1.5"
                        :class="
                            inv.status === 'validated'
                                ? 'bg-green-500'
                                : 'bg-yellow-500'
                        "
                    ></div>

                    <div class="flex items-start justify-between pl-3 mb-2">
                        <div>
                            <h4 class="font-bold text-gray-800 dark:text-white">
                                {{ inv.invoice_number }}
                            </h4>
                            <p
                                class="text-[10px] text-gray-400 uppercase tracking-wide"
                            >
                                {{ inv.payment_status }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div
                                class="font-black text-indigo-600 dark:text-indigo-400"
                            >
                                {{ rp(inv.total_amount) }}
                            </div>
                            <span
                                v-if="inv.due_date"
                                class="text-[10px] text-red-500 bg-red-50 px-1.5 py-0.5 rounded"
                            >
                                Tempo: {{ inv.due_date }}
                            </span>
                        </div>
                    </div>

                    <div v-if="inv.image_path" class="pl-3 mb-3">
                        <img
                            :src="`/storage/${inv.image_path}`"
                            class="object-cover w-16 h-16 border rounded-lg dark:border-gray-600"
                        />
                    </div>

                    <div
                        v-if="canEditDeleteInvoice"
                        class="flex gap-2 pt-3 pl-3 mt-3 border-t border-gray-100 dark:border-gray-700"
                    >
                        <button
                            @click="actions.handleEditInvoice(inv)"
                            class="flex-1 py-2 text-xs font-bold text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200"
                        >
                            Edit
                        </button>
                        <button
                            @click="actions.handleDeleteInvoice(inv)"
                            class="flex-1 py-2 text-xs font-bold text-red-600 rounded-lg bg-red-50 hover:bg-red-100"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="space-y-3">
                <div
                    v-for="item in validationItems"
                    :key="item.id"
                    class="relative p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <div class="absolute top-4 right-4">
                        <svg
                            v-if="item.is_valid"
                            class="w-6 h-6 text-green-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <svg
                            v-else
                            class="w-6 h-6 text-red-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>

                    <h4
                        class="pr-8 mb-2 font-bold text-gray-800 dark:text-white"
                    >
                        {{ item.product?.name }}
                    </h4>

                    <div class="grid grid-cols-3 gap-2 text-xs text-center">
                        <div
                            class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20"
                        >
                            <span
                                class="block text-gray-400 text-[10px] uppercase"
                                >Pesan</span
                            >
                            <span
                                class="text-sm font-bold text-blue-600 dark:text-blue-400"
                                >{{ item.quantity }}</span
                            >
                        </div>
                        <div
                            class="p-2 rounded-lg bg-red-50 dark:bg-red-900/20"
                        >
                            <span
                                class="block text-gray-400 text-[10px] uppercase"
                                >Reject</span
                            >
                            <span
                                class="text-sm font-bold text-red-600 dark:text-red-400"
                                >{{ item.rejected_quantity }}</span
                            >
                        </div>
                        <div
                            class="p-2 border border-green-100 rounded-lg bg-green-50 dark:bg-green-900/20"
                        >
                            <span
                                class="block text-gray-400 text-[10px] uppercase"
                                >Valid</span
                            >
                            <span
                                class="text-sm font-bold text-green-600 dark:text-green-400"
                            >
                                {{ item.quantity - item.rejected_quantity }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="fixed bottom-0 left-0 w-full bg-white dark:bg-gray-800 border-t dark:border-gray-700 p-4 shadow-[0_-4px_20px_rgba(0,0,0,0.1)] z-40 safe-area-pb"
        >
            <div class="flex gap-3">
                <button
                    v-if="purchase.status === 'checking'"
                    @click="actions.openFinalizeModal"
                    :disabled="!allowFinalize"
                    :class="
                        allowFinalize
                            ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-indigo-500/30'
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    "
                    class="flex-1 py-3.5 rounded-xl font-bold text-sm shadow-lg transition active:scale-95 flex justify-center items-center gap-2"
                >
                    <svg
                        v-if="allowFinalize"
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                    <span>{{
                        allowFinalize ? "Selesaikan Validasi" : "Belum Lengkap"
                    }}</span>
                </button>

                <button
                    v-for="(action, index) in actions.getActions(purchase)"
                    :key="index"
                    @click="actions.updateStatus(purchase, action.newStatus)"
                    class="flex-1 py-3.5 bg-lime-500 hover:bg-lime-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-lime-500/30 transition active:scale-95"
                >
                    {{ action.label }}
                </button>

                <button
                    v-if="activeTab === 'invoices' && canEditDeleteInvoice"
                    @click="actions.openCreateInvoiceModal"
                    class="flex items-center justify-center w-12 h-12 text-white bg-gray-900 shadow-lg dark:bg-white dark:text-gray-900 rounded-xl active:scale-95"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Agar tidak ketutup tombol home iPhone */
.safe-area-pb {
    padding-bottom: env(safe-area-inset-bottom, 20px);
}
</style>
