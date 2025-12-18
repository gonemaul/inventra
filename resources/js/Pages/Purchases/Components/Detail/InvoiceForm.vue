<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    show: Boolean,
    purchaseId: [Number, String], // ID transaksi yang sedang divalidasi
    paymentStatuses: Array, // Daftar ['unpaid', 'partial', 'paid']
    editingInvoice: { type: Object, default: null },
});

const emit = defineEmits(["close", "invoice-saved"]);
const { isActionLoading } = useActionLoading();

// Kita gunakan useForm khusus untuk menangani file upload (multipart/form-data)
const form = useForm({
    invoice_number: null,
    invoice_date: new Date().toISOString().split("T")[0],
    due_date: null,
    total_amount: 0,
    invoice_image: null, // File
    payment_status: "unpaid", // Default
});

watch(
    () => props.editingInvoice,
    (newInvoice) => {
        if (newInvoice) {
            // Jika ada data editingInvoice, isi form dengan data tersebut
            form.invoice_number = newInvoice.invoice_number;
            form.invoice_date = newInvoice.invoice_date;
            form.due_date = newInvoice.due_date;
            form.total_amount = newInvoice.total_amount;
            form.payment_status = newInvoice.payment_status;
            // Catatan: File upload (invoice_image) tidak diisi saat edit, harus diunggah ulang jika diubah.
        } else {
            // Reset jika prop hilang (mode Create)
            form.reset();
        }
    },
    { immediate: true }
);

// Referensi input file
const fileInput = ref(null);

// Menangani pemilihan file
function handleFileChange(event) {
    // Ambil file pertama
    form.invoice_image = event.target.files[0];
}

// Fungsi Submit Nota
const submitInvoice = () => {
    // Validasi Sederhana FE
    if (!form.invoice_number || form.total_amount <= 0) {
        alert("Nomor nota dan Total Nominal wajib diisi.");
        return;
    }

    const isEditMode = !!props.editingInvoice;
    const url = isEditMode
        ? route("purchases.updateInvoice", {
              purchase: props.purchaseId,
              invoice: props.editingInvoice.id,
          })
        : route("purchases.storeInvoice", props.purchaseId);
    const method = isEditMode ? "post" : "post";
    isActionLoading.value = true;
    form.transform((data) => ({
        ...data,
        _method: isEditMode ? "put" : "post",
    })).post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit("invoice-saved");
            emit("close");
        },
        onError: (errors) => {
            toast.error(
                errors.message || "Terjadi kesalahan saat menghapus data."
            );
            console.error("Gagal upload/update invoice:", errors);
        },
        onFinish: () => {
            isActionLoading.value = false;
        },
    });
};

// Reset form saat modal ditutup
function closeModal() {
    form.reset();
    emit("close");
}
</script>

