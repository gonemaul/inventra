<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    product: Object, // Data produk yang sedang diedit stoknya
});
const emit = defineEmits(["close", "success"]);
const { isActionLoading } = useActionLoading();
const form = useForm({
    product_id: props.product?.id,
    type: "stock",
    adjustment: "add", // Options: 'add' (Masuk), 'reduce' (Keluar), 'set' (Atur Ulang/Opname)
    qty: 0,
    note: "",
});

// Computed untuk menghitung Stok Akhir secara Realtime
const finalStock = computed(() => {
    const current = props.product?.stock || 0;
    const input = parseInt(form.qty) || 0;

    if (form.adjustment === "add") return current + input;
    if (form.adjustment === "reduce") return current - input;
    if (form.adjustment === "set") return input;
    return current;
});

// Helper untuk ganti mode
const setType = (type) => {
    form.adjustment = type;
    form.qty = 0; // Reset input saat ganti mode agar aman
};

// Helper tombol +/-
const adjustQty = (amount) => {
    const newVal = (parseInt(form.qty) || 0) + amount;
    if (newVal < 0) return;
    form.qty = newVal;
};

const submit = () => {
    isActionLoading.value = true;
    form.put(route("products.update", props.product?.slug), {
        onSuccess: () => emit("success"),
        onFinish: () => (isActionLoading.value = false),
        preserveScroll: true,
    });
};
</script>

