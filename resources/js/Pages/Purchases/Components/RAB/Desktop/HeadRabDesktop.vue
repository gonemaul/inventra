<script setup>
import { Link } from "@inertiajs/vue3";
import { ref, watch, nextTick } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
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

const emit = defineEmits([
    "update:displayedPrice",
    "saveStaging",
    "resetStaging",
    "submitTransaction",
    "openRecommendation",
]);

const qtyInput = ref(null);
watch(
    () => props.stagingItem.product_id,
    (newVal) => {
        if (newVal) {
            nextTick(() => {
                // focus() untuk mengarahkan kursor
                // select() untuk memblok angka "1" default agar siap ketik timpa
                qtyInput.value?.select();
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
</script>

<template>
    <div class="hidden space-y-6 md:block">
        <div class="flex flex-col w-full gap-4 md:flex-row">
            <div
                class="flex flex-col gap-4 p-5 bg-white/70 backdrop-blur-xl border border-gray-200/50 shadow-lg rounded-2xl lg:w-1/2 dark:bg-lime-900/10 dark:border-lime-900/50 dark:shadow-lime-900/20"
            >
                <div
                    class="flex flex-col-reverse items-start gap-4 md:flex-row"
                >
                    <div class="flex flex-col w-full gap-4">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="md:col-span-2">
                                <label
                                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Supplier</label
                                >
                                <select
                                    :disabled="isEdit"
                                    v-model="formHeader.supplier_id"
                                    class="w-full px-3 py-2 transition-colors bg-white border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 sm:text-sm"
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
                                <InputError
                                    :message="formHeader.errors.supplier_id"
                                    class="mt-1"
                                />
                            </div>

                            <div class="md:col-span-1">
                                <label
                                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Tanggal</label
                                >
                                <input
                                    v-model="formHeader.transaction_date"
                                    type="date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 sm:text-sm transition-colors dark:[color-scheme:dark]"
                                />
                                <InputError
                                    :message="
                                        formHeader.errors.transaction_date
                                    "
                                    class="mt-1"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="md:col-span-1">
                                <label
                                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Qty</label
                                >
                                <input
                                    @keydown.enter.prevent="
                                        $emit('saveStaging')
                                    "
                                    ref="qtyInput"
                                    v-model.number="stagingItem.quantity"
                                    type="number"
                                    min="1"
                                    placeholder="0"
                                    :disabled="!stagingItem.product_id"
                                    class="w-full px-3 py-2 transition-colors bg-white border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-500 sm:text-sm"
                                />
                            </div>

                            <div class="md:col-span-2">
                                <label
                                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                    >Harga Beli</label
                                >
                                <div class="relative rounded-md shadow-sm">
                                    <div
                                        class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                                    >
                                        <span
                                            class="text-gray-500 dark:text-gray-400 sm:text-sm"
                                            >Rp</span
                                        >
                                    </div>
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
                                        class="w-full px-3 py-2 pl-10 font-mono text-right transition-colors bg-white border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:placeholder-gray-400 dark:disabled:bg-gray-800 dark:disabled:text-gray-500 sm:text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-shrink-0 w-full md:w-32">
                        <label
                            class="block mb-1 text-sm font-medium text-center text-gray-700 dark:text-gray-300 md:text-left"
                            >Preview</label
                        >
                        <div
                            class="relative w-full overflow-hidden bg-gray-100 border border-gray-300 rounded-lg aspect-square dark:bg-gray-700 dark:border-gray-600 group"
                        >
                            <div
                                class="absolute inset-0 z-0 flex flex-col items-center justify-center w-full h-full transition-colors duration-300"
                            >
                                <svg
                                    class="w-10 h-10 mb-1 text-gray-400 dark:text-gray-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    ></path>
                                </svg>
                                <span
                                    class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-wider"
                                    >No Image</span
                                >
                            </div>
                            <img
                                v-if="stagingItem.image_url"
                                :src="stagingItem.image_url"
                                alt="Preview"
                                loading="lazy"
                                class="absolute inset-0 z-10 object-cover w-full h-full transition-opacity duration-300 opacity-0"
                                onload="this.classList.remove('opacity-0')"
                                onerror="this.style.display='none'"
                            />
                        </div>
                    </div>
                </div>

                <div
                    class="flex flex-wrap justify-center gap-2 lg:justify-start"
                >
                    <Link :href="route('purchases.index')">
                        <SecondaryButton
                            type="button"
                            class="dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                            >Kembali</SecondaryButton
                        >
                    </Link>

                    <PrimaryButton
                        type="button"
                        @click="$emit('saveStaging')"
                        :disabled="!stagingItem.product_id"
                        class="dark:bg-lime-600 dark:hover:bg-lime-700 dark:focus:bg-lime-700"
                    >
                        {{ isEditingMode ? "Update Item" : "Tambah Item" }}
                    </PrimaryButton>

                    <button
                        @click="$emit('resetStaging')"
                        type="button"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-red-500 border border-transparent rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-600 dark:hover:bg-red-700"
                    >
                        Reset
                    </button>

                    <PrimaryButton
                        type="button"
                        @click="$emit('submitTransaction')"
                        :disabled="
                            formHeader.processing || cartItems.length === 0
                        "
                        class="dark:bg-lime-600 dark:hover:bg-lime-700 dark:focus:bg-lime-700"
                    >
                        {{ formHeader.processing ? "Menyimpan..." : "Simpan" }}
                    </PrimaryButton>

                    <button
                        @click="$emit('openRecommendation')"
                        type="button"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-yellow-500 border border-transparent rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:bg-yellow-600 dark:hover:bg-yellow-700"
                    >
                        Rekomendasi
                    </button>
                </div>
            </div>

            <div
                class="flex flex-col justify-between p-5 text-sm bg-white/70 backdrop-blur-xl border border-gray-200/50 shadow-lg rounded-2xl dark:bg-gray-900/90 dark:border-gray-800 relative overflow-hidden group"
            >
                <div class="absolute -right-6 -top-6 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/20 transition-all duration-500"></div>
                <div
                    class="pb-2 text-base text-gray-800 border-b-2 border-gray-400 dark:border-gray-600 text-semibold dark:text-white"
                >
                    {{
                        stagingItem.name
                            ? stagingItem.name + " ( " + stagingItem.code + " )"
                            : "Belum ada barang dipilih"
                    }}
                </div>
                <div class="mt-2 space-y-2 text-gray-700 dark:text-gray-300">
                    <div
                        class="flex justify-between font-medium text-gray-600 dark:text-gray-400"
                    >
                        <strong>Kategori</strong><strong>Satuan</strong
                        ><strong>Ukuran</strong>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ stagingItem.category || "-" }}</span>
                        <span>{{ stagingItem.unit || "-" }}</span>
                        <span>{{ stagingItem.size || "-" }}</span>
                    </div>
                </div>

                <div
                    class="grid grid-cols-3 gap-4 pt-4 mt-4 text-gray-800 border-t-2 border-gray-400 dark:border-gray-600 dark:text-gray-200"
                >
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Sisa Stok
                        </p>
                        <p>{{ stagingItem.current_stock || "0" }}</p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Rekom Restok
                        </p>
                        <p
                            class="font-bold text-orange-600 dark:text-orange-400"
                        >
                            {{ stagingItem.restock_recommendation || "0" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Stok Masuk
                        </p>
                        <p class="font-bold text-lime-600 dark:text-lime-400">
                            {{ stagingItem.quantity || "0" }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Total Stok
                        </p>
                        <p>
                            {{
                                (stagingItem.current_stock || 0) +
                                (stagingItem.quantity || 0)
                            }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Harga Beli
                        </p>
                        <p>
                            {{ formatRupiah(stagingItem.purchase_price || 0) }}
                        </p>
                    </div>
                    <div class="col-span-1">
                        <p
                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                        >
                            Subtotal
                        </p>
                        <p class="font-bold">
                            {{
                                formatRupiah(
                                    (stagingItem.quantity || 0) *
                                        (stagingItem.purchase_price || 0)
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="flex flex-col justify-between flex-1 w-full p-5 bg-gradient-to-br from-gray-900 to-gray-800 shadow-xl rounded-2xl border border-gray-800 relative overflow-hidden group dark:from-gray-950 dark:to-gray-900 dark:border-gray-900"
            >
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-lime-500/10 rounded-full blur-3xl group-hover:bg-lime-500/20 transition-all duration-500"></div>
                
                <div class="flex justify-between text-white/90 relative z-10">
                    <p class="text-lg font-bold">{{ totalUnit }} Unit</p>
                    <p class="text-lg font-bold">{{ totalMacam }} Macam</p>
                </div>
                <div class="mt-8 text-left">
                    <p
                        class="text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400"
                    >
                        Total Belanja
                    </p>
                    <p
                        class="text-4xl font-extrabold text-lime-400 drop-shadow-md"
                    >
                        {{ formatRupiah(totalBelanja) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
