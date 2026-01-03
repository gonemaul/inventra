<script setup>
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePosRecap } from "@/Composable/usePosRecap";

const props = defineProps({
    products: Array,
    categories: Array,
});

// Panggil Logic Rekap
const {
    searchQuery,
    selectedCategory,
    selectedProductType,
    currentProductType,
    cart,
    filteredProducts,
    totalRevenue,
    form,
    addToCart,
    removeItem,
    processRecap,
    clearCart,
    transactionDate,
} = usePosRecap(props);

// Helper Format
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);
const getItemQty = (productId) => {
    const item = cart.value.find((i) => i.id === productId);
    return item ? item.quantity : 0;
};
</script>

<template>
    <Head title="Input Rekap" />

    <AuthenticatedLayout>
        <div
            class="flex flex-col w-full gap-4 lg:h-[85vh] h-[100vh] overflow-hidden lg:flex-row"
        >
            <div
                class="w-full lg:w-[40%] flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-md border border-gray-300 dark:border-gray-700 overflow-hidden h-1/2 lg:h-full"
            >
                <div
                    class="z-10 p-3 space-y-3 bg-white border-b border-gray-100 dark:border-gray-700 shrink-0 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between">
                        <h2
                            class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white"
                        >
                            <span class="text-lime-500">📦</span> Katalog
                        </h2>
                        <div class="relative w-1/2">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari..."
                                class="w-full pl-8 pr-3 py-1.5 bg-gray-100 dark:bg-gray-900 border border-gray-200 rounded-lg text-sm focus:ring-1 focus:ring-lime-500"
                            />
                            <span
                                class="absolute left-2.5 top-2 text-gray-400 text-xs"
                                >🔍</span
                            >
                        </div>
                    </div>

                    <div class="mb-4 space-y-2">
                        <div
                            class="flex gap-2 pb-1 overflow-x-auto custom-scroll-x"
                        >
                            <button
                                @click="selectedCategory = 'all'"
                                :class="
                                    selectedCategory === 'all'
                                        ? 'bg-lime-500 text-white shadow-md shadow-lime-500/20'
                                        : 'bg-white border border-gray-200 text-gray-600 hover:border-lime-400'
                                "
                                class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all active:scale-95"
                            >
                                Semua
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                @click="selectedCategory = cat.id"
                                :class="
                                    selectedCategory === cat.id
                                        ? 'bg-lime-500 text-white shadow-md shadow-lime-500/20'
                                        : 'bg-white border border-gray-200 text-gray-600 hover:border-lime-400'
                                "
                                class="px-3 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all active:scale-95"
                            >
                                {{ cat.name }}
                            </button>
                        </div>

                        <Transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="transform -translate-y-2 opacity-0"
                            enter-to-class="transform translate-y-0 opacity-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="transform translate-y-0 opacity-100"
                            leave-to-class="transform -translate-y-2 opacity-0"
                        >
                            <div
                                v-if="
                                    selectedCategory !== 'all' &&
                                    currentProductType.length > 0
                                "
                                class="flex gap-2 pb-1 pl-1 overflow-x-auto custom-scroll-x"
                            >
                                <button
                                    @click="selectedProductType = 'all'"
                                    :class="
                                        selectedProductType === 'all'
                                            ? 'bg-lime-100 text-lime-700 border border-lime-200'
                                            : 'bg-gray-50 text-gray-500 border border-transparent hover:bg-gray-100'
                                    "
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold whitespace-nowrap transition-all"
                                >
                                    Semua
                                    {{
                                        categories.find(
                                            (c) => c.id === selectedCategory
                                        )?.name
                                    }}
                                </button>

                                <button
                                    v-for="sub in currentProductType"
                                    :key="sub.id"
                                    @click="selectedProductType = sub.id"
                                    :class="
                                        selectedProductType === sub.id
                                            ? 'bg-lime-100 text-lime-700 border border-lime-200'
                                            : 'bg-gray-50 text-gray-500 border border-transparent hover:bg-gray-100'
                                    "
                                    class="px-2.5 py-1 rounded-lg text-[10px] font-bold whitespace-nowrap transition-all"
                                >
                                    {{ sub.name }}
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>

                <div
                    class="flex-1 p-3 overflow-y-auto custom-scroll bg-gray-50 dark:bg-gray-900/50"
                >
                    <div
                        v-if="filteredProducts.length === 0"
                        class="flex flex-col items-center justify-center h-40 text-sm text-gray-400"
                    >
                        <span>Produk tidak ditemukan</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 xl:grid-cols-3">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            @click="addToCart(product)"
                            class="relative flex flex-col justify-between p-2.5 transition-all bg-white border cursor-pointer dark:bg-gray-800 rounded-xl dark:border-gray-700 hover:shadow-lg hover:-translate-y-0.5 active:scale-95 group h-full"
                            :class="
                                product.stock === 0
                                    ? 'opacity-50 cursor-not-allowed hover:border-red-400 hover:shadow-none hover:-translate-y-0 active:scale-100'
                                    : 'border-gray-200 hover:border-lime-400'
                            "
                            ,
                        >
                            <div
                                v-if="getItemQty(product.id) > 0"
                                class="absolute z-10 flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-orange-500 rounded-full shadow-md -top-2 -right-2 animate-bounce-in"
                            >
                                {{ getItemQty(product.id) }}
                            </div>

                            <div>
                                <div
                                    class="h-8 mb-1.5 text-xs font-bold leading-tight text-gray-700 dark:text-gray-200 line-clamp-2 pr-2"
                                >
                                    {{ product.name }}
                                </div>

                                <div class="flex flex-wrap gap-1 mb-2">
                                    <span
                                        v-if="product.size"
                                        class="px-1.5 py-0.5 text-[10px] font-medium text-gray-600 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600"
                                    >
                                        {{ product.size?.name }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-end justify-between mt-auto">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[9px] text-gray-400 uppercase tracking-wider"
                                        >Stok</span
                                    >
                                    <span
                                        class="text-xs font-semibold"
                                        :class="
                                            product.stock <= 5
                                                ? 'text-red-500'
                                                : 'text-gray-600 dark:text-gray-300'
                                        "
                                    >
                                        {{ product.stock }}
                                        <span class="text-[10px]">{{
                                            product.unit?.name
                                        }}</span>
                                    </span>
                                </div>
                                <div class="relative text-right">
                                    <div
                                        class="text-sm font-black transition-opacity duration-200 text-lime-600 dark:text-lime-400"
                                        :class="
                                            product.stock > 0
                                                ? 'group-hover:opacity-0 group-hover:invisible'
                                                : ''
                                        "
                                    >
                                        {{ rp(product.selling_price) }}
                                    </div>

                                    <div
                                        v-if="product.stock > 0"
                                        class="absolute bottom-0 right-0 flex items-center invisible gap-1 text-xs font-bold transition-all duration-200 opacity-0 group-hover:visible group-hover:opacity-100 text-lime-600"
                                    >
                                        <span>+ Add</span>
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-4 h-4"
                                            viewBox="0 0 20 20"
                                            fill="currentColor"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="w-full lg:w-[60%] flex flex-col bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden h-1/2 lg:h-full relative"
            >
                <div
                    class="z-20 flex items-center justify-between p-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shrink-0"
                >
                    <div class="flex items-center gap-3">
                        <h3
                            class="hidden font-bold text-gray-800 dark:text-white sm:block"
                        >
                            📝 Rekap
                        </h3>

                        <div
                            class="flex items-center px-2 py-1 bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600"
                        >
                            <label
                                class="text-[10px] font-bold text-gray-500 dark:text-gray-300 uppercase mr-2"
                                >Tgl:</label
                            >
                            <input
                                type="date"
                                v-model="transactionDate"
                                class="h-5 p-0 text-sm font-bold text-gray-800 bg-transparent border-none cursor-pointer dark:text-white focus:ring-0"
                            />
                        </div>
                    </div>

                    <button
                        v-if="cart.length"
                        @click="clearCart"
                        class="px-2 py-1 text-xs font-bold text-red-500 transition rounded hover:bg-red-50"
                    >
                        Reset
                    </button>
                </div>

                <div
                    class="relative flex-1 min-h-0 overflow-auto bg-white custom-scroll custom-scroll-x dark:bg-gray-800"
                >
                    <table class="w-full text-left border-collapse">
                        <thead
                            class="sticky top-0 z-10 bg-gray-100 dark:bg-gray-900 text-[10px] uppercase font-bold text-gray-500 dark:text-gray-400 tracking-wider shadow-sm"
                        >
                            <tr>
                                <th
                                    class="p-3 bg-gray-100 min-w-48 lg:min-w-1/2 dark:bg-gray-900"
                                >
                                    Produk
                                </th>
                                <th
                                    class="w-20 p-3 text-center bg-gray-100 dark:bg-gray-900"
                                >
                                    Qty
                                </th>
                                <th
                                    class="p-3 text-right bg-gray-100 w-28 dark:bg-gray-900"
                                >
                                    Harga
                                </th>
                                <th
                                    class="p-3 text-right bg-gray-100 w-28 dark:bg-gray-900"
                                >
                                    Subtotal
                                </th>
                                <th
                                    class="w-8 p-3 text-center bg-gray-100 dark:bg-gray-900"
                                ></th>
                            </tr>
                        </thead>

                        <tbody
                            class="text-sm divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr v-if="cart.length === 0">
                                <td
                                    colspan="5"
                                    class="p-10 text-center text-gray-400"
                                >
                                    <div class="mb-2 text-4xl opacity-30">
                                        📥
                                    </div>
                                    Pilih produk di katalog sebelah kiri.
                                </td>
                            </tr>

                            <tr
                                v-for="(item, index) in cart"
                                :key="index"
                                class="transition group hover:bg-lime-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="p-3 align-middle">
                                    <div
                                        class="text-xs font-bold text-gray-800 dark:text-gray-200 sm:text-sm line-clamp-2"
                                    >
                                        {{ item.name }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">
                                        {{ item.code }}
                                    </div>
                                </td>

                                <td class="p-3 align-middle">
                                    <input
                                        type="number"
                                        v-model="item.quantity"
                                        min="1"
                                        class="w-16 py-1 text-sm font-bold text-center text-gray-800 border border-gray-200 rounded-lg dark:text-white bg-gray-50 dark:bg-gray-900 dark:border-gray-600 focus:ring-lime-500 focus:border-lime-500"
                                        @focus="$event.target.select()"
                                    />
                                </td>

                                <td class="p-3 text-right align-middle">
                                    <input
                                        type="number"
                                        v-model="item.selling_price"
                                        class="w-24 p-0 text-sm font-medium text-right text-gray-600 bg-transparent border-b border-gray-300 border-dashed border-none dark:text-gray-300 focus:ring-0 hover:border-gray-400"
                                        @focus="$event.target.select()"
                                    />
                                </td>

                                <td
                                    class="p-3 text-xs font-bold text-right align-middle text-lime-600 dark:text-lime-400 sm:text-sm"
                                >
                                    {{ rp(item.quantity * item.selling_price) }}
                                </td>

                                <td class="p-3 text-center align-middle">
                                    <button
                                        @click="removeItem(index)"
                                        class="p-1 text-gray-300 hover:text-red-500"
                                    >
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="items-center justify-between gap-4 p-4 border-t border-gray-200 lg:flex dark:border-gray-700 bg-gray-50 dark:bg-gray-900 shrink-0"
                >
                    <div>
                        <span
                            class="text-[10px] font-bold text-gray-500 uppercase"
                            >Total Omzet</span
                        >
                        <div
                            class="mt-1 text-xl font-black leading-none text-gray-900 lg:text-2xl dark:text-white"
                        >
                            {{ rp(totalRevenue) }}
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-2 lg:mt-0">
                        <div class="w-full sm:flex-1">
                            <div class="relative">
                                <input
                                    type="text"
                                    v-model="form.notes"
                                    placeholder="Tambahkan Catatan Transaksi (Opsional)..."
                                    class="w-full pl-9 pr-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-800 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-lime-500 focus:border-transparent transition-all shadow-inner"
                                />
                                <span
                                    class="absolute text-gray-400 left-3 top-3"
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
                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                        ></path>
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <button
                            @click="processRecap"
                            :disabled="!cart.length || form.processing"
                            class="flex items-center gap-2 px-4 py-2 font-bold text-white transition shadow-lg lg:px-6 lg:py-3 bg-lime-500 hover:bg-lime-600 rounded-xl shadow-lime-500/30 disabled:opacity-50 active:scale-95"
                        >
                            <span
                                v-if="form.processing"
                                class="w-4 h-4 border-2 border-white rounded-full animate-spin border-t-transparent"
                            ></span>
                            <span>SIMPAN</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scrollbar Vertical (Untuk Tabel/List Produk) */
.custom-scroll::-webkit-scrollbar {
    width: 5px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 10px;
}
.custom-scroll::-webkit-scrollbar-thumb:hover {
    background: #d1d5db;
}

/* [BARU] Scrollbar Horizontal Tipis (Untuk Kategori) */
.custom-scroll-x::-webkit-scrollbar {
    height: 3px; /* Tinggi sangat kecil */
}
.custom-scroll-x::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll-x::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}
.dark .custom-scroll-x::-webkit-scrollbar-thumb {
    background: #4b5563;
}

/* Hilangkan panah input number */
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
</style>
