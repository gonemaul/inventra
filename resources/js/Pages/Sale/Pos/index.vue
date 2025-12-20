<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
// import { defineAsyncComponent } from "vue";
import { usePosRealtime } from "@/Composable/usePosRealtime";

import ConfirmSubmit from "./ConfirmSubmit.vue";
import ScannerBox from "./ScannerBox.vue";
import BottomSheet from "@/Components/BottomSheet.vue";
import { useToast } from "vue-toastification";
import Cart from "./Cart.vue";
// const BarcodeScanner = defineAsyncComponent(() =>
//     import("@/Components/BarcodeScanner.vue")
// );
import BarcodeScanner from "@/Components/BarcodeScanner.vue";

const props = defineProps({
    categories: Array,
    customers: Array,
});

const pos = usePosRealtime(props);
// --- PANGGIL COMPOSABLE ---
const {
    // 1. STATE FORM & DATA
    form,
    filteredProducts, // Ganti 'products' atau 'searchResults' dengan ini
    isFetchingData, // Untuk indikator loading di search bar
    // 2. SEARCH & FILTER
    searchQuery, // v-model ke input cari barang
    selectedCategory, // v-model ke dropdown kategori
    loadMoreProducts, // Fungsi untuk infinite scroll
    // Scanner
    queryMember,
    queryProduk,
    // 4. CART ACTIONS
    addItem,
    grandTotal,
    changeAmount,
    isPaymentSufficient,
    hasInvalidQty,
    submitTransaction,
    // Utils
    rp,
} = pos;

// --- STATE LOKAL UI (Client Side Search) ---
const toast = useToast();
const showMobileCart = ref(false);
const showScanner = ref(false);
const showConfirmModal = ref(false);
const showQtyModal = ref(false);
const scanType = ref("product"); //product atau member

// State untuk Item yang sedang diproses di Modal
const currentItem = ref({
    id: null, // ID Produk (Database)
    name: "", // Nama Produk
    code: "", // SKU / Barcode
    price: 0, // Harga Jual (Sell Price)
    quantity: 1, // Jumlah yang mau dibeli (Default 1)
    stock: 0, // Sisa Stok (Untuk validasi batas maks)
    unit: "Pcs", // Satuan (Opsional, default Pcs)
    image: null, // (Opsional) Jika ada gambar produk
});
// Fungsi ini dipanggil saat Scan berhasil & Produk ditemukan
const prepareModalData = (productMaster) => {
    console.log(productMaster);
    currentItem.value = {
        id: productMaster.id,
        name: productMaster.name,
        code: productMaster.code,
        image_url: productMaster.image_url,
        // PENTING: Pastikan ambil Harga Jual, bukan Harga Beli
        price: parseFloat(
            productMaster.selling_price || productMaster.price || 0
        ),

        // Reset Qty ke 1 setiap kali scan baru
        quantity: 1,

        // Ambil stok untuk validasi (agar tidak minus)
        stock: parseInt(productMaster.stock || 0),

        // Ambil nama satuan jika ada relasi unit, kalau tidak default 'Pcs'
        unit: productMaster.unit?.name || "Pcs",
    };

    // Buka Modal
    showQtyModal.value = true;
};
// Berfungsi mendeteksi jika user scroll mentok bawah -> load data lagi
const handleScroll = (e) => {
    const { scrollTop, clientHeight, scrollHeight } = e.target;
    // Toleransi 50px sebelum mentok bawah
    if (scrollTop + clientHeight >= scrollHeight - 50) {
        loadMoreProducts();
    }
};

const openScanProduk = () => {
    scanType.value = "product";
    showScanner.value = true;
};

const openScanMember = () => {
    scanType.value = "member";
    showScanner.value = true;
};

const handleResScan = (res) => {
    showScanner.value = false;
    alert("scan berhasil :" + res);
    if (scanType.value == "product") {
        res = queryProduk(res);
        prepareModalData(res);
    } else if (scanType.value == "member") {
        res = queryMember(res);
    }
};

const addToCart = (retry = false) => {
    addItem(currentItem);
    if (retry) {
        openScanProduk();
    }
};

