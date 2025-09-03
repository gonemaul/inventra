<script setup>
import Modal from "@/Components/Modal.vue";
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    // item: {
    //     type: Object,
    //     default: () => ({}),
    // },
});

const emit = defineEmits(["close", "delete"]);
</script>
<template>
    <Modal :show="show" @close="$emit('close')" max-width="md">
        <div
            class="w-full max-w-md p-6 bg-white shadow-lg rounded-xl dark:bg-gray-800"
        >
            <!-- Header -->
            <div
                class="relative flex items-center justify-center pb-3 border-b"
            >
                <!-- Judul selalu center -->
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Konfirmasi Hapus
                </h2>

                <!-- Tombol close di kanan atas -->
                <button
                    @click="$emit('close')"
                    class="absolute right-0 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>

            <div class="items-center mt-4 space-y-4">
                <div class="flex justify-center w-full">
                    <svg
                        width="102"
                        height="103"
                        viewBox="0 0 102 103"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <rect
                            x="7.5"
                            y="8"
                            width="87"
                            height="87"
                            rx="43.5"
                            fill="#E20004"
                            fill-opacity="0.16"
                        />
                        <rect
                            x="7.5"
                            y="8"
                            width="87"
                            height="87"
                            rx="43.5"
                            stroke="#FF0004"
                            stroke-opacity="0.4"
                            stroke-width="14.3333"
                        />
                        <path
                            d="M38.4583 44.3334V62.25C38.4583 66.2081 41.667 69.4167 45.625 69.4167H56.375C60.333 69.4167 63.5417 66.2081 63.5417 62.25V44.3334M54.5833 49.7084V60.4584M47.4167 49.7084L47.4167 60.4584M58.1667 38.9584L55.6471 35.179C54.9825 34.1822 53.8637 33.5834 52.6656 33.5834H49.3344C48.1363 33.5834 47.0175 34.1822 46.3529 35.179L43.8333 38.9584M58.1667 38.9584H43.8333M58.1667 38.9584H67.125M43.8333 38.9584H34.875"
                            stroke="#FF1F23"
                            stroke-width="3"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                </div>
                <p
                    class="text-lg font-medium text-center text-gray-500 dark:text-gray-300"
                >
                    Apakah anda Yakin untuk menghapus <b>Produk A</b> ?
                </p>
                <!-- Action Buttons -->
                <div class="flex justify-between gap-2 pt-3 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="w-full px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 border-2 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="w-full px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
