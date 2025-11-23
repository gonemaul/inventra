<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import TextInput from "@/Components/TextInput.vue";
import { useActionLoading } from "@/Composable/useActionLoading";

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
    <Modal :show="show" @close="closeModal" maxWidth="xl">
        <form @submit.prevent="submitInvoice">
            <div class="p-6">
                <h3 class="mb-4 text-xl font-bold dark:text-white">
                    Upload Nota & Data Keuangan
                </h3>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium dark:text-gray-300"
                            >Nomor Nota Supplier</label
                        >
                        <TextInput v-model="form.invoice_number" required />
                        <InputError :message="form.errors.invoice_number" />
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium dark:text-gray-300"
                            >Total Nominal Nota (IDR)</label
                        >
                        <TextInput
                            v-model.number="form.total_amount"
                            type="number"
                            required
                            min="1"
                        />
                        <InputError :message="form.errors.total_amount" />
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium dark:text-gray-300"
                            >Tanggal Nota</label
                        >
                        <TextInput
                            v-model="form.invoice_date"
                            type="date"
                            required
                        />
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium dark:text-gray-300"
                            >Tanggal Jatuh Tempo (Bon)</label
                        >
                        <TextInput v-model="form.due_date" type="date" />
                    </div>
                </div>

                <div class="mb-4">
                    <label
                        class="block mb-1 text-sm font-medium dark:text-gray-300"
                        >Status Pembayaran</label
                    >
                    <select
                        v-model="form.payment_status"
                        class="w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option
                            v-for="status in paymentStatuses"
                            :key="status"
                            :value="status"
                        >
                            {{ status.toUpperCase() }}
                        </option>
                    </select>
                </div>

                <div class="mb-6">
                    <label
                        class="block mb-1 text-sm font-medium dark:text-gray-300"
                        >Foto Nota (Bukti Fisik)</label
                    >
                    <input
                        type="file"
                        @change="handleFileChange"
                        ref="fileInput"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-lime-50 file:text-lime-700 hover:file:bg-lime-100 dark:file:bg-lime-900 dark:file:text-lime-300"
                    />
                    <InputError :message="form.errors.invoice_image" />
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <SecondaryButton @click="closeModal" type="button"
                        >Batal</SecondaryButton
                    >
                    <PrimaryButton :disabled="form.processing" type="submit">
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Nota & Lanjut Checking"
                        }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </Modal>
</template>
