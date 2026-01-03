<script setup>
import { useForm } from "@inertiajs/vue3";
import { method } from "lodash";
import { computed, watch } from "vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import InputRupiah from "@/Components/InputRupiah.vue";

// 1. PROPS: Terima data Invoice & Status Tampil dari Parent
const props = defineProps({
    show: Boolean,
    paymentMethods: Array,
    invoice: Object, // Butuh data invoice untuk ID dan hitung sisa hutang
});

// 2. EMITS: Untuk memberitahu parent agar menutup modal
const emit = defineEmits(["close"]);
const toast = useToast();
const { isActionLoading } = useActionLoading();

// 3. HELPERS (Lokal di komponen ini)
const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// 4. LOGIC
const remainingDebt = computed(() => {
    if (!props.invoice) return 0;
    return props.invoice.total_amount - props.invoice.amount_paid;
});

const form = useForm({
    payment_date: new Date().toISOString().split("T")[0],
    amount: 0,
    payment_method: "transfer",
    proof_image: null,
    notes: "",
});

// 5. SMART WATCHER: Reset form & Auto-fill saat modal dibuka
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            form.clearErrors();
            // Auto-fill nominal dengan sisa hutang
            form.amount = remainingDebt.value;
            form.payment_date = new Date().toISOString().split("T")[0];
        }
    }
);

const submitPayment = () => {
    // Post ke route update/store
    isActionLoading.value = true;
    form.post(route("finance.store", props.invoice.id), {
        onSuccess: () => {
            emit("close");
            isActionLoading.value = false;
        },
    });
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-md overflow-hidden bg-white shadow-2xl rounded-xl animate-fade-in-down"
        >
            <div
                class="flex items-center justify-between p-4 border-b bg-gray-50"
            >
                <h3 class="font-bold text-gray-800">Input Pembayaran</h3>
                <button
                    @click="$emit('close')"
                    class="text-xl font-bold text-gray-400 hover:text-red-500"
                >
                    &times;
                </button>
            </div>

            <form @submit.prevent="submitPayment" class="p-5 space-y-4">
                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700"
                        >Nominal Bayar</label
                    >
                    <div class="relative">
                        <span
                            class="absolute font-bold text-gray-500 left-3 top-2"
                            >Rp</span
                        >
                        <InputRupiah
                            v-model="form.amount"
                            class="w-full py-2 pl-10 pr-3 text-lg font-bold border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :max="remainingDebt"
                            placeholder="0"
                        />
                        <!-- <input
                            type="number"
                            v-model="form.amount"
                            class="w-full py-2 pl-10 pr-3 text-lg font-bold border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            :max="remainingDebt"
                            placeholder="0"
                        /> -->
                    </div>
                    <div class="mt-1 text-xs text-right text-gray-400">
                        Maks: {{ formatRupiah(remainingDebt) }}
                    </div>
                    <div
                        v-if="form.errors.amount"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.amount }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700"
                            >Tanggal</label
                        >
                        <input
                            type="date"
                            v-model="form.payment_date"
                            class="w-full p-2 text-sm border rounded-lg"
                        />
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-sm font-medium text-gray-700"
                            >Metode</label
                        >
                        <select
                            v-model="form.payment_method"
                            class="w-full p-2 text-sm border rounded-lg"
                        >
                            <option
                                v-for="cat in paymentMethods"
                                :key="cat"
                                :value="cat"
                            >
                                {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700"
                        >Bukti Foto / Struk</label
                    >
                    <input
                        type="file"
                        @input="form.proof_image = $event.target.files[0]"
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <div
                        v-if="form.errors.proof_image"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.proof_image }}
                    </div>
                </div>

                <div>
                    <label class="block mb-1 text-sm font-medium text-gray-700"
                        >Catatan (Opsional)</label
                    >
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full p-2 text-sm border rounded-lg"
                        placeholder="Contoh: Cicilan pertama"
                    ></textarea>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="flex-1 py-2 text-gray-600 rounded-lg hover:bg-gray-100"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 py-2 font-bold text-white rounded-lg bg-lime-600 hover:bg-lime-700 disabled:opacity-50"
                    >
                        {{
                            form.processing ? "Menyimpan..." : "Bayar Sekarang"
                        }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
