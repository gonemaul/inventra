<script setup>
import Modal from "@/Components/Modal.vue";
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["close", "save"]);
</script>
<template>
    <Modal :show="show" @close="$emit('close')" max-width="lg">
        <div
            class="w-full p-6 bg-white shadow-lg maxs-w-md rounded-xl dark:bg-gray-800"
        >
            <!-- Header -->
            <div class="flex items-center justify-between pb-3 border-b">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Pembayaran Nota
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>

            <!-- Form -->
            <form class="items-center mt-4 space-y-4">
                <div class="flex flex-col">
                    <label
                        for="image-upload"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-600"
                    >
                        Upload Gambar
                    </label>
                    <label
                        for="image-upload"
                        class="flex flex-col items-center justify-center w-full h-40 mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-300 hover:bg-gray-100 dark:hover:bg-gray-400"
                    >
                        <div
                            class="flex flex-col items-center justify-center pt-5 pb-6"
                        >
                            <p
                                class="mb-2 text-sm text-gray-500 dark:text-gray-600"
                            >
                                <span class="font-semibold"
                                    >Klik untuk Upload</span
                                >
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                WEBP, PNG (max. 1MB)
                            </p>
                        </div>
                        <input
                            id="image-upload"
                            type="file"
                            name="image"
                            class="hidden"
                        />
                    </label>
                </div>

                <!-- Kode -->
                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Nominal
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan kode"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">Nominal wajib diisi</p>
                </div>

                <!-- Nama -->
                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Keterangan
                    </label>
                    <textarea
                        type="text"
                        placeholder="Masukkan keterangan"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-2 pt-3 border-t">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600"
                    >
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
