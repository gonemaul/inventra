<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import formCreate from "../form-create.vue";
import { ref } from "vue";

const showCreate = ref(false);

const file = ref(null);
const selectedData = ref("");
const message = ref("");
const messageColor = ref("");

// Opsi data yang bisa di-import
const importOptions = [
    { label: "Kategori", value: "kategori" },
    { label: "Produk", value: "produk" },
    { label: "User", value: "user" },
    { label: "Transaksi", value: "transaksi" },
];

const onFileChange = (e) => {
    file.value = e.target.files[0];
};

const handleImport = () => {
    if (!selectedData.value) {
        message.value = "Silakan pilih jenis data terlebih dahulu.";
        messageColor.value = "text-red-500";
        return;
    }

    if (!file.value) {
        message.value = "Silakan pilih file yang akan diimport.";
        messageColor.value = "text-red-500";
        return;
    }

    // Simulasi sukses
    setTimeout(() => {
        message.value = `File ${file.value.name} berhasil diimport ke data ${selectedData.value}!`;
        messageColor.value = "text-green-500";
        file.value = null;
        selectedData.value = "";
    }, 1000);
};

const resetForm = () => {
    file.value = null;
    selectedData.value = "";
    message.value = "";
};
</script>
<template>
    <formCreate :show="showCreate" @close="showCreate = false"></formCreate>
    <div class="p-6 bg-white shadow rounded-xl dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-gray-100">
            Backup & Import Data
        </h2>

        <!-- Backup Section -->
        <div class="mb-8">
            <h3
                class="mb-2 font-medium text-gray-700 text-md dark:text-gray-300"
            >
                Backup Data
            </h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Download data Anda dalam format yang tersedia.
            </p>
            <div class="flex gap-3">
                <button
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600 focus:ring-2 focus:ring-lime-400"
                >
                    Download CSV
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-blue-400"
                >
                    Download Excel
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400"
                >
                    Download JSON
                </button>
            </div>
        </div>

        <!-- Import Section -->
        <div>
            <h3
                class="mb-2 font-medium text-gray-700 text-md dark:text-gray-300"
            >
                Import Data
            </h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Pilih jenis data yang ingin diimport, lalu unggah file
                CSV/Excel.
            </p>

            <form @submit.prevent="handleImport" class="space-y-4">
                <!-- Pilih Jenis Data -->
                <select
                    v-model="selectedData"
                    class="block w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600"
                >
                    <option disabled value="">-- Pilih Data --</option>
                    <option
                        v-for="opt in importOptions"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </select>

                <!-- File Upload -->
                <input
                    type="file"
                    @change="onFileChange"
                    accept=".csv, .xlsx, .xls"
                    class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg cursor-pointer focus:outline-none dark:text-gray-300 dark:border-gray-600 dark:bg-gray-700"
                />

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-blue-400"
                    >
                        Download Template
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600 focus:ring-2 focus:ring-lime-400"
                    >
                        Import
                    </button>
                    <button
                        type="button"
                        @click="resetForm"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-100 dark:hover:bg-gray-500"
                    >
                        Reset
                    </button>
                </div>
            </form>

            <!-- Status / Feedback -->
            <div v-if="message" class="mt-4 text-sm" :class="messageColor">
                {{ message }}
            </div>
        </div>
    </div>
</template>
