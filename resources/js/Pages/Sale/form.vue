<script setup>
import { Head, Link } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { ref } from "vue";
import { useSaleLogic } from "@/Composable/useSaleLogic";
import ProductSearch from "./partials/ProductSearch.vue";
import SaleTable from "./partials/SaleTable.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";

// STATE
const toast = useToast();
const { isActionLoading } = useActionLoading();
const productSearchRef = ref(null);
const focusSearch = () => {
    nextTick(() => {
        if (productSearchRef.value) {
            productSearchRef.value.focusInput();
        }
    });
};
// 3. Gunakan Logic
const {
    form,
    grandTotal,
    totalQty,
    formatCurrency,
    handleSearch,
    searchResults,
    isLoadingSearch,
    addItem,
    removeItem,
    checkIntegerInput,
    isDecimalAllowed,
    calculateSubtotal,
    calculateQty,
    hasInvalidQty,
    hasStockError,
    submitForm,
} = useSaleLogic();

isActionLoading.value = form.processing;
</script>

<template>
    <Head title="Input Rekap Penjualan" />

    <div class="flex flex-col h-screen overflow-hidden bg-gray-50">
        <div
            class="relative z-20 flex flex-col items-center justify-between flex-shrink-0 gap-4 px-6 py-4 bg-white border-b border-gray-200 shadow-sm md:flex-row"
        >
            <div class="flex items-center flex-1 w-full gap-4">
                <div class="w-40">
                    <InputLabel
                        value="Tanggal Laporan"
                        class="mb-1 text-xs font-bold text-gray-500 uppercase"
                    />
                    <input
                        type="date"
                        v-model="form.report_date"
                        class="w-full text-sm font-bold border-gray-300 rounded focus:ring-indigo-500"
                    />
                </div>
                <div class="flex-1">
                    <InputLabel
                        value="Catatan"
                        class="mb-1 text-xs font-bold text-gray-500 uppercase"
                    />
                    <input
                        type="text"
                        v-model="form.notes"
                        class="w-full text-sm border-gray-300 rounded focus:ring-indigo-500"
                        placeholder="Shift pagi, cuaca hujan..."
                    />
                </div>
            </div>

            <div
                class="flex items-center justify-between order-2 w-full h-auto gap-2 px-0 py-2 mt-2 border-l-0 border-r border-gray-200 rounded md:flex-row md:gap-8 md:px-8 md:border-l md:h-12 md:w-auto md:justify-start md:py-0 md:order-none bg-gray-50 md:bg-transparent md:rounded-none md:mt-0"
            >
                <div class="flex-1 text-center md:flex-none">
                    <div
                        class="text-[9px] md:text-[10px] text-gray-400 uppercase font-bold tracking-wider"
                    >
                        Item
                    </div>
                    <div
                        class="text-base font-bold leading-none text-gray-700 md:text-lg"
                    >
                        {{ form.items.length }}
                    </div>
                </div>
                <div
                    class="flex-1 pl-2 text-center border-l border-gray-200 md:flex-none md:border-0 md:pl-0"
                >
                    <div
                        class="text-[9px] md:text-[10px] text-gray-400 uppercase font-bold tracking-wider"
                    >
                        Qty
                    </div>
                    <div
                        class="text-base font-bold leading-none text-gray-700 md:text-lg"
                    >
                        {{ totalQty }}
                    </div>
                </div>
            </div>
            <!-- <div
                class="items-center hidden h-12 gap-8 px-8 border-l border-r border-gray-200 md:flex"
            >
                <div class="text-center">
                    <div
                        class="text-[10px] text-gray-400 uppercase font-bold tracking-wider"
                    >
                        Total Item
                    </div>
                    <div class="text-lg font-bold text-gray-700">
                        {{ form.items.length }}
                    </div>
                </div>
                <div class="text-center">
                    <div
                        class="text-[10px] text-gray-400 uppercase font-bold tracking-wider"
                    >
                        Total Qty
                    </div>
                    <div class="text-lg font-bold text-gray-700">
                        {{ totalQty }}
                    </div>
                </div>
            </div> -->
            <div class="order-1 w-full text-right md:w-auto md:order-none">
                <div
                    class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1"
                >
                    Estimasi Omset
                </div>
                <div
                    class="text-2xl font-black leading-none tracking-tight text-indigo-600 md:text-3xl"
                >
                    {{ formatCurrency(grandTotal) }}
                </div>
            </div>
            <!-- <div class="w-full text-right md:w-auto">
                <div
                    class="text-[10px] text-gray-400 uppercase tracking-widest font-bold mb-1"
                >
                    Estimasi Omset
                </div>
                <div
                    class="text-3xl font-black leading-none tracking-tight text-indigo-600"
                >
                    {{ formatCurrency(grandTotal) }}
                </div>
            </div> -->
        </div>

        <div class="relative flex flex-col flex-1 min-h-0">
            <div class="z-10 p-4 pb-0">
                <ProductSearch
                    ref="productSearchRef"
                    :search-results="searchResults"
                    :is-loading="isLoadingSearch"
                    @search="handleSearch"
                    @add="addItem"
                />
            </div>

            <div class="flex flex-col flex-1 p-4 pt-2 overflow-hidden">
                <SaleTable
                    :items="form.items"
                    :is-decimal-allowed="isDecimalAllowed"
                    :check-integer="checkIntegerInput"
                    @remove="removeItem"
                    @update-calc="calculateSubtotal"
                    @update-qty="calculateQty"
                />
            </div>

            <div
                class="z-20 flex justify-end flex-shrink-0 gap-3 p-4 bg-white border-t border-gray-200"
            >
                <Link
                    :href="route('sales.index')"
                    class="px-6 py-3 text-sm font-medium text-gray-700 transition bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Kembali
                </Link>
                <PrimaryButton
                    :disabled="
                        form.items.length === 0 ||
                        form.processing ||
                        hasInvalidQty ||
                        hasStockError
                    "
                    :class="{
                        'opacity-50  cursor-not-allowed':
                            form.items.length === 0 ||
                            form.processing ||
                            hasInvalidQty ||
                            hasStockError,
                    }"
                    @click="submitForm"
                    class="flex items-center gap-2 px-8 py-3 text-base shadow-lg"
                >
                    <svg
                        v-if="form.processing"
                        class="w-5 h-5 text-white animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span v-if="form.processing">Menyimpan...</span>
                    <span v-else>Simpan Rekap</span>
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
