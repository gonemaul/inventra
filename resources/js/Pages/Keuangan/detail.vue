<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import PaymentModal from "./partials/PaymentModal.vue";
import ImageModal from "@/Components/ImageModal.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({ invoice: Object, paymentMethods: Array });
// --- HELPERS ---
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });

// state image
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedtName = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedtName.value = "Bukti Pembayaran : " + name;
    showImageModal.value = true;
};

// --- LOGIC HITUNGAN ---
const totalPaid = computed(() => props.invoice.amount_paid);
const remainingDebt = computed(
    () => props.invoice.total_amount - props.invoice.amount_paid
);
const isPaidOff = computed(() => remainingDebt.value <= 0);

// --- LOGIC MODAL & FORM ---
const showModal = ref(false);
</script>

<template>
    <Head title="Bayar Tagihan" />
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedtName"
        @close="showImageModal = false"
    />
    <AuthenticatedLayout :showSidebar="false" :showHeader="false">
        <div
            class="w-full min-h-screen px-4 py-2 sm:px-6 lg:px-8 bg-customBg-light text-customText-light dark:bg-customBg-dark dark:text-customText-dark"
        >
            <div class="flex items-center gap-2 mb-4 text-sm text-gray-500">
                <Link
                    :href="route('finance.index')"
                    class="flex items-center transition hover:text-blue-600"
                    ><svg
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
                    Keuangan</Link
                >
                <span>/</span>
                <span class="font-semibold text-gray-700"
                    >#{{ invoice.invoice_number }}</span
                >
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div
                        class="p-6 bg-white border-l-8 shadow rounded-xl"
                        :class="
                            isPaidOff ? 'border-green-500' : 'border-red-500'
                        "
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">
                                    Invoice #{{ invoice.invoice_number }}
                                </h1>
                                <p class="mt-1 text-gray-500">
                                    Supplier:
                                    <span class="font-semibold text-blue-600">{{
                                        invoice.purchase?.supplier?.name
                                    }}</span>
                                </p>
                                <div class="mt-2 text-sm text-gray-400">
                                    Jatuh Tempo:
                                    {{ formatDate(invoice.due_date) }}
                                </div>
                            </div>

                            <div class="text-right">
                                <p
                                    class="text-sm font-medium tracking-wider text-gray-500 uppercase"
                                >
                                    Sisa Tagihan
                                </p>
                                <div
                                    class="mt-1 text-4xl font-bold"
                                    :class="
                                        isPaidOff
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ formatRupiah(remainingDebt) }}
                                </div>
                                <p class="my-1 text-xs text-gray-400">
                                    Terbayar: {{ formatRupiah(totalPaid) }} dari
                                    {{ formatRupiah(invoice.total_amount) }}
                                </p>
                                <div
                                    class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden"
                                >
                                    <div
                                        class="h-full transition-all duration-500 rounded-full"
                                        :class="
                                            isPaidOff
                                                ? 'bg-green-500'
                                                : 'bg-blue-500'
                                        "
                                        :style="{
                                            width:
                                                Math.min(
                                                    100,
                                                    Math.round(
                                                        (totalPaid /
                                                            invoice.total_amount) *
                                                            100
                                                    )
                                                ) + '%',
                                        }"
                                    ></div>
                                </div>
                                <div
                                    class="flex items-center justify-between mt-1 text-xs text-gray-400"
                                >
                                    <span class="ml-1">
                                        {{
                                            Math.min(
                                                100,
                                                Math.round(
                                                    (totalPaid /
                                                        invoice.total_amount) *
                                                        100
                                                )
                                            )
                                        }}
                                        %
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex gap-3 pt-6 mt-6 border-t"
                            v-if="!isPaidOff"
                        >
                            <button
                                @click="showModal = true"
                                class="flex items-center gap-2 px-6 py-3 font-bold text-white transition transform bg-blue-600 rounded-lg shadow-lg hover:bg-blue-700 active:scale-95"
                            >
                                💳 Input Pembayaran
                            </button>
                        </div>
                        <div
                            v-else
                            class="p-3 pt-6 mt-6 font-bold text-center text-green-700 border-t rounded bg-green-50"
                        >
                            ✅ LUNAS PADA {{ formatDate(invoice.paid_at) }}
                        </div>
                    </div>

                    <div class="overflow-hidden bg-white shadow rounded-xl">
                        <div
                            class="p-4 font-bold text-gray-700 border-b bg-gray-50"
                        >
                            Rincian Pembelian
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="text-gray-500 border-b">
                                <tr>
                                    <th class="p-3">Produk</th>
                                    <th class="p-3 text-right">Qty</th>
                                    <th class="p-3 text-right">Harga Satuan</th>
                                    <th class="p-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in invoice.items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td>
                                        <div class="flex flex-col ml-3">
                                            <span
                                                class="font-bold text-gray-800 dark:text-white"
                                            >
                                                {{ item.product_snapshot.name }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ item.product_snapshot.code }}
                                                |
                                                {{
                                                    item.product_snapshot
                                                        .category
                                                }}
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                {{ item.product_snapshot.size }}
                                                -
                                                {{ item.product_snapshot.unit }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-right">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="p-3 text-right text-gray-500">
                                        {{ formatRupiah(item.purchase_price) }}
                                    </td>
                                    <td class="p-3 font-semibold text-right">
                                        {{ formatRupiah(item.subtotal) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="h-full bg-white shadow rounded-xl">
                        <div
                            class="flex items-center justify-between p-4 font-bold text-gray-700 border-b"
                        >
                            <span>Riwayat Pembayaran</span>
                            <span
                                class="px-2 py-1 text-xs text-gray-600 bg-gray-100 rounded-full"
                                >{{ invoice.payments.length }}x Bayar</span
                            >
                        </div>

                        <div class="p-4 space-y-6">
                            <div
                                v-for="(pay, index) in invoice.payments"
                                :key="pay.id"
                                class="relative pl-6 border-l-2 border-gray-200 last:border-0"
                            >
                                <div
                                    class="absolute -left-[9px] top-0 w-4 h-4 rounded-full border-2 border-white"
                                    :class="
                                        index === 0
                                            ? 'bg-green-500'
                                            : 'bg-gray-300'
                                    "
                                ></div>

                                <div class="mb-1 text-sm text-gray-500">
                                    {{ formatDate(pay.payment_date) }}
                                </div>
                                <div class="text-lg font-bold text-gray-800">
                                    {{ formatRupiah(pay.amount) }}
                                </div>

                                <div
                                    class="inline-block px-2 py-1 mt-1 text-xs text-gray-600 capitalize bg-gray-100 rounded"
                                >
                                    Via {{ pay.payment_method }}
                                </div>

                                <div
                                    v-if="pay.notes"
                                    class="p-2 mt-2 text-sm italic text-gray-600 border border-yellow-100 rounded bg-yellow-50"
                                >
                                    "{{ pay.notes }}"
                                </div>

                                <div v-if="pay.proof_image" class="mt-2">
                                    <span
                                        class="flex items-center gap-1 text-xs text-blue-500 cursor-pointer hover:underline"
                                        @click="
                                            openImageModal(
                                                pay.proof_image_url,
                                                pay.payment_date
                                            )
                                        "
                                    >
                                        📄 Lihat Bukti
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="invoice.payments.length === 0"
                                class="py-8 text-sm italic text-center text-gray-400"
                            >
                                Belum ada pembayaran yang tercatat.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <PaymentModal
                :show="showModal"
                :invoice="invoice"
                :paymentMethods="paymentMethods"
                @close="showModal = false"
            />
        </div>
    </AuthenticatedLayout>
</template>
