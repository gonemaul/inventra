<script setup>
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { ref, watch } from "vue";
import BottomSheet from "@/Components/BottomSheet.vue";

const { isActionLoading } = useActionLoading();
const toast = useToast();
const props = defineProps({
    show: Boolean,
    purchase: Object, // Data purchase untuk kalkulasi preview
});

const emit = defineEmits(["close"]);

const form = useForm({
    shipping_cost: 0,
    other_costs: 0,
    notes: "",
});

const isProcessing = ref(false);

// Reset form saat modal dibuka
watch(
    () => props.show,
    (val) => {
        if (val) {
            form.shipping_cost = 0;
            form.other_costs = 0;
            form.notes = "";
        }
    }
);

const submitFinalize = () => {
    isProcessing.value = true;
    isActionLoading.value = true;
    form.put(route("purchases.finalize", props.purchase.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("close");
            isProcessing.value = false;
        },
        onError: () => {
            isActionLoading.value = false;
            isProcessing.value = false;
            toast.error(
                errors.message || "Terjadi kesalahan saat menghapus data."
            );
        },
        onFinish: () => {
            isActionLoading.value = false;
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="Selesaikan & Masukkan Stok"
    >
        <div class="p-6">
            <!-- <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Selesaikan & Masukkan Stok
            </h2> -->

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Pastikan semua data valid. Aksi ini akan menambah stok dan tidak
                dapat dibatalkan. Silakan masukkan biaya tambahan jika ada (akan
                dibebankan ke HPP produk).
            </p>

            <div class="mt-6 space-y-4">
                <div>
                    <InputLabel
                        for="shipping"
                        value="Biaya Ongkos Kirim (Opsional)"
                    />
                    <TextInput
                        id="shipping"
                        v-model="form.shipping_cost"
                        type="number"
                        class="block w-full mt-1"
                        min="0"
                    />
                </div>

                <div>
                    <InputLabel
                        for="other"
                        value="Biaya Lain-lain / Admin (Opsional)"
                    />
                    <TextInput
                        id="other"
                        v-model="form.other_costs"
                        type="number"
                        class="block w-full mt-1"
                        min="0"
                    />
                </div>

                <div>
                    <InputLabel
                        for="notes"
                        value="Catatan Tambahan (Opsional)"
                    />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-lime-500 focus:ring-lime-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300"
                        placeholder="Contoh: Barang diterima lengkap, bonus 2 pcs..."
                    ></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <SecondaryButton @click="$emit('close')">
                    Batal
                </SecondaryButton>

                <PrimaryButton
                    class="bg-lime-600 hover:bg-lime-700"
                    :disabled="isProcessing || form.processing"
                    @click="submitFinalize"
                >
                    <span v-if="form.processing">Memproses...</span>
                    <span v-else>Selesaikan Transaksi</span>
                </PrimaryButton>
            </div>
        </div>
    </BottomSheet>
</template>
