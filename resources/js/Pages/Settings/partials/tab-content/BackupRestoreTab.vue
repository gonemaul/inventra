<script setup>
import { router } from "@inertiajs/vue3";
import { ref } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";

// Menerima data dari Parent (Settings/Index.vue)
const props = defineProps({
    backups: Array, // List file backup
    autoBackupEnabled: Boolean, // Status ON/OFF jadwal otomatis (dari DB settings)
    lastRestore: Object,
    lastBackup: Object,
});

const fileInput = ref(null);
const { isActionLoading } = useActionLoading();

// ---------------------------------------------------------
// 1. LOGIC OTOMATISASI (JADWAL)
// ---------------------------------------------------------
const toggleAutoBackup = () => {
    // Kirim status kebalikan (Toggle)
    router.post(
        route("backups.update-setting"),
        {
            enabled: !props.autoBackupEnabled,
        },
        {
            preserveScroll: true,
            onStart: () => (isActionLoading.value = true),
            onFinish: () => (isActionLoading.value = false),
        }
    );
};

// ---------------------------------------------------------
// 2. LOGIC BACKUP (MANUAL)
// ---------------------------------------------------------
const createBackup = () => {
    if (confirm("Buat backup database sekarang?")) {
        isActionLoading.value = true;
        router.post(
            route("backups.store"),
            {},
            {
                onFinish: () => (isActionLoading.value = false),
                preserveScroll: true,
            }
        );
    }
};

// ---------------------------------------------------------
// 3. LOGIC RESTORE (PULIHKAN DATA)
// ---------------------------------------------------------

// A. Restore dari File yang ada di Server (List)
const restoreFromList = (fileName) => {
    const msg =
        "⚠️ PERINGATAN KERAS!\n\n" +
        "Anda akan me-restore database ke titik waktu file ini dibuat.\n" +
        "Semua data transaksi SETELAH waktu file tersebut akan HILANG dan TIDAK BISA KEMBALI.\n\n" +
        "Apakah Anda yakin ingin melanjutkan?";

    if (confirm(msg)) {
        isActionLoading.value = true;
        // Asumsi route backend: Route::post('/backups/restore/{fileName}')
        router.post(
            route("backups.restore", fileName),
            {},
            {
                onFinish: () => (isActionLoading.value = false),
                // onSuccess: () => toast.success("Database berhasil dipulihkan."),
                preserveScroll: true,
            }
        );
    }
};

// B. Restore dari File Upload (Local PC)
const triggerUpload = () => fileInput.value.click();

const handleUploadRestore = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    if (
        confirm(
            `Upload file "${file.name}" dan restore database menggunakan file ini?`
        )
    ) {
        const formData = new FormData();
        formData.append("backup_file", file);

        isActionLoading.value = true;
        // Asumsi route backend: Route::post('/backups/upload-restore')
        router.post(route("backups.upload-restore"), formData, {
            onFinish: () => {
                isActionLoading.value = false;
                fileInput.value.value = null; // Reset input
            },
            // onSuccess: () =>
            // toast.success("Database berhasil dipulihkan dari file upload."),
        });
    }
};

