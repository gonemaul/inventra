<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";
const props = defineProps({
    purchase: Object,
    invoice: Object,
    linkedItems: Object,
    validateInvoice: Object,
});
console.log(props.invoice);
// Hitung Realisasi (Total item yang sudah discan/link)
const totalScanned = computed(() => {
    return props.linkedItems.reduce((acc, item) => {
        return acc + (item.quantity || 0) * (item.purchase_price || 0);
    }, 0);
});

const invoiceBalance = computed(() => {
    const target = props.invoice.total_amount || 0;
    // Hitung total dari linkedItems (karena parent me-refresh props ini setelah save)
    const current = props.linkedItems.reduce((acc, item) => {
        return acc + (item.quantity || 0) * (item.purchase_price || 0);
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
        class="sticky top-0 z-30 bg-white border-b border-gray-100 shadow-lg dark:bg-gray-900 shadow-gray-200/50 dark:shadow-none dark:border-gray-800 rounded-b-3xl"
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

                            <h1
                                class="text-lg font-black tracking-tight text-gray-800 dark:text-white"
                            >
                                #{{ invoice.invoice_number }}
                            </h1>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-8 h-8 text-gray-500 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-100"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                ></path>
                            </svg>
                        </div>
                        <div>
                            <p
                                class="text-xs font-bold text-gray-800 dark:text-white"
                            >
                                {{ purchase.supplier?.name || "Umum/Cash" }}
                            </p>
                            <p
                                class="w-40 text-[10px] text-gray-500 truncate dark:text-gray-400"
                            >
                                {{ purchase.supplier?.address || "-" }}
                                |
                                {{ purchase.supplier?.phone || "-" }}
                            </p>
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

            <div
                class="relative h-3 mb-4 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-700"
            >
                <div
                    class="absolute top-0 left-0 h-full transition-all duration-500 ease-out rounded-full"
                    :class="
                        isReadyToValidate
                            ? 'bg-lime-500 shadow-[0_0_10px_rgba(132,204,22,0.6)]'
                            : 'bg-blue-500'
                    "
                    :style="{ width: `${progressPercentage}%` }"
                ></div>

                <span
                    class="absolute inset-0 flex items-center justify-center text-[9px] font-bold text-gray-700 z-10"
                    :class="
                        progressPercentage > 50
                            ? 'text-white drop-shadow-md'
                            : 'text-gray-600'
                    "
                >
                    {{ Math.round(progressPercentage) }}%
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 h-[72px]">
                <div
                    class="flex flex-col justify-center p-3 border border-gray-300 bg-gray-50 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                >
                    <p
                        class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-0.5"
                    >
                        Total Nota
                    </p>
                    <p
                        class="text-sm font-bold text-gray-800 truncate dark:text-gray-200"
                    >
                        {{ formatRupiah(invoice.total_amount) }}
                    </p>
                </div>

                <transition name="fade" mode="out-in">
                    <button
                        v-if="
                            isReadyToValidate && invoice.status !== 'validated'
                        "
                        @click="validateInvoice"
                        class="relative flex items-center justify-between w-full h-full px-4 overflow-hidden text-white transition-all shadow-lg bg-lime-500 hover:bg-lime-600 active:scale-95 rounded-2xl shadow-lime-500/40 group"
                    >
                        <div
                            class="absolute inset-0 transition-transform duration-300 translate-y-full bg-white/10 group-hover:translate-y-0"
                        ></div>

                        <div class="relative z-10 text-left">
                            <p
                                class="text-[10px] font-bold opacity-90 uppercase tracking-wider"
                            >
                                Data Cocok
                            </p>
                            <p class="text-sm font-black leading-none">
                                VALIDASI
                            </p>
                        </div>
                        <div
                            class="bg-white/20 p-1.5 rounded-full relative z-10 group-hover:translate-x-1 transition-transform"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </button>

                    <div
                        v-else
                        :class="[
                            'w-full h-full p-3 rounded-2xl border flex flex-col justify-center relative overflow-hidden',
                            Math.abs(invoiceBalance) < 100
                                ? 'bg-teal-50 border-teal-200 text-teal-800' // Sudah Validated
                                : 'bg-white dark:bg-gray-800 border-red-100 text-red-600', // Belum Balance
                        ]"
                    >
                        <div class="relative z-10">
                            <p
                                class="text-[10px] uppercase font-bold tracking-wider mb-0.5 opacity-70"
                            >
                                {{
                                    Math.abs(invoiceBalance) < 100
                                        ? "Status"
                                        : "Selisih"
                                }}
                            </p>
                            <p class="text-sm font-black uppercase truncate">
                                {{
                                    Math.abs(invoiceBalance) < 100
                                        ? invoice.status
                                        : formatRupiah(invoiceBalance)
                                }}
                            </p>
                        </div>

                        <svg
                            v-if="Math.abs(invoiceBalance) >= 100"
                            xmlns="http://www.w3.org/2000/svg"
                            class="absolute w-10 h-10 -right-2 -bottom-2 opacity-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            class="absolute w-10 h-10 -right-2 -bottom-2 opacity-10"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </transition>
            </div>
        </div>
    </header>
</template>
