<script setup>
import { ref, watch, nextTick } from "vue";
import { Link } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    formHeader: Object,
    dropdowns: Object,
    stagingItem: Object,
    isEdit: Boolean,
    isEditingMode: Boolean,
    cartItems: Array,
    displayedPrice: [String, Number],
    totalUnit: [Number, String],
    totalMacam: [Number, String],
    totalBelanja: [Number, String],
});

defineEmits([
    "update:displayedPrice",
    "saveStaging",
    "resetStaging",
    "submitTransaction",
    "openRecommendation",
]);

const showDetails = ref(false);
const qtyInputMobile = ref(null);
watch(
    () => props.stagingItem.product_id,
    (newVal) => {
        if (newVal) {
            nextTick(() => {
                // Gunakan ?. (optional chaining) jaga-jaga kalau elemen belum ter-render
                qtyInputMobile.value?.select();
            });
        }
    }
);
const formatRupiah = (number) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
};
function parseRupiah(value) {
    if (!value && value !== 0) return "";
    return new Intl.NumberFormat("id-ID").format(value);
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            class="p-4 transition-colors bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <div
                class="flex items-center justify-between pb-2 mb-4 border-b border-gray-100 dark:border-gray-700"
            >
                <div>
                    <span
                        class="text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                        >Total Belanja</span
                    >
                    <p
                        class="text-xl font-extrabold text-lime-600 dark:text-lime-400"
                    >
                        {{ formatRupiah(totalBelanja) }}
                    </p>
                </div>
                <div
                    class="text-xs font-medium text-right text-gray-500 dark:text-gray-400"
                >
                    <p>{{ totalUnit }} Unit</p>
                    <p>{{ totalMacam }} Macam</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label
                        class="block mb-1 text-xs font-bold text-gray-500 dark:text-gray-400"
                        >Supplier</label
                    >
                    <select
                        :disabled="isEdit"
                        v-model="formHeader.supplier_id"
                        class="w-full px-3 py-2 text-sm transition-colors bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400"
                    >
                        <option value="">Pilih Supplier</option>
                        <option
                            v-for="supplier in dropdowns.suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.name }}
                        </option>
                    </select>
                    <InputError :message="formHeader.errors.supplier_id" />
                </div>

                <div>
                    <label
                        class="block mb-1 text-xs font-bold text-gray-500 dark:text-gray-400"
                        >Tanggal</label
                    >
                    <input
                        v-model="formHeader.transaction_date"
                        type="date"
                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:[color-scheme:dark] transition-colors"
                    />
                    <InputError :message="formHeader.errors.transaction_date" />
                </div>
            </div>
        </div>

        <div
            class="relative p-4 overflow-hidden transition-colors bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <div class="mb-4">
                <label
                    class="block mb-1 text-xs font-bold text-gray-500 dark:text-gray-400"
                    >Barang Terpilih</label
                >
                <div
                    class="text-base font-bold text-gray-800 break-words dark:text-white"
                >
                    {{
                        stagingItem.name
                            ? stagingItem.name
                            : "Belum ada barang dipilih"
                    }}
                </div>
                <div
                    v-if="stagingItem.code"
                    class="text-xs font-mono text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-300 inline-block px-1.5 py-0.5 rounded mt-1"
                >
                    {{ stagingItem.code }}
                </div>
            </div>

            <div class="flex gap-4">
                <div
                    class="relative flex-shrink-0 w-24 h-24 overflow-hidden bg-gray-100 border border-gray-200 rounded-lg dark:bg-gray-700 dark:border-gray-600"
                >
                    <div
                        class="absolute inset-0 z-0 flex flex-col items-center justify-center text-gray-400 dark:text-gray-500 font-bold text-[10px]"
                    >
                        <svg
                            class="w-8 h-8 mb-1 opacity-50"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                            ></path>
                        </svg>
                        <span class="text-[10px] font-medium">No Image</span>
                    </div>
                    <img
                        :src="stagingItem.image_url"
                        loading="lazy"
                        decoding="async"
                        onload="this.classList.remove('opacity-0')"
                        onerror="this.style.display='none'"
                        class="absolute inset-0 z-10 object-cover w-full h-full opacity-0"
                    />
                </div>

                <div class="flex-1 space-y-3">
                    <div>
                        <label
                            class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400"
                            >Qty</label
                        >
                        <input
                            @@keydown.enter.prevent="$emit('saveStaging')"
                            ref="qtyInputMobile"
                            v-model.number="stagingItem.quantity"
                            type="number"
                            min="1"
                            placeholder="0"
                            :disabled="!stagingItem.product_id"
                            class="w-full px-3 py-2 text-sm transition-colors bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 disabled:bg-gray-100 dark:disabled:bg-gray-800 dark:disabled:text-gray-600"
                        />
                    </div>
                    <div class="flex justify-between gap-3">
                        <div>
                            <label
                                class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >Harga Beli</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute text-xs text-gray-500 left-3 top-2 dark:text-gray-400"
                                    >Rp</span
                                >
                                <input
                                    :value="displayedPrice"
                                    @keydown.enter.prevent="
                                        $emit('saveStaging')
                                    "
                                    @input="
                                        $emit(
                                            'update:displayedPrice',
                                            $event.target.value
                                        )
                                    "
                                    type="text"
                                    placeholder="0"
                                    :disabled="!stagingItem.product_id"
                                    class="w-full py-2 pl-8 pr-3 font-mono text-sm text-right transition-colors bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 disabled:bg-gray-100 dark:disabled:bg-gray-800 dark:disabled:text-gray-600"
                                />
                            </div>
                        </div>
                        <div>
                            <label
                                class="block mb-1 text-xs font-medium text-gray-500 dark:text-gray-400"
                                >Subtotal Item</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute text-xs text-gray-500 left-3 top-2 dark:text-gray-400"
                                    >Rp</span
                                >
                                <input
                                    :value="
                                        parseRupiah(
                                            (stagingItem.quantity || 0) *
                                                (stagingItem.purchase_price ||
                                                    0)
                                        )
                                    "
                                    type="text"
                                    placeholder="0"
                                    :disabled="true"
                                    class="w-full py-2 pl-8 pr-3 font-mono text-sm text-right transition-colors bg-white border border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 disabled:bg-gray-100 dark:disabled:bg-gray-800 dark:disabled:text-gray-600"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 mt-4">
                <button
                    @click="$emit('resetStaging')"
                    type="button"
                    class="col-span-1 py-2.5 text-xs font-bold text-red-600 bg-red-50 border border-red-100 rounded-lg hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:border-red-900/30 dark:hover:bg-red-900/40"
                >
                    RESET
                </button>
                <button
                    @click="$emit('saveStaging')"
                    :disabled="!stagingItem.product_id"
                    type="button"
                    class="col-span-2 py-2.5 text-xs font-bold text-white bg-lime-600 rounded-lg hover:bg-lime-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 dark:disabled:text-gray-500 shadow-sm"
                >
                    {{ isEditingMode ? "UPDATE ITEM" : "TAMBAH KE LIST" }}
                </button>
            </div>
        </div>

        <div
            class="overflow-hidden transition-colors bg-white border border-gray-100 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <button
                @click="showDetails = !showDetails"
                type="button"
                class="flex items-center justify-between w-full p-3 text-sm font-bold text-gray-700 transition dark:text-gray-200 bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
                <span>Rincian Data Barang</span>
                <svg
                    :class="{ 'rotate-180': showDetails }"
                    class="w-5 h-5 text-gray-500 transition-transform dark:text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                    />
                </svg>
            </button>

            <div
                v-show="showDetails"
                class="p-4 space-y-4 text-xs border-t border-gray-100 dark:border-gray-700"
            >
                <div
                    class="flex justify-between pb-2 text-gray-800 border-b border-gray-100 dark:border-gray-700 dark:text-gray-300"
                >
                    <div class="text-center">
                        <span
                            class="block text-gray-400 dark:text-gray-500 mb-0.5"
                            >Kategori</span
                        >
                        <span class="font-medium">{{
                            stagingItem.category || "-"
                        }}</span>
                    </div>
                    <div class="text-center">
                        <span
                            class="block text-gray-400 dark:text-gray-500 mb-0.5"
                            >Satuan</span
                        >
                        <span class="font-medium">{{
                            stagingItem.unit || "-"
                        }}</span>
                    </div>
                    <div class="text-center">
                        <span
                            class="block text-gray-400 dark:text-gray-500 mb-0.5"
                            >Ukuran</span
                        >
                        <span class="font-medium">{{
                            stagingItem.size || "-"
                        }}</span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-2 text-gray-700 gap-x-4 gap-y-3 dark:text-gray-300"
                >
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Sisa Stok:</span
                        >
                        <span class="font-bold">{{
                            stagingItem.current_stock || "0"
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Rek. Restok:</span
                        >
                        <span
                            class="font-bold text-orange-500 dark:text-orange-400"
                            >{{
                                stagingItem.restock_recommendation || "0"
                            }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Stok Masuk:</span
                        >
                        <span
                            class="font-bold text-lime-600 dark:text-lime-400"
                            >{{ stagingItem.quantity || "0" }}</span
                        >
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500 dark:text-gray-400"
                            >Total Akhir:</span
                        >
                        <span class="font-bold">{{
                            (stagingItem.current_stock || 0) +
                            (stagingItem.quantity || 0)
                        }}</span>
                    </div>
                    <div
                        class="flex justify-between col-span-2 pt-2 border-t border-gray-100 dark:border-gray-700"
                    >
                        <span class="text-gray-500 dark:text-gray-400"
                            >Subtotal Item Ini:</span
                        >
                        <span class="font-bold text-gray-800 dark:text-white">
                            {{
                                formatRupiah(
                                    (stagingItem.quantity || 0) *
                                        (stagingItem.purchase_price || 0)
                                )
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 mt-2">
            <button
                @click="$emit('openRecommendation')"
                type="button"
                class="flex items-center justify-center gap-2 py-3 text-xs font-bold text-white uppercase transition bg-yellow-500 rounded-lg shadow-sm hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-700"
            >
                <svg
                    class="w-4 h-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                Rekomendasi
            </button>

            <Link :href="route('purchases.index')" class="w-full">
                <button
                    type="button"
                    class="w-full py-3 text-xs font-bold text-gray-700 uppercase transition bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600"
                >
                    Kembali
                </button>
            </Link>
        </div>

        <button
            @click="$emit('submitTransaction')"
            :disabled="formHeader.processing || cartItems.length === 0"
            type="button"
            class="w-full py-4 text-sm font-extrabold tracking-widest text-white uppercase transition-all bg-blue-600 rounded-lg shadow-md hover:bg-blue-700 disabled:bg-gray-400 dark:disabled:bg-gray-700 dark:disabled:text-gray-500"
        >
            {{
                formHeader.processing
                    ? "Menyimpan Transaksi..."
                    : "SIMPAN TRANSAKSI"
            }}
        </button>
    </div>
</template>
