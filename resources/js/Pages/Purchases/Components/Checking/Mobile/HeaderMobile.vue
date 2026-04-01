<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
const props = defineProps({
    purchase: Object,
    invoice: Object,
    linkedItems: Object,
    validateInvoice: Object,
});
// Hitung Realisasi (Total item yang sudah discan/link)
const totalScanned = computed(() => {
    return (props.linkedItems || []).reduce((acc, item) => {
        return acc + (item.subtotal || 0);
    }, 0);
});

const invoiceBalance = computed(() => {
    const target = props.invoice.total_amount || 0;
    // Hitung total dari linkedItems (karena parent me-refresh props ini setelah save)
    const current = props.linkedItems.reduce((acc, item) => {
        return acc + (item.subtotal || 0);
    }, 0);
    return target - current;
});

const isReadyToValidate = computed(() => {
    // Balance 0 (toleransi floating point < 100 perak) DAN ada item yg discan
    return Math.abs(invoiceBalance.value) < 100 && props.linkedItems.length > 0;
});

// Hitung Persentase Progress
const progressPercentage = computed(() => {
    const target = props.invoice.total_amount || 1; // Hindari bagi 0
    let percent = (totalScanned.value / target) * 100;
    return Math.min(Math.max(percent, 0), 100); // Clamp 0-100%
});

// --- COMPUTED DATA & HELPERS ---
const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
const formatTanggal = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          })
        : "-";
</script>
<template>
    <header
        class="z-30 bg-white border-b border-gray-100 shadow-lg dark:bg-gray-900 shadow-gray-200/50 dark:shadow-none dark:border-gray-800 rounded-b-3xl"
    >
        <div class="px-5 pt-5 pb-6">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <div class="flex items-start">
                        <Link
                            :href="route('purchases.checking', purchase.id)"
                            class="p-2 mr-3 -ml-2 text-gray-400 transition-colors rounded-full hover:text-gray-800 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-800"
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
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </Link>
                        <div class="flex items-center gap-2 mb-1">
                            <span
                                :class="[
                                    'w-1.5 h-8 rounded-full',
                                    isReadyToValidate
                                        ? 'bg-lime-500 animate-pulse'
                                        : 'bg-gray-300 dark:bg-gray-700',
                                ]"
                            ></span>
                            <div class="space-y-0.5">
                                <h1 class="text-sm font-bold leading-none t-gray-800 dark:text-gray-100">
                                    {{ purchase.reference_no || "PO-####" }}
                                </h1>
                                <h1
                                    class="text-xs font-black tracking-tight text-gray-800 dark:text-gray-400"
                                >
                                    Invoice : #{{ invoice.invoice_number }} |
                                    {{ formatTanggal(invoice.invoice_date) }}
                                </h1>
                                <!-- <span
                                    class="text-[10px] text-gray-500 dark:text-gray-400"
                                    >{{
                                        formatTanggal(invoice.invoice_date)
                                    }}</span
                                > -->
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 my-2">
                        <div
                            class="flex items-center justify-center w-10 h-10 text-indigo-600 border border-indigo-100 rounded-lg shrink-0 bg-indigo-50 dark:bg-indigo-900/30 dark:text-indigo-400 dark:border-indigo-800"
                        >
                            <span class="text-sm font-black uppercase">
                                {{
                                    (purchase.supplier?.name || "U").substring(
                                        0,
                                        1
                                    )
                                }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-bold text-gray-800 truncate dark:text-gray-100"
                            >
                                {{
                                    purchase.supplier?.name ||
                                    "Supplier Umum/Cash"
                                }}
                            </p>

                            <div
                                class="flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400 mt-0.5"
                            >
                                <span class="truncate max-w-[120px]">
                                    {{
                                        purchase.supplier?.address ||
                                        "Tanpa Alamat"
                                    }}
                                </span>

                                <span class="text-gray-300 dark:text-gray-600"
                                    >•</span
                                >

                                <span
                                    class="font-mono text-gray-600 dark:text-gray-300 whitespace-nowrap"
                                >
                                    {{ purchase.supplier?.phone || "-" }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <span
                        :class="[
                            'px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border',
                            invoice.status === 'validated'
                                ? 'bg-teal-50 text-teal-700 border-teal-200'
                                : 'bg-orange-50 text-orange-700 border-orange-200',
                        ]"
                    >
                        {{ invoice.status }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Jatuh Tempo:
                        <span class="font-bold text-red-500">{{
                            formatTanggal(invoice.due_date)
                        }}</span>
                    </p>
                </div>
            </div>

        </div>
    </header>
</template>
