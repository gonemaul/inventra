<script setup>
import { ref, watch, onMounted } from "vue";
import axios from "axios";
import debounce from "lodash/debounce";

// --- PROPS & EMITS ---
const props = defineProps({
    supplierId: {
        type: [Number, String],
        required: true,
    },
});

const emit = defineEmits(["select-product"]);

// --- STATE ---
const products = ref([]);
const isLoading = ref(false); // Loading awal / ganti supplier
const isLoadingMore = ref(false); // Loading infinite scroll
const page = ref(1);
const hasMore = ref(true);
const searchKeyword = ref("");
const observerTarget = ref(null); // Elemen pemantau scroll bawah

// --- 1. CORE LOGIC: FETCH DATA ---
const fetchProducts = async (reset = false) => {
    // Jangan fetch jika tidak ada supplier
    if (!props.supplierId) return;

    if (reset) {
        isLoading.value = true;
        page.value = 1;
        products.value = []; // Kosongkan data lama
        hasMore.value = true;
    } else {
        isLoadingMore.value = true;
    }

    try {
        const response = await axios.get(
            `/purchases/products/${props.supplierId}`,
            {
                params: {
                    page: page.value,
                    search: searchKeyword.value,
                },
            }
        );

        const newItems = response.data.data;

        if (reset) {
            products.value = newItems;
        } else {
            products.value = [...products.value, ...newItems];
        }

        // Cek next page
        hasMore.value = !!response.data.next_page_url;
        if (hasMore.value) page.value++;
    } catch (error) {
        console.error("Gagal load katalog:", error);
    } finally {
        isLoading.value = false;
        isLoadingMore.value = false;
    }
};

// --- 2. FITUR: AUTO REFRESH SAAT SUPPLIER BERUBAH ---
watch(
    () => props.supplierId,
    (newId) => {
        if (newId) {
            // Reset Search & Load Ulang dari Page 1
            searchKeyword.value = "";
            fetchProducts(true);
        } else {
            products.value = []; // Kosongkan jika supplier di-unselect
        }
    },
    { immediate: true }
);
// immediate: true -> agar jalan juga saat pertama kali komponen di-mount

// --- 3. FITUR: SEARCH ---
const handleSearch = debounce(() => {
    fetchProducts(true);
}, 500);

// --- 4. FITUR: INFINITE SCROLL ---
const onIntersect = (entries) => {
    const entry = entries[0];
    if (
        entry.isIntersecting &&
        hasMore.value &&
        !isLoading.value &&
        !isLoadingMore.value
    ) {
        fetchProducts(false); // Load Next Page
    }
};

onMounted(() => {
    const observer = new IntersectionObserver(onIntersect, { threshold: 0.1 });
    if (observerTarget.value) observer.observe(observerTarget.value);
});

// --- HELPER FORMAT RUPIAH ---
const formatRupiah = (val) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
};

// --- ACTION ---
const selectItem = (item) => {
    emit("select-product", item);
};
</script>

<template>
    <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-900">
        <div
            class="sticky top-0 z-20 p-3 bg-white border-b shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <div class="relative">
                <div
                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                >
                    <svg
                        class="w-5 h-5 text-gray-400"
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
                    v-model="searchKeyword"
                    @input="handleSearch"
                    type="text"
                    class="block w-full py-2 pl-10 pr-3 leading-5 placeholder-gray-500 bg-white border border-gray-300 rounded-lg focus:outline-none focus:placeholder-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    placeholder="Cari nama barang atau kode..."
                />
            </div>
        </div>

        <div class="flex-1 p-3 overflow-y-auto custom-scrollbar">
            <div
                v-if="isLoading"
                class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
            >
                <div
                    v-for="n in 8"
                    :key="n"
                    class="flex flex-col p-2 bg-white rounded-lg shadow dark:bg-gray-800 animate-pulse"
                >
                    <div
                        class="w-full mb-2 bg-gray-200 rounded aspect-square dark:bg-gray-700"
                    ></div>
                    <div
                        class="w-3/4 h-4 mb-2 bg-gray-200 rounded dark:bg-gray-700"
                    ></div>
                    <div
                        class="w-1/2 h-3 mt-auto bg-gray-200 rounded dark:bg-gray-700"
                    ></div>
                </div>
            </div>

            <div
                v-else-if="products.length > 0"
                class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
            >
                <div
                    v-for="item in products"
                    :key="item.id"
                    @click="selectItem(item)"
                    class="relative flex flex-col overflow-hidden transition-all bg-white border border-gray-200 shadow-sm cursor-pointer group dark:bg-gray-800 dark:border-gray-700 rounded-xl hover:shadow-md"
                >
                    <div
                        class="relative w-full overflow-hidden bg-gray-100 aspect-square dark:bg-gray-700"
                    >
                        <img
                            :src="item.image_url"
                            loading="lazy"
                            decoding="async"
                            class="absolute inset-0 z-10 object-contain w-full h-full transition-transform duration-500 opacity-0 group-hover:scale-110"
                            onload="this.classList.remove('opacity-0')"
                            onerror="this.style.display='none'"
                        />
                        <div
                            class="absolute inset-0 z-0 flex flex-col items-center justify-center text-gray-400"
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
                            <span class="text-[10px]">No Image</span>
                        </div>

                        <div class="absolute z-20 top-2 left-2">
                            <span
                                class="bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded font-medium backdrop-blur-sm"
                            >
                                Stok: {{ item.stock }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 p-3">
                        <div
                            class="text-[10px] text-gray-500 mb-1 flex justify-between"
                        >
                            <span
                                class="px-1 bg-gray-100 rounded dark:bg-gray-700"
                                >{{ item.code }}</span
                            >
                            <span>{{ item.unit?.name || "Pcs" }}</span>
                        </div>

                        <h3
                            class="text-xs md:text-sm font-semibold text-gray-800 dark:text-gray-200 leading-tight mb-2 line-clamp-2 min-h-[2.5em]"
                        >
                            {{ item.name }}
                        </h3>
                        <p>
                            Rekomendasi
                            {{
                                item.insights.find((i) => i.type === "restock")
                                    ?.payload?.suggested_qty
                            }}
                        </p>

                        <div
                            class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100 border-dashed dark:border-gray-700"
                        >
                            <div class="flex flex-col">
                                <span class="text-[10px] text-gray-400"
                                    >Harga Beli</span
                                >
                                <span
                                    class="text-sm font-bold text-blue-600 dark:text-blue-400"
                                >
                                    {{ formatRupiah(item.purchase_price) }}
                                </span>
                            </div>

                            <button
                                class="bg-blue-50 text-blue-600 p-1.5 rounded-lg hover:bg-blue-600 hover:text-white transition-colors"
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
                                        d="M12 4v16m8-8H4"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center py-10 text-gray-400"
            >
                <svg
                    class="w-12 h-12 mb-2 opacity-50"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    ></path>
                </svg>
                <p class="text-sm">Tidak ada produk ditemukan</p>
            </div>

            <div v-if="isLoadingMore" class="flex justify-center py-4">
                <svg
                    class="w-5 h-5 text-blue-500 animate-spin"
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
            </div>

            <div ref="observerTarget" class="w-full h-4"></div>
        </div>
    </div>
</template>
