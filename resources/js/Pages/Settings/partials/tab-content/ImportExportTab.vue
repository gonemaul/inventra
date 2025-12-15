<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

// --- LOGIC EXPORT ---
const exportTargets = [
    {
        label: "Data Produk",
        value: "products",
        icon: "📦",
        desc: "Stok, Harga, Supplier",
    },
    {
        label: "Data Penjualan",
        value: "sales",
        icon: "📈",
        desc: "Transaksi, Profit (Coming Soon)",
        disabled: true,
    },
];

const triggerExport = (type) => {
    if (type === "sales") return; // Belum ada
    window.location.href = route("data.export.download", { type });
};

// --- LOGIC IMPORT ---
const importTargets = [
    { label: "Data Produk", value: "products" },
    { label: "Data Supplier", value: "suppliers" },
];

const page = usePage();
const importErrors = computed(() => page.props.errors.import_errors || []);
const selectedImport = ref("");
const fileInput = ref(null);
const importForm = useForm({ type: "", file: null });

const downloadTemplate = () => {
    if (!selectedImport.value) return alert("Pilih jenis data dulu!");
    window.location.href = route("data.import.template", {
        type: selectedImport.value,
    });
};

const submitImport = () => {
    if (!selectedImport.value || !fileInput.value.files[0]) return;
    importForm.type = selectedImport.value;
    importForm.file = fileInput.value.files[0];

    importForm.post(route("data.import.store"), {
        onSuccess: () => {
            importForm.reset();
            fileInput.value.value = null;
        },
    });
};
</script>

<template>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 animate-fade-in">
        <div class="space-y-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 rounded-lg bg-lime-100 text-lime-600">
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
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                        ></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    Import Data
                </h3>
            </div>

            <div
                class="p-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700"
            >
                <div class="mb-4">
                    <label
                        class="text-sm font-bold text-gray-700 dark:text-gray-300"
                        >1. Pilih Jenis Data</label
                    >
                    <select
                        v-model="selectedImport"
                        class="w-full mt-1 border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white"
                    >
                        <option value="" disabled>-- Pilih --</option>
                        <option
                            v-for="t in importTargets"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.label }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label
                        class="text-sm font-bold text-gray-700 dark:text-gray-300"
                        >2. Siapkan File Excel</label
                    >
                    <button
                        @click="downloadTemplate"
                        :disabled="!selectedImport"
                        class="w-full py-2 mt-1 text-sm font-bold text-gray-700 transition bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 disabled:opacity-50"
                    >
                        Download Template
                    </button>
                </div>

                <div class="mb-4">
                    <label
                        class="text-sm font-bold text-gray-700 dark:text-gray-300"
                        >3. Upload & Proses</label
                    >
                    <input
                        type="file"
                        ref="fileInput"
                        accept=".xlsx"
                        class="block w-full mt-1 text-sm text-gray-500 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-bold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100"
                    />
                </div>

                <button
                    @click="submitImport"
                    :disabled="importForm.processing || !selectedImport"
                    class="w-full py-3 mt-2 font-bold text-white transition shadow-lg bg-lime-600 hover:bg-lime-700 rounded-xl shadow-lime-200 dark:shadow-none disabled:opacity-50"
                >
                    {{
                        importForm.processing
                            ? "Memproses Data..."
                            : "Mulai Import"
                    }}
                </button>

                <div
                    v-if="importErrors.length > 0"
                    class="p-4 mt-4 border border-red-200 rounded-lg bg-red-50 dark:bg-red-900/20 dark:border-red-800"
                >
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg
                                class="w-5 h-5 text-red-400 dark:text-red-300"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                                aria-hidden="true"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>

                        <div class="w-full ml-3">
                            <h3
                                class="text-sm font-semibold text-red-800 dark:text-red-200"
                            >
                                Gagal Import ({{ importErrors.length }} baris)
                            </h3>
                            <div
                                class="mt-2 overflow-y-auto text-sm text-red-700 max-h-40 dark:text-red-300"
                            >
                                <ul class="pl-5 space-y-1 list-disc">
                                    <li
                                        v-for="(err, i) in importErrors"
                                        :key="i"
                                    >
                                        {{ err }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="p-2 text-blue-600 bg-blue-100 rounded-lg">
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
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                        ></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">
                    Export Data
                </h3>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div
                    v-for="target in exportTargets"
                    :key="target.value"
                    @click="triggerExport(target.value)"
                    class="flex items-center gap-4 p-4 transition bg-white border border-gray-200 shadow-sm cursor-pointer group dark:bg-gray-800 dark:border-gray-700 rounded-2xl hover:shadow-md hover:border-blue-300"
                    :class="{
                        'opacity-60 cursor-not-allowed': target.disabled,
                    }"
                >
                    <div
                        class="flex items-center justify-center w-12 h-12 text-2xl transition rounded-xl group-hover:scale-110 bg-gray-50 dark:bg-gray-700"
                    >
                        {{ target.icon }}
                    </div>

                    <div class="flex-1">
                        <h4 class="font-bold text-gray-800 dark:text-white">
                            {{ target.label }}
                        </h4>
                        <p class="text-xs text-gray-500">{{ target.desc }}</p>
                    </div>

                    <div class="text-blue-600">
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            ></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