// ---------------------------------------------------------
// 4. LOGIC LAINNYA
// ---------------------------------------------------------
const deleteBackup = (fileName) => {
    if (confirm("Hapus file backup ini permanen?")) {
        isActionLoading.value = true;
        router.delete(route("backups.destroy", fileName), {
            preserveScroll: true,
            onFinish: () => (isActionLoading.value = false),
        });
    }
};
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <div
            class="flex items-center justify-between p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
        >
            <div class="flex items-center gap-4">
                <div
                    class="p-3 rounded-full"
                    :class="
                        autoBackupEnabled
                            ? 'bg-green-100 text-green-600'
                            : 'bg-gray-100 text-gray-400'
                    "
                >
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
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                        Backup Otomatis
                    </h3>
                    <p class="text-sm text-gray-500">
                        Status:
                        <span
                            class="font-bold"
                            :class="
                                autoBackupEnabled
                                    ? 'text-green-600'
                                    : 'text-gray-400'
                            "
                            >{{
                                autoBackupEnabled
                                    ? "AKTIF (Setiap Jam 22:00)"
                                    : "NON-AKTIF"
                            }}</span
                        >
                    </p>
                </div>
            </div>

            <button
                @click="toggleAutoBackup"
                :disabled="isActionLoading.value == true"
                class="relative inline-flex items-center h-6 transition-colors rounded-full w-11 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                :class="
                    autoBackupEnabled
                        ? 'bg-green-500'
                        : 'bg-gray-200 dark:bg-gray-700'
                "
            >
                <span class="sr-only">Toggle Backup</span>
                <span
                    class="inline-block w-4 h-4 transition-transform transform bg-white rounded-full"
                    :class="
                        autoBackupEnabled ? 'translate-x-6' : 'translate-x-1'
                    "
                />
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div
                class="flex flex-col justify-between p-6 border border-indigo-100 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl dark:border-indigo-800"
            >
                <div>
                    <h4
                        class="mb-1 text-lg font-bold text-indigo-900 dark:text-indigo-100"
                    >
                        Backup Sekarang
                    </h4>
                    <p
                        class="mb-4 text-xs text-indigo-700 dark:text-indigo-300"
                    >
                        Buat salinan database saat ini secara manual. Disarankan
                        sebelum melakukan perubahan besar.
                    </p>
                </div>
                <button
                    @click="createBackup"
                    :disabled="isActionLoading.value == true"
                    class="flex items-center justify-center w-full gap-2 py-2 text-sm font-bold text-white transition bg-indigo-600 rounded-lg shadow-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    <svg
                        v-if="isActionLoading.value == true"
                        class="w-4 h-4 animate-spin"
                        viewBox="0 0 24 24"
                    >
                        <path
                            fill="currentColor"
                            d="M12 2A10 10 0 1 0 22 12A10 10 0 0 0 12 2Zm0 18a8 8 0 1 1 8-8A8 8 0 0 1 12 20Z"
                            opacity=".3"
                        />
                        <path
                            fill="currentColor"
                            d="M20 12h2A10 10 0 0 0 12 2V4A8 8 0 0 1 20 12Z"
                        />
                    </svg>
                    <span>+ Buat Backup Baru</span>
                </button>
                <span
                    class="flex justify-between mt-1"
                    v-if="Object.keys(lastBackup).length > 0"
                >
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">
                        Terakhir dibakcup tanggal :
                        <strong>{{ lastBackup["date"] }}</strong>
                    </p>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">
                        Oleh :
                        <strong>{{ lastBackup["date"] }}</strong>
                    </p>
                </span>
                <p
                    v-else
                    class="text-xs text-center text-indigo-700 dark:text-indigo-300"
                >
                    Belum pernah dibackup
                </p>
            </div>

            <div
                class="flex flex-col justify-between p-6 border border-orange-100 bg-orange-50 dark:bg-orange-900/20 rounded-2xl dark:border-orange-800"
            >
                <div>
                    <h4
                        class="mb-1 text-lg font-bold text-orange-900 dark:text-orange-100"
                    >
                        Restore dari File
                    </h4>
                    <p
                        class="mb-4 text-xs text-orange-700 dark:text-orange-300"
                    >
                        Punya file backup (.zip) dari komputer lain? Upload
                        disini untuk memulihkan database.
                    </p>
                </div>
                <input
                    type="file"
                    ref="fileInput"
                    class="hidden"
                    accept=".zip"
                    @change="handleUploadRestore"
                />
                <button
                    @click="triggerUpload"
                    :disabled="isActionLoading.value == true"
                    class="flex items-center justify-center w-full gap-2 py-2 text-sm font-bold text-white transition bg-orange-600 rounded-lg shadow-md hover:bg-orange-700 disabled:opacity-50"
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
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                        ></path>
                    </svg>
                    <span>Upload & Restore</span>
                </button>
                <span
                    class="flex justify-between mt-1"
                    v-if="Object.keys(lastRestore).length > 0"
                >
                    <p class="text-xs text-orange-700 dark:text-orange-300">
                        Terakhir dipulihkan tanggal :
                        <Strong>{{ lastRestore["date"] }}</Strong>
                    </p>
                    <p class="text-xs text-orange-700 dark:text-orange-300">
                        Oleh :
                        <Strong>{{ lastRestore["user"] }}</Strong>
                    </p>

                    <p class="text-xs text-orange-700 dark:text-orange-300">
                        Dari :
                        <strong>{{ lastRestore["filename"] }}</strong>
                    </p>
                </span>
                <p
                    v-else
                    class="mt-3 text-xs text-center text-orange-700 dark:text-orange-300"
                >
                    Belum pernah pemulihan
                </p>
            </div>
        </div>

        <div
            class="overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl"
        >
            <div
                class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
            >
                <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                    Riwayat Backup (Restore Points)
                </h4>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead
                        class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50"
                    >
                        <tr>
                            <th class="px-6 py-3">Waktu Backup</th>
                            <th class="px-6 py-3">Nama File</th>
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
                            class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30"
                        >
                            <td
                                class="px-6 py-4 font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap"
                            >
                                {{ file.date }}
                            </td>
                            <td
                                class="px-6 py-4 font-mono text-xs text-gray-500"
                            >
                                {{ file.name }}
                            </td>
                            <td class="px-6 py-4">
                                <span
                                    class="px-2 py-1 text-xs font-bold text-gray-600 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ file.size }}
                                </span>
                            </td>
                            <td
                                class="flex justify-end gap-2 px-6 py-4 text-right"
                            >
                                <button
                                    @click="restoreFromList(file.name)"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg hover:bg-orange-100 transition text-xs font-bold"
                                    title="Pulihkan database ke titik ini"
                                >
                                    <svg
                                        class="w-3 h-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                        ></path>
                                    </svg>
                                    Restore
                                </button>

                                <a
                                    :href="route('backups.download', file.name)"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition text-xs font-bold"
                                    title="Download ke Komputer"
                                >
                                    ⬇️
                                </a>

                                <button
                                    @click="deleteBackup(file.name)"
                                    class="p-1.5 text-gray-400 hover:text-red-600 rounded transition"
                                    title="Hapus File"
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
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        ></path>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!backups || backups.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-12 text-center text-gray-400"
                            >
                                Belum ada file backup tersimpan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
