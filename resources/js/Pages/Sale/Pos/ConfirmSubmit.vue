<script setup>
import Modal from "@/Components/Modal.vue";
import { watch, ref } from "vue";
const props = defineProps({
    showConfirmModal: {
        type: Boolean,
        default: false,
    },
    changeAmount: {
        type: Number,
        default: 0,
    },
    processing: {
        type: Boolean,
        default: false,
    },
    grandTotal: { type: Number, default: 0 },
    paymentAmount: { type: Number, default: 0 },
});

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const actionType = ref(null);

const handleClick = (type) => {
    actionType.value = type;
    emit("confirmTransaction", type === "print");
};

const emit = defineEmits(["close", "confirmTransaction"]);
</script>
<template>
    <Modal 
        :show="showConfirmModal" 
        @close="!processing && $emit('close')" 
        max-width="md"
        :closeable="!processing"
    >
        <div
            class="relative z-10 w-full p-6 overflow-hidden text-center transition-all transform scale-100 bg-white shadow-2xl dark:bg-gray-800 rounded-3xl"
        >
             <!-- Processing Overlay with Premium Animation -->
            <div v-if="processing" class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-white/90 dark:bg-gray-800/90 backdrop-blur-sm">
                <div class="relative w-24 h-24 mb-4">
                     <!-- Outer Ring -->
                    <div class="absolute inset-0 rounded-full border-4 border-gray-200 dark:border-gray-700"></div>
                     <!-- Spinner -->
                    <div class="absolute inset-0 rounded-full border-4 border-lime-500 border-t-transparent animate-spin"></div>
                     <!-- Inner Icon -->
                    <div class="absolute inset-0 flex items-center justify-center animate-pulse">
                         <span class="text-3xl">🚀</span>
                    </div>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-1">Memproses...</h3>
                <p class="text-xs text-gray-500">Mohon tunggu sebentar</p>
            </div>

            <div
                class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-lime-100 dark:bg-lime-900/30 text-lime-600 dark:text-lime-400 animate-bounce-subtle"
            >
                <svg
                    class="w-8 h-8"
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
            </div>

            <h3 class="mb-2 text-xl font-black text-gray-800 dark:text-white">
                Simpan Transaksi?
            </h3>
            <div class="grid grid-cols-3 gap-2 border-b dark:border-gray-700 pb-4 mb-6">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Tagihan</p>
                    <p class="font-bold text-gray-800 dark:text-white">{{ rp(grandTotal) }}</p>
                </div>
                 <div class="text-center border-l dark:border-gray-700">
                    <p class="text-xs text-gray-500">Bayar</p>
                    <p class="font-bold text-gray-800 dark:text-white">{{ rp(paymentAmount) }}</p>
                </div>
                 <div class="text-center border-l dark:border-gray-700">
                    <p class="text-xs text-gray-500">Kembali</p>
                    <p class="font-bold text-lime-600">{{ rp(Math.abs(changeAmount)) }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex gap-3">
                    <button
                        @click="handleClick('print')"
                        :disabled="processing"
                        class="flex-1 py-3.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-2xl shadow-lg transition active:scale-95 flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <span>🖨️ Cetak</span>
                    </button>
                    <button
                        @click="handleClick('save')"
                        :disabled="processing"
                        class="flex-1 py-3.5 bg-lime-500 hover:bg-lime-600 text-white font-bold rounded-2xl shadow-lg transition active:scale-95 flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                    >
                        <span>💾 Simpan</span>
                    </button>
                </div>

                <button
                    @click="$emit('close')"
                    :disabled="processing"
                    class="mt-1 text-xs font-medium text-gray-400 transition-colors hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Batal / Revisi
                </button>
            </div>
        </div>
    </Modal>
</template>
