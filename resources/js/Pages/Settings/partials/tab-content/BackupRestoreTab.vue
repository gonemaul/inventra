<script setup>
import { router } from "@inertiajs/vue3";
import { ref, reactive } from "vue";
import { useToast } from "vue-toastification";
import PremiumLoadingModal from "@/Components/PremiumLoadingModal.vue";
import PremiumConfirmModal from "@/Components/PremiumConfirmModal.vue";

// Menerima data dari Parent (Settings/Index.vue)
const props = defineProps({
    backups: Array, // List file backup
    autoBackupEnabled: Boolean, // Status ON/OFF jadwal otomatis (dari DB settings)
    lastRestore: Object,
    lastBackup: Object,
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const toast = useToast();
const fileInput = ref(null);
const isActionLoading = ref(false);

// State Modal Loading Custom
const modalState = reactive({
    message: "Memproses...",
    subMessage: "Mohon tunggu sebentar...",
});

const setModal = (msg, sub) => {
    modalState.message = msg;
    modalState.subMessage = sub;
};

// State Modal Konfirmasi Custom
const confirmState = reactive({
    show: false,
    title: "",
    message: "",
    type: "info",
    confirmText: "Ya, Lanjutkan",
    onConfirm: null,
});

const openConfirm = ({ title, message, type, confirmText, action }) => {
    confirmState.title = title;
    confirmState.message = message;
    confirmState.type = type || "info";
    confirmState.confirmText = confirmText || "Ya, Lanjutkan";
    confirmState.onConfirm = action;
    confirmState.show = true;
};

const handleConfirmAction = () => {
    confirmState.show = false;
    if (confirmState.onConfirm) {
        confirmState.onConfirm();
    }
};

// Form State untuk Pengaturan Jadwal
const form = reactive({
    enabled: props.autoBackupEnabled,
    backup_daily_time: props.settings.backup_daily_time || "22:00",
    backup_frequency: props.settings.backup_frequency || "3", // String biar aman di select
    report_morning_time: props.settings.report_morning_time || "06:30",
    report_financial_time: props.settings.report_financial_time || "12:30",
    report_closing_time: props.settings.report_closing_time || "21:00",
    insight_generate_time: props.settings.insight_generate_time || "21:30",
});

// ---------------------------------------------------------
// 1. LOGIC OTOMATISASI (JADWAL)
// ---------------------------------------------------------
const saveSettings = () => {
    setModal("Menyimpan Pengaturan...", "Sedang memperbarui jadwal backup otomatis.");
    router.post(
        route("backups.update-setting"),
        {
            enabled: form.enabled,
            backup_daily_time: form.backup_daily_time,
            backup_frequency: form.backup_frequency,
            report_morning_time: form.report_morning_time,
            report_financial_time: form.report_financial_time,
            report_closing_time: form.report_closing_time,
            insight_generate_time: form.insight_generate_time,
        },
        {
            preserveScroll: true,
            onStart: () => (isActionLoading.value = true),
            onFinish: () => (isActionLoading.value = false),
            onSuccess: () => toast.success("Pengaturan jadwal berhasil disimpan!"),
            onError: () => toast.error("Gagal menyimpan pengaturan."),
        }
    );
};

// ---------------------------------------------------------
// 2. LOGIC BACKUP (MANUAL)
// ---------------------------------------------------------
const createBackup = () => {
    openConfirm({
        title: "Buat Backup Database?",
        message: "Sistem akan membuat salinan database saat ini. Proses ini aman dilakukan kapan saja.",
        type: "info",
        confirmText: "Buat Backup",
        action: () => {
             setModal("Membuat Backup...", "Proses ini mungkin memakan waktu beberapa detik.");
            isActionLoading.value = true;
            router.post(
                route("backups.store"),
                {},
                {
                    onFinish: () => (isActionLoading.value = false),
                    preserveScroll: true,
                    onSuccess: () => toast.success("Backup berhasil dibuat!"),
                }
            );
        }
    });
};

// ---------------------------------------------------------
// 3. LOGIC RESTORE (PULIHKAN DATA)
// ---------------------------------------------------------

// A. Restore dari File yang ada di Server (List)
const restoreFromList = (fileName) => {
    openConfirm({
        title: "Peringatan Restore Database!",
        message: "Anda akan mengembalikan database ke titik waktu file ini dibuat. Semua data transaksi SETELAH waktu file tersebut akan HILANG PERMANEN. Apakah Anda yakin?",
        type: "danger",
        confirmText: "Ya, Restore Sekarang",
        action: () => {
            setModal("Memulihkan Database...", "Sistem sedang mengembalikan data dari file backup. JANGAN refresh halaman.");
            isActionLoading.value = true;
            router.post(
                route("backups.restore", fileName),
                {},
                {
                    onFinish: () => (isActionLoading.value = false),
                    preserveScroll: true,
                }
            );
        }
    });
};

// B. Restore dari File Upload (Local PC)
const triggerUpload = () => fileInput.value.click();

const handleUploadRestore = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    openConfirm({
        title: "Upload & Restore?",
        message: `Anda akan me-restore database menggunakan file "${file.name}". Data saat ini akan ditimpa. Lanjutkan?`,
        type: "warning",
        confirmText: "Upload & Restore",
        action: () => {
            const formData = new FormData();
            formData.append("backup_file", file);

            setModal("Upload & Restore...", "Sedang mengupload dan merestore database Anda.");
            isActionLoading.value = true;
            router.post(route("backups.upload-restore"), formData, {
                onFinish: () => {
                    isActionLoading.value = false;
                    fileInput.value.value = null; // Reset input
                },
            });
        }
    }); 
};

// ---------------------------------------------------------
// 4. LOGIC LAINNYA
// ---------------------------------------------------------
const deleteBackup = (fileName) => {
    openConfirm({
        title: "Hapus File Backup?",
        message: "File backup ini akan dihapus permanen dari server dan tidak bisa dikembalikan.",
        type: "danger",
        confirmText: "Hapus",
        action: () => {
            setModal("Menghapus Backup...", "Sedang menghapus file dari server.");
            isActionLoading.value = true;
            router.delete(route("backups.destroy", fileName), {
                preserveScroll: true,
                onFinish: () => (isActionLoading.value = false),
                onSuccess: () => toast.success("File backup dihapus."),
            });
        }
    });
};
</script>

<template>
    <!-- MODAL CUSTOM CONFIRM & LOADING -->
    <PremiumLoadingModal 
        :show="isActionLoading" 
        :message="modalState.message" 
        :subMessage="modalState.subMessage"
    />

    <PremiumConfirmModal 
        :show="confirmState.show"
        :title="confirmState.title"
        :message="confirmState.message"
        :type="confirmState.type"
        :confirmText="confirmState.confirmText"
        @confirm="handleConfirmAction"
        @close="confirmState.show = false"
    />

    <div class="space-y-8 animate-fade-in">
        <!-- PENGATURAN JADWAL -->
        <div class="flex flex-col p-6 space-y-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="p-3 rounded-full" :class="form.enabled ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Jadwal Otomatis</h3>
                        <p class="text-sm text-gray-500">Atur kapan backup dan laporan digenerate secara otomatis.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="form.enabled = !form.enabled"
                        class="relative inline-flex items-center h-6 transition-colors rounded-full w-11 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        :class="form.enabled ? 'bg-green-500' : 'bg-gray-200 dark:bg-gray-700'"
                    >
                        <span class="sr-only">Toggle Backup</span>
                        <span class="inline-block w-4 h-4 transition-transform transform bg-white rounded-full" :class="form.enabled ? 'translate-x-6' : 'translate-x-1'"/>
                    </button>
                    <!-- Tombol Simpan -->
                    <!-- Tampilkan hanya jika ada perubahan atau kita ingin explicit save -->
                    <button
                        v-if="form.enabled"
                        @click="saveSettings"
                        :disabled="isActionLoading"
                        class="px-4 py-2 text-sm font-bold text-white transition bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Simpan Pengaturan
                    </button>
                </div>
            </div>

            <!-- Form Detail (Hanya muncul jika Enabled) -->
            <div v-if="form.enabled" class="grid grid-cols-1 gap-6 pt-6 border-t border-gray-100 md:grid-cols-2 lg:grid-cols-3 dark:border-gray-700">
                <!-- 1. Backup Harian -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Backup Utama (Harian)</label>
                    <input v-model="form.backup_daily_time" type="time" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-1 text-xs text-gray-400">Backup lengkap (DB + File) + Upload Cloud.</p>
                </div>

                <!-- 2. Frekuensi Backup Ringan -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Frekuensi Backup Ringan</label>
                    <select v-model="form.backup_frequency" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white">
                        <option value="1">Setiap 1 Jam</option>
                        <option value="2">Setiap 2 Jam</option>
                        <option value="3">Setiap 3 Jam</option>
                        <option value="4">Setiap 4 Jam</option>
                        <option value="6">Setiap 6 Jam</option>
                        <option value="12">Setiap 12 Jam</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Backup Cepat (Hanya DB, Lokal).</p>
                </div>

                <!-- 3. Laporan Pagi -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Generating Laporan Pagi</label>
                    <input v-model="form.report_morning_time" type="time" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-1 text-xs text-gray-400">Rencana Restock & Insight Harian.</p>
                </div>

                <!-- 4. Laporan Finansial -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Laporan Finansial (Siang)</label>
                    <input v-model="form.report_financial_time" type="time" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-1 text-xs text-gray-400">Cek Omzet & Cash Flow siang hari.</p>
                </div>

                <!-- 5. Generate Insight -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Generate Insight (Analisa)</label>
                    <input v-model="form.insight_generate_time" type="time" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-1 text-xs text-gray-400">Analisa berat (Dead Stock, Tren) untuk besok.</p>
                </div>

                <!-- 6. Tutup Toko (Closing) -->
                <div>
                    <label class="block mb-1 text-xs font-bold text-gray-500 uppercase">Laporan Closing (Malam)</label>
                    <input v-model="form.report_closing_time" type="time" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white" />
                    <p class="mt-1 text-xs text-gray-400">Rekap Akhir Hari & Laporan Laba.</p>
                </div>
            </div>
            <!-- BUTTON SIMPAN JIKA ENABLED=FALSE -->
            <div v-else class="flex justify-end">
                 <button
                        @click="saveSettings"
                        :disabled="isActionLoading"
                        class="px-4 py-2 text-sm font-bold text-white transition bg-indigo-600 rounded-lg shadow-sm hover:bg-indigo-700 disabled:opacity-50"
                    >
                        Simpan Perubahan
                    </button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="flex flex-col justify-between p-6 border border-indigo-100 bg-indigo-50 dark:bg-indigo-900/20 rounded-2xl dark:border-indigo-800">
                <div>
                    <h4 class="mb-1 text-lg font-bold text-indigo-900 dark:text-indigo-100">Backup Sekarang</h4>
                    <p class="mb-4 text-xs text-indigo-700 dark:text-indigo-300">Buat salinan database saat ini secara manual. Disarankan sebelum melakukan perubahan besar.</p>
                </div>
                <button
                    @click="createBackup"
                    :disabled="isActionLoading"
                    class="flex items-center justify-center w-full gap-2 py-2 text-sm font-bold text-white transition bg-indigo-600 rounded-lg shadow-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    <span>+ Buat Backup Baru</span>
                </button>
                <span class="flex justify-between mt-1" v-if="Object.keys(lastBackup).length > 0">
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">Terakhir dibakcup tanggal : <strong>{{ lastBackup["date"] }}</strong></p>
                    <p class="text-xs text-indigo-700 dark:text-indigo-300">Oleh : <strong>{{ lastBackup["user"] }}</strong></p>
                </span>
                <p v-else class="text-xs text-center text-indigo-700 dark:text-indigo-300">Belum pernah dibackup</p>
            </div>

            <div class="flex flex-col justify-between p-6 border border-orange-100 bg-orange-50 dark:bg-orange-900/20 rounded-2xl dark:border-orange-800">
                <div>
                    <h4 class="mb-1 text-lg font-bold text-orange-900 dark:text-orange-100">Restore dari File</h4>
                    <p class="mb-4 text-xs text-orange-700 dark:text-orange-300">Punya file backup (.zip) dari komputer lain? Upload disini untuk memulihkan database.</p>
                </div>
                <input type="file" ref="fileInput" class="hidden" accept=".zip" @change="handleUploadRestore" />
                <button
                    @click="triggerUpload"
                    :disabled="isActionLoading"
                    class="flex items-center justify-center w-full gap-2 py-2 text-sm font-bold text-white transition bg-orange-600 rounded-lg shadow-md hover:bg-orange-700 disabled:opacity-50"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Upload & Restore</span>
                </button>
                <span class="flex justify-between mt-1" v-if="Object.keys(lastRestore).length > 0">
                    <p class="text-xs text-orange-700 dark:text-orange-300">Terakhir dipulihkan tanggal : <Strong>{{ lastRestore["date"] }}</Strong></p>
                    <p class="text-xs text-orange-700 dark:text-orange-300">Oleh : <Strong>{{ lastRestore["user"] }}</Strong></p>
                    <p class="text-xs text-orange-700 dark:text-orange-300">Dari : <strong>{{ lastRestore["filename"] }}</strong></p>
                </span>
                <p v-else class="mt-3 text-xs text-center text-orange-700 dark:text-orange-300">Belum pernah pemulihan</p>
            </div>
        </div>

        <div class="overflow-hidden bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-2xl">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                <h4 class="text-sm font-bold text-gray-800 dark:text-white">Riwayat Backup (Restore Points)</h4>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">Waktu Backup</th>
                            <th class="px-6 py-3">Nama File</th>
                            <th class="px-6 py-3">Ukuran</th>
                            <th class="px-6 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="file in backups" :key="file.name" class="transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ file.date }}</td>
                            <td class="px-6 py-4 font-mono text-xs text-gray-500 dark:text-gray-300">{{ file.name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-bold text-gray-600 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300">{{ file.size }}</span>
                            </td>
                            <td class="flex justify-end gap-2 px-6 py-4 text-right">
                                <button
                                    @click="restoreFromList(file.name)"
                                    class="flex items-center gap-1 px-3 py-1.5 bg-orange-50 border border-orange-200 text-orange-700 rounded-lg hover:bg-orange-100 transition text-xs font-bold"
                                    title="Pulihkan database ke titik ini"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                    Restore
                                </button>
                                <a :href="route('backups.download', file.name)" class="flex items-center gap-1 px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition text-xs font-bold" title="Download ke Komputer">⬇️</a>
                                <button @click="deleteBackup(file.name)" class="p-1.5 text-gray-400 hover:text-red-600 rounded transition" title="Hapus File"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg></button>
                            </td>
                        </tr>
                        <tr v-if="!backups || backups.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">Belum ada file backup tersimpan.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
