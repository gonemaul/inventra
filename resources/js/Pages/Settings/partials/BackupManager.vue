<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    backups: Array,
});

const processing = ref(false);

// Logic Export Produk
const exportData = () => {
    window.location.href = route("export.download", { type: "products" });
};

// Logic Backup
const createBackup = () => {
    if (confirm("Buat backup database sekarang?")) {
        processing.value = true;
        router.post(
            route("backups.store"),
            {},
            {
                onFinish: () => (processing.value = false),
            }
        );
    }
};

const uploadDrive = (fileName) => {
    if (
        confirm(
            "Upload file ini ke Google Drive? Pastikan API Key sudah diset."
        )
    ) {
        processing.value = true;
        router.post(
            route("backups.upload-drive", fileName),
            {},
            {
                onFinish: () => (processing.value = false),
            }
        );
    }
};

const deleteBackup = (fileName) => {
    if (confirm("Hapus file ini permanen?")) {
        router.delete(route("backups.destroy", fileName));
    }
};
</script>

<template>
    <Head title="Data & Backup" />

    <AuthenticatedLayout headerTitle="Manajemen Data">
        <div class="max-w-6xl mx-auto space-y-8">
            <div
                class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
            >
                <h3
                    class="mb-4 text-lg font-bold text-gray-800 dark:text-white"
                >
                    📤 Export Data Master
                </h3>
                <div class="flex items-center gap-4">
                    <div
                        class="flex-1 p-4 border border-green-100 bg-green-50 rounded-xl"
                    >
                        <h4 class="font-bold text-green-800">Data Produk</h4>
                        <p class="mb-3 text-xs text-green-600">
                            Download semua data produk ke Excel.
                        </p>
                        <button
                            @click="exportData"
                            class="px-4 py-2 text-sm font-bold text-white transition bg-green-600 rounded-lg shadow-sm hover:bg-green-700"
                        >
                            Download Excel
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
            >
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3
                            class="text-lg font-bold text-gray-800 dark:text-white"
                        >
                            💾 Database Backup
                        </h3>
                        <p class="text-xs text-gray-500">
                            Amankan data transaksi Anda secara berkala.
                        </p>
                    </div>
                    <button
                        @click="createBackup"
                        :disabled="processing"
                        class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg shadow-lg hover:bg-indigo-700 disabled:opacity-50 shadow-indigo-200"
                    >
                        {{ processing ? "Memproses..." : "+ Backup Database" }}
                    </button>
                </div>

                <div
                    class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-xl"
                >
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-3">Nama File</th>
                                <th class="px-6 py-3">Waktu</th>
                                <th class="px-6 py-3">Ukuran</th>
                                <th class="px-6 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="file in backups"
                                :key="file.name"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td
                                    class="px-6 py-4 font-mono text-gray-700 dark:text-gray-300"
                                >
                                    {{ file.name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ file.date }}
                                </td>
                                <td class="px-6 py-4 text-gray-500">
                                    {{ file.size }}
                                </td>
                                <td
                                    class="flex justify-end gap-2 px-6 py-4 text-right"
                                >
                                    <a
                                        :href="
                                            route('backups.download', file.name)
                                        "
                                        class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded border border-gray-300 text-xs font-bold"
                                        title="Download ke Laptop"
                                    >
                                        ⬇️ PC
                                    </a>

                                    <button
                                        @click="uploadDrive(file.name)"
                                        class="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 text-xs font-bold"
                                        title="Upload ke Google Drive"
                                    >
                                        ☁️ Drive
                                    </button>

                                    <button
                                        @click="deleteBackup(file.name)"
                                        class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 rounded border border-red-200 text-xs font-bold"
                                        title="Hapus Backup"
                                    >
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="backups.length === 0">
                                <td
                                    colspan="4"
                                    class="px-6 py-12 text-center text-gray-400"
                                >
                                    Belum ada history backup.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
