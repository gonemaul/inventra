<script setup>
import Modal from "@/Components/Modal.vue";
import { ref } from "vue";

// Menerima props 'show' dari parent untuk mengontrol visibilitas
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

// Emit event ke parent
const emit = defineEmits(["close", "mode-selected"]);

const closeModal = () => {
    emit("close");
};

const selectMode = (mode) => {
    // Kirim mode yang dipilih ('single' atau 'continuous') ke parent
    emit("mode-selected", mode);
    closeModal();
};
</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div
            class="p-6 transition-colors duration-300 bg-white dark:bg-gray-800"
        >
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2
                        class="text-xl font-bold tracking-tight text-gray-900 dark:text-white"
                    >
                        Mode Scanner
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Pilih metode scan yang ingin digunakan
                    </p>
                </div>

                <button
                    @click="closeModal"
                    class="text-gray-400 transition-colors hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <span class="sr-only">Close</span>
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <button
                    @click="selectMode('single')"
                    class="relative flex flex-col items-center justify-center p-6 transition-all duration-300 bg-white border-2 border-gray-100 shadow-lg group rounded-2xl dark:bg-gray-700 hover:shadow-lime-500/20 dark:shadow-black/30 hover:border-lime-500 dark:hover:border-lime-400 hover:-translate-y-1"
                >
                    <div
                        class="p-4 mb-4 transition-colors duration-300 rounded-full bg-lime-50 text-lime-600 dark:bg-gray-600 dark:text-lime-400 group-hover:bg-lime-500 group-hover:text-white"
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
                                stroke-width="1.5"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                    </div>

                    <span
                        class="text-lg font-bold text-gray-800 transition-colors dark:text-gray-100 group-hover:text-lime-600 dark:group-hover:text-lime-400"
                    >
                        Scan 1x
                    </span>

                    <span
                        class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        Scan satu produk lalu tutup
                    </span>
                </button>

                <button
                    @click="selectMode('continuous')"
                    class="relative flex flex-col items-center justify-center p-6 transition-all duration-300 bg-white border-2 border-gray-100 shadow-lg group rounded-2xl dark:bg-gray-700 hover:shadow-lime-500/20 dark:shadow-black/30 hover:border-lime-500 dark:hover:border-lime-400 hover:-translate-y-1"
                >
                    <div
                        class="p-4 mb-4 transition-colors duration-300 rounded-full bg-lime-50 text-lime-600 dark:bg-gray-600 dark:text-lime-400 group-hover:bg-lime-500 group-hover:text-white"
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
                                stroke-width="1.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </div>

                    <span
                        class="text-lg font-bold text-gray-800 transition-colors dark:text-gray-100 group-hover:text-lime-600 dark:group-hover:text-lime-400"
                    >
                        Scan Beruntun
                    </span>

                    <span
                        class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        Scan banyak tanpa jeda
                    </span>
                </button>
            </div>

            <div class="mt-8 text-center">
                <button
                    @click="closeModal"
                    class="text-sm font-medium text-gray-400 underline transition-colors hover:text-gray-600 dark:hover:text-gray-200 decoration-dotted"
                >
                    Batalkan
                </button>
            </div>
        </div>
    </Modal>
</template>
