<script setup>
import Modal from "@/Components/Modal.vue";
import { ref } from "vue";
defineProps({
    showConfirmModal: {
        type: Boolean,
        default: false,
    },
    changeAmount: {
        type: Number,
        default: 0,
    },
});

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const processingTransaction = ref(null);
const handleClick = (type) => {
    processingTransaction.value = type;
    emit("confirmTransaction", type === "print");
};
const emit = defineEmits(["close", "confirmTransaction"]);
</script>
<template>
    <Modal :show="showConfirmModal" @close="$emit('close')" max-width="md">
        <div
            class="relative z-10 w-full p-6 overflow-hidden text-center transition-all transform scale-100 bg-white shadow-2xl dark:bg-gray-800 rounded-3xl"
        >
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
            <p
                class="pb-4 mb-6 text-sm text-gray-500 border-b dark:text-gray-400 dark:border-gray-700"
            >
                Kembalian:
                <span class="block mt-1 text-lg font-bold text-lime-600">{{
                    rp(Math.abs(changeAmount))
                }}</span>
            </p>

            <div class="flex flex-col gap-3">
                <button
                    @click="handleClick('print')"
                    :disabled="processingTransaction"
                    class="w-full py-3.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-2xl shadow-lg transition active:scale-95 flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span
                        v-if="processingTransaction === 'print'"
                        class="w-4 h-4 border-2 border-current rounded-full animate-spin border-t-transparent"
                    ></span>
                    <span v-else>🖨️ Simpan & Cetak</span>
                </button>

                <button
                    @click="handleClick('save')"
                    :disabled="processingTransaction"
                    class="w-full py-3.5 bg-lime-500 hover:bg-lime-600 text-white font-bold rounded-2xl shadow-lg transition active:scale-95 flex justify-center items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <span
                        v-if="processingTransaction === 'save'"
                        class="w-4 h-4 border-2 border-current rounded-full animate-spin border-t-transparent"
                    ></span>
                    <span v-else>💾 Simpan Saja</span>
                </button>

                <button
                    @click="$emit('close')"
                    :disabled="processingTransaction"
                    class="mt-2 text-xs font-medium text-gray-400 transition-colors hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Batal / Revisi
                </button>
            </div>
        </div>
    </Modal>
</template>
