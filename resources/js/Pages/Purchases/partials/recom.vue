<script setup>
import Modal from "@/Components/Modal.vue";
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    items: {
        type: Object,
        default: () => ({}),
    },
});
const emit = defineEmits(["close", "save"]);
</script>
<template>
    <Modal :show="show" @close="$emit('close')">
        <div
            class="w-full p-6 bg-white shadow-lg maxs-w-md rounded-xl dark:bg-gray-800"
        >
            <!-- Header -->
            <div
                class="relative flex items-center justify-center pb-3 border-b"
            >
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Rekomendasi Pembelian
                </h2>
                <button
                    @click="$emit('close')"
                    class="absolute right-0 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>

            <!-- Tabel Produk -->
            <div class="overflow-x-auto">
                <table
                    class="w-full text-sm border border-gray-300 rounded-lg dark:border-gray-600"
                >
                    <thead class="bg-gray-100 dark:bg-gray-700 dark:text-white">
                        <tr>
                            <th class="px-3 py-2 text-left">
                                <input type="checkbox" @change="toggleAll" />
                            </th>
                            <th class="px-3 py-2 text-left">Produk</th>
                            <th class="px-3 py-2 text-center">Satuan</th>
                            <th class="px-3 py-2 text-center">Stok</th>
                            <th class="px-3 py-2 text-center">Rekom</th>
                            <th class="px-3 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in items"
                            :key="index"
                            :class="
                                item.inRAB
                                    ? 'bg-yellow-100 dark:bg-yellow-800'
                                    : ''
                            "
                            class="border-t border-gray-300 dark:border-gray-600 dark:text-white"
                        >
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    v-model="selected"
                                    :value="item.id"
                                    :disabled="item.inRAB"
                                />
                            </td>
                            <td class="px-3 py-2">{{ item.nama }}</td>
                            <td class="px-3 py-2 text-center">
                                {{ item.satuan }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{ item.stok }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{ item.rekom }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span
                                    v-if="item.inRAB"
                                    class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-200 rounded dark:bg-yellow-600 dark:text-white"
                                >
                                    Sudah di RAB
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-200 rounded dark:bg-green-600 dark:text-white"
                                >
                                    Baru
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex justify-end gap-2 mt-4">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500"
                >
                    Batal
                </button>
                <button
                    @click="$emit('tambah', selected)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                >
                    Tambah
                </button>
            </div>
        </div>
    </Modal>
</template>