<template>
    <BottomSheet :show @close="closeModal" title="Tambah Invoice">
        <form
            @submit.prevent="submitInvoice"
            class="overflow-hidden bg-white dark:bg-gray-900 rounded-2xl"
        >
            <div
                class="px-6 py-4 bg-gray-200 border-b border-gray-100 dark:border-gray-800 dark:bg-gray-800/50"
            >
                <h3
                    class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 text-lime-500"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    Upload Nota & Keuangan
                </h3>
                <p class="mt-1 text-xs text-gray-500">
                    Pastikan data yang diinput sesuai dengan bukti fisik nota
                    dari supplier.
                </p>
            </div>

            <div class="px-3 py-4 space-y-6 border-l border-r border-gray-300">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1.5"
                            >
                                No. Nota Supplier
                                <span class="text-red-500">*</span>
                            </label>
                            <TextInput
                                v-model="form.invoice_number"
                                required
                                placeholder="Contoh: INV-001/2024"
                                class="w-full py-3"
                            />
                            <InputError :message="form.errors.invoice_number" />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1.5"
                            >
                                Total Nominal (Rp)
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute text-sm font-bold text-gray-500 -translate-y-1/2 left-3 top-1/2"
                                    >Rp</span
                                >
                                <TextInput
                                    v-model.number="form.total_amount"
                                    type="number"
                                    required
                                    min="1"
                                    placeholder="0"
                                    class="w-full py-3 pl-10 font-bold text-gray-800"
                                />
                            </div>
                            <InputError :message="form.errors.total_amount" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1.5"
                            >
                                Tanggal Nota <span class="text-red-500">*</span>
                            </label>
                            <TextInput
                                v-model="form.invoice_date"
                                type="date"
                                required
                                class="w-full py-3"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1.5"
                            >
                                Jatuh Tempo (Opsional)
                            </label>
                            <TextInput
                                v-model="form.due_date"
                                type="date"
                                class="w-full py-3"
                            />
                            <p class="text-[10px] text-gray-400 mt-1">
                                Kosongkan jika tunai.
                            </p>
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100 dark:border-gray-800" />

                <div class="space-y-4">
                    <div>
                        <label
                            class="block mb-2 text-xs font-bold tracking-wide text-gray-700 uppercase dark:text-gray-300"
                        >
                            Status Pembayaran
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <label
                                v-for="status in paymentStatuses"
                                :key="status"
                                :class="[
                                    'cursor-pointer border rounded-xl p-3 text-center transition flex items-center justify-center gap-2',
                                    form.payment_status === status
                                        ? 'bg-lime-50 border-lime-500 text-lime-700 font-bold ring-1 ring-lime-500'
                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50',
                                ]"
                            >
                                <input
                                    type="radio"
                                    v-model="form.payment_status"
                                    :value="status"
                                    class="hidden"
                                />
                                <span class="text-sm uppercase">{{
                                    status
                                }}</span>
                                <svg
                                    v-if="form.payment_status === status"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-4 h-4"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block mb-2 text-xs font-bold tracking-wide text-gray-700 uppercase dark:text-gray-300"
                        >
                            Foto Nota Fisik
                        </label>
                        <div class="relative">
                            <input
                                type="file"
                                @change="handleFileChange"
                                ref="fileInput"
                                accept="image/*"
                                class="absolute inset-0 z-10 w-full h-full opacity-0 cursor-pointer"
                            />
                            <div
                                class="p-6 text-center transition border-2 border-gray-300 border-dashed dark:border-gray-600 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 bg-gray-50 dark:bg-gray-900"
                            >
                                <div
                                    v-if="!form.invoice_image"
                                    class="space-y-2"
                                >
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto rounded-full bg-lime-100 text-lime-600"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-5 h-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                    </div>
                                    <p
                                        class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                    >
                                        Tap untuk ambil foto / upload
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        JPG, PNG (Max 2MB)
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="flex items-center justify-between p-2 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
                                >
                                    <div
                                        class="flex items-center gap-3 overflow-hidden"
                                    >
                                        <div
                                            class="flex items-center justify-center w-10 h-10 rounded bg-lime-100 text-lime-600 shrink-0"
                                        >
                                            IMG
                                        </div>
                                        <p
                                            class="text-sm font-medium truncate max-w-[150px]"
                                        >
                                            Foto Terpilih
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click.stop="form.invoice_image = null"
                                        class="z-20 p-2 text-red-500"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-5 h-5"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <InputError :message="form.errors.invoice_image" />
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col-reverse justify-end gap-3 px-6 py-4 border-b border-l border-r border-gray-300 sm:flex-row"
            >
                <SecondaryButton
                    @click="closeModal"
                    type="button"
                    class="justify-center w-full py-3 sm:w-auto"
                >
                    Batal
                </SecondaryButton>

                <PrimaryButton
                    :disabled="form.processing"
                    type="submit"
                    class="justify-center w-full py-3 shadow-lg sm:w-auto bg-lime-500 hover:bg-lime-600 border-lime-500 shadow-lime-500/20"
                >
                    <svg
                        v-if="!form.processing"
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 mr-2"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan & Lanjut Checking</span>
                </PrimaryButton>
            </div>
        </form>
    </BottomSheet>
</template>