const confirmTransaction = (shouldPrint) => {
    if (form.items.length === 0) return toast.error("Keranjang kosong!");
    if (hasInvalidQty.value) return toast.error("Qty tidak valid!");
    if (form.payment_method === "cash" && !isPaymentSufficient.value)
        return toast.error("Uang kurang!");
    submitTransaction(shouldPrint, {
        onSuccess: (page) => {
            // Tutup modal & drawer HP setelah sukses reset data
            showConfirmModal.value = false;
            showMobileCart.value = false;
            const printUrl = page.props.flash?.print_url;
            if (shouldPrint && printUrl) {
                setTimeout(() => {
                    window.open(printUrl, "_blank", "width=300,height=600");
                }, 50);
            } else if (shouldPrint && !printUrl) {
                alert("Gagal mendapatkan URL Print dari server.");
            }
        },
    });
};
// Fungsi untuk cek Qty item di cart berdasarkan ID
const getCartQty = (productId) => {
    const item = form.items.find((i) => i.product_id === productId);
    return item ? item.quantity : 0;
};
</script>

<template>
    <Head title="Kasir POS" />
    <ConfirmSubmit
        :showConfirmModal="showConfirmModal"
        :changeAmount="changeAmount"
        @close="showConfirmModal = false"
        @confirmTransaction="confirmTransaction"
    />
    <!-- <ScannerBox
        :showScanner="showScanner"
        :activeScannerType="activeScannerType"
        @close="showScanner = false"
        @stopScanner="stopScanner"
    /> -->

    <!-- <ScannerModeModal
        :show="showScannerModal"
        @close="showScannerModal = false"
        @mode-selected="startScanner"
    /> -->
    <BarcodeScanner
        v-if="showScanner"
        @result="handleResScan"
        @close="showScanner = false"
    />
    <div
        class="flex flex-col lg:flex-row h-[100dvh] w-full bg-gray-100 dark:bg-gray-900 overflow-hidden font-sans transition-colors duration-300"
    >
        <div class="relative flex flex-col flex-1 h-full overflow-hidden">
            <div
                class="z-10 flex items-center gap-3 px-4 py-3 bg-white border-b shadow-sm shrink-0 dark:bg-gray-800 dark:border-gray-700"
            >
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-lg bg-lime-50 dark:bg-gray-700 text-lime-700 dark:text-lime-400"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        ></path>
                    </svg>
                </Link>
                <div class="relative flex-1">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari Nama / Kode..."
                        class="w-full pl-9 pr-10 py-2.5 bg-gray-100 dark:bg-gray-900 border-none rounded-xl focus:ring-2 focus:ring-lime-500 text-sm dark:text-white transition-all shadow-inner"
                    />
                    <span class="absolute text-gray-400 left-3 top-3">🔍</span>
                    <div
                        v-if="isFetchingData"
                        class="absolute right-11 top-3.5 flex items-center gap-2"
                    >
                        <span class="text-[10px] text-gray-400 italic"
                            >Syncing...</span
                        >
                        <span class="relative flex w-2 h-2">
                            <span
                                class="absolute inline-flex w-full h-full rounded-full opacity-75 animate-ping bg-lime-400"
                            ></span>
                            <span
                                class="relative inline-flex w-2 h-2 rounded-full bg-lime-500"
                            ></span>
                        </span>
                    </div>
                    <button
                        @click="openScanProduk()"
                        class="absolute right-1.5 top-1.5 p-1 bg-white dark:bg-gray-700 rounded-lg shadow-sm text-gray-600 dark:text-gray-200 hover:text-lime-600 hover:bg-lime-50 transition border border-gray-200 dark:border-gray-600"
                        title="Scan Produk"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                            ></path>
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                            ></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                class="px-4 py-2 overflow-x-auto bg-white border-b shadow-sm shrink-0 dark:bg-gray-800 dark:border-gray-700 whitespace-nowrap scrollbar-hide"
            >
                <button
                    @click="selectedCategory = 'all'"
                    :class="
                        selectedCategory === 'all'
                            ? 'bg-lime-500 text-white shadow-lime-500/40'
                            : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600'
                    "
                    class="px-5 py-2 mr-2 text-xs font-bold transition-all rounded-full active:scale-95"
                >
                    Semua
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.id"
                    :class="
                        selectedCategory === cat.id
                            ? 'bg-lime-500 text-white shadow-lime-500/40'
                            : 'bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600'
                    "
                    class="px-5 py-2 mr-2 text-xs font-bold transition-all rounded-full active:scale-95"
                >
                    {{ cat.name }}
                </button>
            </div>

            <div
                class="h-[calc(100vh-220px)] flex-1 p-4 overflow-y-auto bg-gray-100 custom-scroll pb-28 lg:pb-4 dark:bg-gray-900"
                @scroll="handleScroll"
            >
                <div
                    class="grid grid-cols-2 gap-3 pb-20 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addItem(product)"
                        class="relative overflow-hidden transition-all bg-white border border-gray-200 shadow-sm cursor-pointer group dark:bg-gray-800 rounded-xl dark:border-gray-700 hover:shadow-md hover:border-lime-500 active:scale-95"
                        :class="
                            getCartQty(product.id) > 0
                                ? 'border-lime-500 shadow-md bg-lime-50 ring-1 ring-lime-500'
                                : 'border-gray-200 shadow-sm bg-white hover:border-lime-300'
                        "
                    >
                        <div
                            v-if="getCartQty(product.id) > 0"
                            class="absolute z-10 flex items-center justify-center w-8 h-8 text-xs font-bold text-white border-2 border-white rounded-full shadow-lg bottom-1 right-1 bg-lime-600 animate-bounce-short"
                        >
                            {{ getCartQty(product.id) }}x
                        </div>
                        <div
                            class="relative w-full overflow-hidden bg-gray-100 aspect-square dark:bg-gray-700"
                        >
                            <img
                                v-if="product.image_path"
                                :src="product.image_url"
                                loading="lazy"
                                decoding="async"
                                alt=""
                                class="absolute inset-0 z-10 object-cover w-full h-full transition-opacity duration-500 opacity-0"
                                onload="this.classList.remove('opacity-0')"
                                onerror="this.style.display='none'"
                            />

                            <div
                                class="flex flex-col items-center justify-center w-full h-full text-gray-500 bg-gray-300 dark:text-gray-700 dark:bg-gray-400"
                            >
                                <svg
                                    class="w-10 h-10"
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
                                    class="text-[9px] font-bold mt-1 opacity-50"
                                    >NO IMG</span
                                >
                            </div>

                            <div
                                v-if="product.unit"
                                class="absolute z-10 top-2 right-2"
                            >
                                <span
                                    class="bg-gray-900/70 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm"
                                >
                                    {{ product.unit.name }}
                                </span>
                            </div>

                            <div
                                v-if="product.stock <= 5"
                                class="absolute z-10 top-2 left-2"
                            >
                                <span
                                    class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-lg shadow-sm animate-pulse"
                                >
                                    Sisa {{ parseFloat(product.stock) }}
                                </span>
                            </div>

                            <div
                                class="absolute inset-0 z-20 flex items-center justify-center transition-opacity opacity-0 bg-lime-500/20 group-hover:opacity-100"
                            >
                                <div
                                    class="p-2 text-white transition-transform duration-300 scale-0 rounded-full shadow-lg bg-lime-500 group-hover:scale-110"
                                >
                                    <svg
                                        class="w-6 h-6"
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
                                </div>
                            </div>
                        </div>

                        <div class="p-3 flex flex-col h-[85px] justify-between">
                            <h3
                                class="text-xs font-bold leading-snug text-gray-800 sm:text-sm dark:text-gray-100 line-clamp-2"
                            >
                                {{ product.name }}
                            </h3>

                            <div class="flex items-end justify-between mt-1">
                                <div class="flex flex-col">
                                    <span
                                        class="text-[13px] sm:text-[15px] font-black text-lime-600 dark:text-lime-400 leading-none"
                                    >
                                        {{
                                            rp(
                                                product.selling_price ||
                                                    product.price
                                            )
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="text-gray-300 transition-colors group-hover:text-lime-500"
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
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-if="isFetchingData && filteredProducts.length === 0"
                    class="py-10 text-center"
                >
                    <div
                        class="w-8 h-8 mx-auto mb-2 border-b-2 rounded-full animate-spin border-lime-500"
                    ></div>
                    <span class="text-xs text-gray-400"
                        >Memuat database produk...</span
                    >
                </div>
                <div
                    v-else-if="filteredProducts.length === 0"
                    class="flex flex-col items-center justify-center px-4 py-16 text-center animate-fade-in"
                >
                    <div class="relative mb-6">
                        <div
                            class="flex items-center justify-center w-24 h-24 rounded-full shadow-inner bg-gray-50 dark:bg-gray-800"
                        >
                            <svg
                                class="w-12 h-12 text-gray-300 dark:text-gray-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>

                        <div
                            class="absolute -top-2 -right-2 bg-white dark:bg-gray-700 rounded-full p-1.5 shadow-md border border-gray-100 dark:border-gray-600"
                        >
                            <div
                                class="p-1 rounded-full bg-lime-100 dark:bg-lime-900/50"
                            >
                                <svg
                                    class="w-5 h-5 text-lime-500 dark:text-lime-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <h3
                        class="mb-2 text-lg font-bold text-gray-900 dark:text-white"
                    >
                        Produk tidak ditemukan
                    </h3>
                    <p
                        class="max-w-xs mx-auto mb-6 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
                    >
                        Kami tidak dapat menemukan produk dengan kata kunci atau
                        kategori tersebut.
                    </p>

                    <button
                        @click="
                            search = '';
                            selectedCategory = 'all';
                        "
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-700 hover:border-lime-500 hover:text-lime-600 dark:hover:border-lime-500 dark:hover:text-lime-400 shadow-sm hover:shadow-md"
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
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Reset Pencarian
                    </button>
                </div>

                <div
                    v-if="
                        filteredProducts.length > 0 &&
                        filteredProducts.length % 20 === 0
                    "
                    class="py-4 text-center text-[10px] text-gray-400"
                >
                    Scroll untuk memuat lebih banyak...
                </div>
            </div>

            <div
                v-if="form.items.length > 0"
                class="absolute z-30 lg:hidden bottom-4 left-4 right-4"
            >
                <button
                    @click="showMobileCart = true"
                    class="w-full bg-gray-900 dark:bg-lime-600 text-white p-3.5 rounded-2xl shadow-xl shadow-gray-900/20 flex justify-between items-center animate-bounce-subtle"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex items-center justify-center text-xs font-bold text-white rounded-full shadow-sm bg-lime-500 dark:bg-white dark:text-lime-700 w-7 h-7"
                            >{{
                                form.items.reduce(
                                    (a, b) => a + parseFloat(b.quantity),
                                    0
                                )
                            }}</span
                        >
                        <div
                            class="flex flex-col items-start leading-none gap-0.5"
                        >
                            <span
                                class="text-[9px] text-gray-400 dark:text-lime-100 uppercase font-bold tracking-wider"
                                >Total Belanja</span
                            >
                            <span class="text-lg font-bold">{{
                                rp(grandTotal)
                            }}</span>
                        </div>
                    </div>
                    <span
                        class="flex items-center gap-1 px-4 py-2 text-xs font-bold bg-gray-700 dark:bg-lime-800/30 rounded-xl"
                    >
                        Bayar
                        <svg
                            class="w-3 h-3"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            ></path>
                        </svg>
                    </span>
                </button>
            </div>
        </div>
        <Cart
            :showMobileCart="showMobileCart"
            :reprops="pos"
            @openScanMember="openScanMember"
            @showBayar="showConfirmModal = true"
            @showDesktop="showMobileCart = false"
        />
    </div>
    <BottomSheet
        :show="showQtyModal"
        @close="showQtyModal = false"
        title="Tambah Pesanan"
    >
        <div class="space-y-6">
            <div
                class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800"
            >
                <div
                    class="flex items-center justify-center text-lg font-bold text-gray-400 bg-gray-100 border border-gray-200 w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-700 shrink-0"
                >
                    {{ currentItem.name?.substring(0, 2).toUpperCase() }}
                </div>

                <div class="flex-1 min-w-0">
                    <h3
                        class="text-lg font-bold leading-tight text-gray-800 truncate dark:text-white"
                    >
                        {{ currentItem.name }}
                    </h3>
                    <div class="flex items-center justify-between mt-1">
                        <p
                            class="text-xs text-gray-500 font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded"
                        >
                            {{ currentItem.code }}
                        </p>
                        <p
                            class="text-xs font-medium"
                            :class="
                                3 > currentItem.stock
                                    ? 'text-red-500'
                                    : 'text-gray-400'
                            "
                        >
                            Stok: {{ currentItem.stock }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between">
                    <button
                        @click="
                            currentItem.quantity > 1
                                ? currentItem.quantity--
                                : null
                        "
                        class="flex items-center justify-center text-gray-500 transition bg-white border border-gray-200 shadow-sm w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-600 active:scale-95 hover:text-red-500 disabled:opacity-50"
                        :disabled="currentItem.quantity <= 1"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
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

                    <div class="flex-1 px-2 text-center">
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5"
                            >Jumlah Beli</label
                        >
                        <input
                            v-model.number="currentItem.quantity"
                            type="number"
                            class="w-full p-0 text-3xl font-black text-center text-gray-800 bg-transparent border-none dark:text-white focus:ring-0"
                        />
                    </div>

                    <button
                        @click="currentItem.quantity++"
                        class="flex items-center justify-center text-white transition shadow-lg w-14 h-14 bg-lime-500 rounded-xl shadow-lime-500/30 active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                class="flex items-center justify-between p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800 rounded-xl"
            >
                <div>
                    <p class="text-xs text-gray-400">Harga Satuan</p>
                    <p class="font-bold text-gray-600 dark:text-gray-300">
                        {{ rp(currentItem.price) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Subtotal
                    </p>
                    <p
                        class="text-2xl font-black text-gray-900 dark:text-white"
                    >
                        {{ rp(currentItem.price * currentItem.quantity) }}
                    </p>
                </div>
            </div>

            <div
                v-if="currentItem.quantity > currentItem.stock"
                class="px-3 py-2 text-xs font-bold text-center text-red-600 border border-red-100 rounded-lg bg-red-50"
            >
                ⚠️ Jumlah melebihi sisa stok ({{ currentItem.stock }})
            </div>
        </div>

        <template #footer>
            <div class="flex gap-3 h-14">
                <button
                    @click="addToCart(false)"
                    class="flex-1 font-bold transition border text-lime-700 bg-lime-100 border-lime-200 rounded-xl active:bg-lime-200 dark:bg-lime-800 dark:text-lime-300 dark:border-lime-700"
                >
                    Masuk Keranjang
                </button>

                <button
                    @click="addToCart(true)"
                    class="flex-[1.3] bg-gray-900 text-white font-bold rounded-xl shadow-xl flex items-center justify-center gap-2 active:scale-[0.98] transition hover:bg-gray-800 dark:bg-white dark:text-gray-900"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                        />
                    </svg>
                    <span>Scan Lagi</span>
                </button>
            </div>
        </template>
    </BottomSheet>
</template>

<style>
.animate-fade-in-down {
    animation: fadeInDown 0.3s ease-out;
}
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.custom-scroll::-webkit-scrollbar {
    width: 3px;
}
.custom-scroll::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}
.dark .custom-scroll::-webkit-scrollbar-thumb {
    background: #4b5563;
}

/* Animasi Halus */
.animate-bounce-subtle {
    animation: bounce-subtle 2s infinite;
}
@keyframes bounce-subtle {
    0%,
    100% {
        transform: translateY(-3%);
    }
    50% {
        transform: translateY(0);
    }
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}
.animate-bounce-short {
    animation: bounceShort 0.3s ease-in-out;
}
@keyframes bounceShort {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}
</style>
