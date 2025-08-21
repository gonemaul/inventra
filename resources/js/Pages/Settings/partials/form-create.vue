<script setup>
import Modal from "@/Components/Modal.vue";
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: "Kategori",
    },
    isSupplier: {
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
                    Tambah {{ title }}
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>

            <!-- Form -->
            <form class="mt-4 space-y-4">
                <!-- Kode -->
                <div v-if="!isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Kode
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan kode"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">Kode wajib diisi</p>
                </div>

                <!-- Nama -->
                <div v-if="!isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Nama
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan nama"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">Nama wajib diisi</p>
                </div>

                <!-- Nama Supplier -->
                <div v-if="isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Nama Supplier
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan nama"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">
                        Nama Supplier wajib diisi
                    </p>
                </div>

                <!-- Phone -->
                <div v-if="isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        No Hp
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan No Hp"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">No Hp wajib diisi</p>
                </div>

                <!-- Alamat -->
                <div v-if="isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Alamat
                    </label>
                    <input
                        type="text"
                        placeholder="Masukkan alamat"
                        class="w-full px-3 py-2 text-sm border rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                    <p class="mt-1 text-xs text-red-500">Alamat wajib diisi</p>
                </div>

                <!-- Type -->
                <div v-if="isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Type
                    </label>
                    <select
                        class="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Pilih Type</option>
                        <option value="tersedia">Online</option>
                        <option value="habis">Offline</option>
                    </select>
                    <p class="mt-1 text-xs text-red-500">Type wajib diisi</p>
                </div>

                <!-- Status -->
                <div v-if="isSupplier">
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Status
                    </label>
                    <select
                        class="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Pilih Status</option>
                        <option value="tersedia">Active</option>
                        <option value="habis">Inactive</option>
                    </select>
                    <p class="mt-1 text-xs text-red-500">Status wajib diisi</p>
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
