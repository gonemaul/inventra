<script setup>
import { ref, watch, nextTick, onMounted } from "vue";

// PROPS: Data yang diterima dari Parent (Logic Composable)
const props = defineProps({
    searchResults: {
        type: Array,
        default: () => [],
    },
    isLoading: {
        type: Boolean,
        default: false,
    },
});

// EMITS: Event yang dikirim ke Parent
const emit = defineEmits(["search", "add"]);

// STATE LOCAL
const query = ref("");
const searchInput = ref(null); // Ref untuk elemen DOM input

// WATCHER: Kirim query ke parent setiap user mengetik
watch(query, (newVal) => {
    emit("search", newVal);
});

// FUNGSI SELECT ITEM
const selectItem = (product) => {
    emit("add", product); // Kirim produk terpilih ke parent

    // Reset pencarian setelah dipilih
    query.value = "";

    // Note: Fokus akan dipindah ke Qty oleh Parent,
    // jadi kita tidak perlu handling fokus di sini saat select.
};

// EXPOSE FUNCTION: Agar Parent bisa memaksa kursor masuk ke sini
// (Digunakan saat user tekan Enter di kolom Qty lalu balik ke sini)
const focusInput = () => {
    nextTick(() => {
        if (searchInput.value) searchInput.value.focus();
    });
};

defineExpose({ focusInput });

// HELPERS VISUAL
const getInitials = (name) =>
    name ? name.substring(0, 2).toUpperCase() : "??";
const formatCurrency = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

// Auto focus saat pertama kali diload
onMounted(() => {
    focusInput();
});
</script>

<template>
    <div class="relative z-30 w-full">
        <div class="relative rounded-lg shadow-sm">
            <div
                class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none"
            >
                <svg
                    v-if="isLoading"
                    class="w-6 h-6 text-indigo-500 animate-spin"
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
                <svg
                    v-else
                    class="w-6 h-6 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    ></path>
                </svg>
            </div>

            <input
                ref="searchInput"
                type="text"
                v-model="query"
                class="w-full py-4 pl-12 pr-4 text-lg placeholder-gray-400 transition duration-150 ease-in-out border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Scan Barcode atau Ketik Nama Produk..."
                autocomplete="off"
            />

            <div
                v-if="query"
                class="absolute inset-y-0 right-0 flex items-center pr-3"
            >
                <button
                    @click="query = ''"
                    class="text-gray-400 hover:text-gray-600 focus:outline-none"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <div
            v-if="searchResults.length > 0 && query"
            class="absolute w-full mt-2 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-2xl max-h-80 ring-1 ring-black ring-opacity-5"
        >
            <ul>
                <li
                    v-for="product in searchResults"
                    :key="product.id"
                    @click="selectItem(product)"
                    class="items-center px-4 py-3 space-y-2 transition duration-150 ease-in-out border-b border-gray-100 cursor-pointer group hover:bg-indigo-50 last:border-b-0"
                >
                    <div class="flex gap-2">
                        <div
                            class="flex items-center justify-center flex-shrink-0 w-12 h-12 overflow-hidden bg-gray-100 border border-gray-200 rounded-md shadow-sm group-hover:border-indigo-200"
                        >
                            <img
                                v-if="product.image"
                                :src="product.image"
                                class="object-cover w-full h-full"
                            />
                            <span
                                v-else
                                class="text-sm font-bold text-gray-400 group-hover:text-indigo-500"
                            >
                                {{ getInitials(product.name) }}
                            </span>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div
                                class="text-sm font-bold text-gray-800 truncate group-hover:text-indigo-700"
                            >
                                {{ product.name }}
                            </div>
                            <div class="flex items-center mt-0.5 space-x-2">
                                <span
                                    class="bg-gray-100 text-gray-600 text-[10px] font-mono px-1.5 py-0.5 rounded border border-gray-200"
                                >
                                    {{ product.code }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <span
                            class="text-xs text-gray-500"
                            :class="
                                parseFloat(product.stock) <= 0
                                    ? 'text-red-500 font-bold'
                                    : ''
                            "
                        >
                            Stok: {{ product.stock }} {{ product.unit }}
                        </span>
                        <div class="text-right">
                            <div
                                class="font-mono text-sm font-bold text-indigo-600"
                            >
                                {{ formatCurrency(product.price) }}
                            </div>
                            <div
                                v-if="parseFloat(product.stock) <= 0"
                                class="text-[10px] text-red-600 font-bold uppercase mt-1"
                            >
                                Habis
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>

        <div
            v-if="query && searchResults.length === 0 && !isLoading"
            class="absolute w-full p-4 mt-2 text-sm text-center text-gray-500 bg-white border border-gray-200 rounded-lg shadow-lg"
        >
            Produk tidak ditemukan.
        </div>
    </div>
</template>