<template>
    <form
        @submit.prevent="submit"
        class="flex flex-col h-full bg-white dark:bg-customBg-tableDark"
    >
        <div class="flex-1 space-y-6 overflow-y-auto custom-scrollbar">
            <div
                class="flex items-center gap-4 p-3 border border-gray-100 bg-gray-50 dark:bg-gray-700/50 rounded-xl dark:border-gray-700"
            >
                <div
                    class="relative flex-shrink-0 w-20 h-20 overflow-hidden bg-gray-100 border rounded-lg dark:bg-gray-700"
                >
                    <img
                        v-if="product.image_url"
                        :src="product.image_url"
                        loading="lazy"
                        decoding="async"
                        onerror="this.style.display='none'"
                        onload="this.classList.remove('opacity-0')"
                        class="absolute inset-0 z-10 object-cover w-full h-full bg-white opacity-0 dark:bg-gray-900"
                    />
                    <div
                        class="absolute inset-0 z-0 w-full h-full flex items-center justify-center text-[10px] text-gray-400"
                    >
                        NO IMG
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center mb-1">
                        <span
                            class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold"
                        >
                            {{ product.code }}
                        </span>
                    </div>

                    <div
                        class="text-[10px] font-medium text-gray-500 dark:text-gray-400 mb-1"
                    >
                        {{ product.category?.name }}
                        <span v-if="product.product_type">
                            | {{ product.product_type?.name }}</span
                        >
                        | Size {{ product.size?.name }}
                    </div>
                    <h2
                        class="text-sm font-bold leading-snug text-gray-900 dark:text-white line-clamp-2"
                    >
                        {{ product.name }}
                    </h2>
                    <div
                        class="text-lg font-black text-lime-600 dark:text-lime-400"
                    >
                        <span class="text-[11px] text-gray-500"
                            >Stock Awal :
                        </span>
                        {{ product.stock }}
                        <span class="text-[12px]">{{
                            product.unit?.name
                        }}</span>
                    </div>
                </div>
            </div>

            <div
                class="grid grid-cols-3 gap-2 p-1 bg-gray-100 rounded-lg dark:bg-gray-700"
            >
                <button
                    type="button"
                    @click="setType('add')"
                    class="py-2 text-sm font-bold transition-all duration-200 rounded-md"
                    :class="
                        form.adjustment === 'add'
                            ? 'bg-white dark:bg-gray-600 text-lime-600 shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'
                    "
                >
                    Masuk (+)
                </button>
                <button
                    type="button"
                    @click="setType('reduce')"
                    class="py-2 text-sm font-bold transition-all duration-200 rounded-md"
                    :class="
                        form.adjustment === 'reduce'
                            ? 'bg-white dark:bg-gray-600 text-red-500 shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'
                    "
                >
                    Keluar (-)
                </button>
                <button
                    type="button"
                    @click="setType('set')"
                    class="py-2 text-sm font-bold transition-all duration-200 rounded-md"
                    :class="
                        form.adjustment === 'set'
                            ? 'bg-white dark:bg-gray-600 text-blue-500 shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'
                    "
                >
                    Opname (=)
                </button>
            </div>

            <div class="space-y-2">
                <label
                    class="block text-xs font-medium text-center text-gray-500 uppercase"
                >
                    {{
                        form.adjustment === "add"
                            ? "Jumlah Penambahan"
                            : form.adjustment === "reduce"
                            ? "Jumlah Pengurangan"
                            : "Set Stok Baru"
                    }}
                </label>

                <div class="flex items-center justify-center gap-4">
                    <button
                        type="button"
                        @click="adjustQty(-1)"
                        class="flex items-center justify-center w-12 h-12 text-gray-600 transition bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-95"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 12H4"
                            />
                        </svg>
                    </button>

                    <input
                        v-model="form.qty"
                        type="number"
                        min="0"
                        class="w-32 text-4xl font-black text-center bg-transparent border-b-2 focus:ring-0 focus:border-lime-500 dark:text-white"
                        :class="[
                            form.adjustment === 'add'
                                ? 'border-lime-200 text-lime-600'
                                : '',
                            form.adjustment === 'reduce'
                                ? 'border-red-200 text-red-600'
                                : '',
                            form.adjustment === 'set'
                                ? 'border-blue-200 text-blue-600'
                                : '',
                        ]"
                    />

                    <button
                        type="button"
                        @click="adjustQty(1)"
                        class="flex items-center justify-center w-12 h-12 text-gray-600 transition bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 active:scale-95"
                    >
                        <svg
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div
                    class="flex flex-col items-center justify-center p-4 transition-colors duration-300 border rounded-xl"
                    :class="[
                        finalStock < 0
                            ? 'bg-red-50 border-red-100 dark:bg-red-900/20 dark:border-red-800'
                            : 'bg-gray-50 border-gray-100 dark:bg-gray-700/30 dark:border-gray-600',
                    ]"
                >
                    <span class="mb-1 text-xs text-gray-500 dark:text-gray-400"
                        >Estimasi Stok Akhir</span
                    >
                    <div class="flex items-baseline gap-2">
                        <span
                            class="text-3xl font-black text-gray-800 dark:text-white"
                            >{{ finalStock }}</span
                        >
                        <span class="text-sm font-medium text-gray-500">{{
                            product?.unit?.name || "Pcs"
                        }}</span>
                    </div>
                    <p
                        v-if="finalStock < 0"
                        class="text-[10px] text-red-500 font-bold mt-1"
                    >
                        ⚠️ Stok menjadi minus!
                    </p>
                </div>

                <div>
                    <label
                        class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200"
                        >Keterangan / Alasan
                        <span class="text-red-500">*</span></label
                    >
                    <textarea
                        v-model="form.note"
                        rows="3"
                        placeholder="Contoh: Barang datang dari supplier, Barang rusak kena air, dll."
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                    ></textarea>
                    <p
                        v-if="form.errors.note"
                        class="mt-1 text-xs text-red-500"
                    >
                        {{ form.errors.note }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="flex gap-3 p-4 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-customBg-tableDark"
        >
            <button
                type="button"
                @click="$emit('close')"
                class="w-1/3 px-4 py-2 text-sm font-bold text-gray-700 transition bg-gray-100 rounded-lg hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
            >
                Batal
            </button>
            <button
                type="submit"
                :disabled="form.processing"
                class="w-2/3 px-4 py-2 text-sm font-bold text-white transition transform rounded-lg shadow-lg active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed"
                :class="[
                    form.adjustment === 'add'
                        ? 'bg-lime-600 hover:bg-lime-700'
                        : '',
                    form.adjustment === 'reduce'
                        ? 'bg-red-600 hover:bg-red-700'
                        : '',
                    form.adjustment === 'set'
                        ? 'bg-blue-600 hover:bg-blue-700'
                        : '',
                ]"
            >
                <span v-if="form.processing">Menyimpan...</span>
                <span v-else>
                    {{
                        form.adjustment === "add"
                            ? "Tambah Stok"
                            : form.adjustment === "reduce"
                            ? "Kurangi Stok"
                            : "Update Stok"
                    }}
                </span>
            </button>
        </div>
    </form>
</template>
