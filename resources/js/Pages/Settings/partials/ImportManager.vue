<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const importTargets = [
    { label: "Kategori Produk", value: "categories" },
    // { label: "Satuan (Unit)", value: "units" },
    { label: "Data Produk", value: "products" },
    // { label: "Supplier", value: "suppliers" },
    // { label: "Customer", value: "customers" },
];

const selectedTarget = ref("");
const fileInput = ref(null);

const form = useForm({
    type: "",
    file: null,
});

// 1. Download Template
const downloadTemplate = () => {
    if (!selectedTarget.value) return alert("Pilih jenis data dulu!");

    // Redirect ke URL download
    window.location.href = route("import.template", {
        type: selectedTarget.value,
    });
};

// 2. Upload & Process
const submitImport = () => {
    if (!selectedTarget.value || !fileInput.value.files[0]) return;

    form.type = selectedTarget.value;
    form.file = fileInput.value.files[0];

    form.post(route("import.store"), {
        onSuccess: () => {
            form.reset();
            fileInput.value.value = null; // Reset input file
        },
        onError: () => {
            // Error akan muncul di props.errors
        },
    });
};
</script>
<template>
    <div
        class="p-6 bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700"
    >
        <h3 class="mb-4 text-lg font-bold text-gray-800 dark:text-white">
            Import Data Masal
        </h3>

        <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <label
                        class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300"
                        >1. Pilih Data Target</label
                    >
                    <select
                        v-model="selectedTarget"
                        class="w-full border-gray-300 rounded-lg dark:bg-gray-700"
                    >
                        <option value="" disabled>-- Pilih Data --</option>
                        <option
                            v-for="t in importTargets"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.label }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300"
                        >2. Unduh Template</label
                    >
                    <button
                        @click="downloadTemplate"
                        :disabled="!selectedTarget"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-700 transition bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 disabled:opacity-50"
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
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            ></path>
                        </svg>
                        Download Template Excel
                    </button>
                    <p class="mt-1 text-xs text-gray-500">
                        Isi data sesuai kolom di template. Jangan ubah judul
                        kolom.
                    </p>
                </div>
            </div>

            <div
                class="pl-8 space-y-4 border-l border-gray-200 dark:border-gray-700"
            >
                <div>
                    <label
                        class="block mb-2 text-sm font-bold text-gray-700 dark:text-gray-300"
                        >3. Upload File (.xlsx)</label
                    >
                    <input
                        type="file"
                        ref="fileInput"
                        accept=".xlsx, .xls, .csv"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100"
                    />
                </div>

                <div
                    v-if="form.progress"
                    class="w-full bg-gray-200 rounded-full h-2.5"
                >
                    <div
                        class="bg-lime-600 h-2.5 rounded-full"
                        :style="{ width: form.progress.percentage + '%' }"
                    ></div>
                </div>

                <button
                    @click="submitImport"
                    :disabled="form.processing || !selectedTarget"
                    class="w-full py-2 font-bold text-white transition rounded-lg shadow bg-lime-600 hover:bg-lime-700 disabled:opacity-50"
                >
                    {{
                        form.processing ? "Sedang Memproses..." : "Mulai Import"
                    }}
                </button>
            </div>
        </div>

        <div
            v-if="$page.props.errors.import_errors"
            class="p-4 mt-6 border border-red-200 rounded-lg bg-red-50"
        >
            <h4 class="mb-2 text-sm font-bold text-red-700">
                ⚠️ Gagal Import (Semua data dibatalkan):
            </h4>
            <ul
                class="overflow-y-auto text-xs text-red-600 list-disc list-inside max-h-32"
            >
                <li
                    v-for="(err, idx) in $page.props.errors.import_errors"
                    :key="idx"
                >
                    {{ err }}
                </li>
            </ul>
        </div>
    </div>
</template>
