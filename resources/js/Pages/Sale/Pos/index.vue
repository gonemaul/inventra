<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, watch, nextTick } from "vue";
import { usePosRealtime } from "@/Composable/usePosRealtime";

import { useToast } from "vue-toastification";
import BottomSheet from "@/Components/BottomSheet.vue";
import ConfirmSubmit from "./ConfirmSubmit.vue";
import Cart from "./Cart.vue";
import BarcodeScanner from "@/Components/BarcodeScanner.vue";
import FilterProduct from "./FilterProduct.vue";
import ProductDetailModal from "./ProductDetailModal.vue";
import ProductComparisonModal from "./ProductComparisonModal.vue";

const props = defineProps({
    categories: Array,
    customers: Array,
    brands: Array,
    sale: Object, // Existing sale for edit
    mode: String, // 'edit' or 'create'
});
const pos = usePosRealtime(props);
// --- PANGGIL COMPOSABLE ---
const {
    //state
    form,
    filterState,
    allProducts,
    filteredProducts,
    isFetchingData,
    // computed
    grandTotal,
    changeAmount,
    isPaymentSufficient,
    hasInvalidQty,
    // actions
    // actions
    addItem,
    loadMoreProducts, // Fungsi untuk infinite scroll

    queryProduk,
    queryMember,
    // Utils
    submitTransaction,
    rp,
    // compare actions
    toggleCompare,
    clearCompare,
    isInCompare,
    compareList,
} = pos;

// --- STATE LOKAL UI (Client Side Search) ---
const toast = useToast();
const showMobileCart = ref(false);
const showScanner = ref(false);
const showConfirmModal = ref(false);
const showQtyModal = ref(false);
const showDetailModal = ref(false);
const showCompareModal = ref(false);
const scanType = ref("product"); //product atau member
const productGridRef = ref(null);
const selectedDetailProduct = ref(null);

watch(
    filterState,
    async () => {
        await nextTick();
        if (productGridRef.value) {
            productGridRef.value.scrollTo({
                top: 0,
                behavior: "smooth", // Opsional: Berikan efek gerak halus
            });
        }
    },
    { deep: true } // Wajib: agar mendeteksi perubahan properti dalam object (search/cat/sort)
);
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

const openDetail = (product) => {
    selectedDetailProduct.value = product;
    showDetailModal.value = true;
};


const openScanProduk = () => {
    scanType.value = "product";
    showScanner.value = true;
};

const openScanMember = () => {
    scanType.value = "member";
    showScanner.value = true;
};

const handleResScan = async (res) => {
    showScanner.value = false;
    alert("scan berhasil " + scanType.value + " | " + res);
    if (scanType.value == "product") {
        try {
            const productData = await queryProduk(res);
            if (productData) {
                // Update res dengan data object produk yang baru didapat
                res = productData;

                // Baru jalankan prepare modal
                prepareModalData(res);
            } else {
                alert("Produk tidak ditemukan di database.");
            }
        } catch (error) {
            console.error("Terjadi kesalahan saat query produk:", error);
            alert("Gagal mengambil data produk.");
        }
    } else if (scanType.value == "member") {
        res = queryMember(res);
    }
};

const addToCart = (retry = false) => {
    addItem(currentItem.value);
    showQtyModal.value = false;
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
            // Cek apakah ada error dari server (via flash message)
            if (page.props.flash.error) {
                toast.error(page.props.flash.error);
                return;
            }

            // Tutup modal & drawer HP setelah sukses reset data
            showConfirmModal.value = false;
            showMobileCart.value = false;
            const printUrl = page.props.flash?.print_url;
            if (shouldPrint && printUrl) {
                setTimeout(() => {
                    window.open(printUrl, "_blank", "width=300,height=600");
                }, 50);
            } else if (shouldPrint && !printUrl) {
                toast.error("Gagal mendapatkan URL Print dari server.");
            } else {
                toast.success("Transaksi berhasil disimpan.");
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
    <ProductDetailModal
        :show="showDetailModal"
        :product="selectedDetailProduct"
        @close="showDetailModal = false"
        @addToCart="(p) => { prepareModalData(p); }"
    />
    <ProductComparisonModal
        :show="showCompareModal"
        :products="compareList"
        @close="showCompareModal = false"
        @remove="toggleCompare"
        @addToCart="(p) => { prepareModalData(p); }"
    />
    <BarcodeScanner
        v-if="showScanner"
        @result="handleResScan"
        @close="showScanner = false"
    />
    <div
        class="flex flex-col lg:flex-row h-[100dvh] w-full bg-gray-100 dark:bg-gray-900 overflow-hidden font-sans transition-colors duration-300"
    >
        <div class="relative flex flex-col flex-1 h-full overflow-hidden">
            <FilterProduct
                :categories="categories"
                :brands="brands"
                :is-fetching="isFetchingData"
                v-model:search="filterState.search"
                v-model:category="filterState.category"
                v-model:sub-category="filterState.subCategory"
                v-model:brand="filterState.brand"
                v-model:sort="filterState.sort"
                v-model:hideEmptyStock="filterState.hideEmptyStock"
                @scan="openScanProduk"
            />
            <div
                ref="productGridRef"
                class="h-[calc(100vh-220px)] flex-1 p-4 overflow-y-auto bg-gray-100 custom-scroll scroll-smooth pb-28 lg:pb-4 dark:bg-gray-900"
                @scroll="handleScroll"
            >
                <div
                    class="grid grid-cols-2 gap-3 pb-10 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        class="relative flex flex-col justify-between overflow-hidden transition-all duration-200 border shadow-sm group rounded-xl bg-white dark:bg-gray-800"
                        :class="[
                            product.stock == 0 ? 'opacity-75 grayscale-[0.5]' : '',
                            getCartQty(product.id) > 0
                                ? 'border-lime-500 ring-1 ring-lime-500 bg-lime-50/30 dark:bg-lime-900/10'
                                : 'border-gray-200 dark:border-gray-700 hover:shadow-md',
                        ]"
                    >
                        <!-- 1. Main Click Area (Image & Info) triggers Add to Cart -->
                        <div 
                            class="flex-1 flex flex-col cursor-pointer active:scale-[0.98] transition-transform"
                            @click="addItem(product)"
                        >
                            <div
                                class="relative w-full overflow-hidden bg-gray-100 border-b border-gray-100 aspect-square dark:border-gray-700 dark:bg-gray-700"
                            >
                                <!-- Badges Unit/Size (Prominent) -->
                                <div
                                    class="absolute z-20 flex flex-col items-start gap-1 top-2 left-2"
                                >
                                    <span
                                        v-if="product.unit"
                                        class="text-[10px] font-bold px-2 py-0.5 rounded shadow-sm backdrop-blur text-gray-700 bg-white/90 dark:text-gray-200 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-600"
                                    >
                                        {{ product.unit.name }}
                                    </span>
                                    <span
                                        v-if="product.size"
                                        class="text-[10px] font-bold text-white px-2 py-0.5 rounded shadow-sm backdrop-blur bg-gray-800/90 dark:bg-gray-600/90 border border-gray-700"
                                    >
                                        {{ product.size.name }}
                                    </span>
                                </div>
    
                                <img
                                    v-if="product.image_url"
                                    :src="product.image_url"
                                    loading="lazy"
                                    class="absolute inset-0 z-10 object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
                                    alt=""
                                    onerror="this.style.display='none'"
                                />
                                
                                <div
                                    v-if="!product.image_url"
                                    class="flex items-center justify-center w-full h-full text-gray-400 dark:text-gray-600"
                                >
                                    <svg
                                        class="w-10 h-10 opacity-50"
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
                                </div>
    
                                <div
                                    v-if="product.stock <= 0"
                                    class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/60 backdrop-blur-[1px]"
                                >
                                    <span
                                        class="px-2 py-1 text-xs font-bold text-white transform border-2 border-white rounded -rotate-12"
                                        >KOSONG</span
                                    >
                                </div>
                            </div>
    
                            <div class="flex flex-col flex-1 p-3">
                                <div class="mb-1 flex justify-between items-start">
                                    <span
                                        class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide"
                                    >
                                        {{ product.brand?.name || "No Brand" }}
                                    </span>
                                </div>
    
                                <h3
                                    class="text-xs font-bold leading-snug text-gray-800 dark:text-gray-100 line-clamp-2 min-h-[2.5em] mb-2"
                                    :title="product.name"
                                >
                                    {{ product.name }}
                                </h3>
    
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-1.5">
                                        <div
                                            class="w-1.5 h-1.5 rounded-full"
                                            :class="
                                                product.stock <= 5
                                                    ? 'bg-red-500 animate-pulse'
                                                    : 'bg-green-500 dark:bg-green-400'
                                            "
                                        ></div>
                                        <span
                                            class="text-[10px] font-medium"
                                            :class="
                                                product.stock <= 5
                                                    ? 'text-red-500 dark:text-red-400'
                                                    : 'text-gray-500 dark:text-gray-400'
                                            "
                                        >
                                            {{
                                                product.stock <= 5
                                                    ? `Sisa ${parseFloat(
                                                          product.stock
                                                      )}`
                                                    : `Stok ${parseFloat(
                                                          product.stock
                                                      )}`
                                            }}
                                        </span>
                                    </div>
                                    <span v-if="product.total_sold > 0" class="text-[9px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">
                                        {{ product.total_sold }} Terjual
                                    </span>
                                </div>
    
                                <div
                                    class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100 dark:border-gray-700"
                                >
                                    <span
                                        class="text-sm font-black text-lime-600 dark:text-lime-400"
                                    >
                                        {{
                                            rp(
                                                product.selling_price ||
                                                    product.price
                                            )
                                        }}
                                    </span>
    
                                    <div
                                        v-if="getCartQty(product.id) > 0"
                                        class="flex items-center justify-center text-xs font-bold text-white bg-orange-500 rounded-full shadow-md w-7 h-7 animate-bounce-short"
                                    >
                                        {{ getCartQty(product.id) }}
                                    </div>
    
                                    <div
                                        v-else
                                        class="p-1.5 rounded-lg text-lime-600 bg-lime-50 dark:text-lime-400 dark:bg-lime-500/10"
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
                                                d="M12 4v16m8-8H4"
                                            ></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Separate Action Buttons (Always Visible) -->
                        <div class="grid grid-cols-2 border-t border-gray-100 dark:border-gray-700 divide-x divide-gray-100 dark:divide-gray-700 bg-gray-50 dark:bg-gray-800/50">
                            <button
                                @click.stop="openDetail(product)"
                                class="py-2.5 flex items-center justify-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                Detail
                            </button>
                            <button
                                @click.stop="toggleCompare(product)"
                                class="py-2.5 flex items-center justify-center gap-1.5 text-xs font-semibold transition"
                                :class="isInCompare(product.id) 
                                    ? 'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400' 
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                {{ isInCompare(product.id) ? 'Batal' : 'Bandingkan' }}
                            </button>
                        </div>
                    </div>
                </div>
                <div
                    v-if="isFetchingData"
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
                        allProducts.length > filteredProducts.length
                    "
                    class="py-4 text-center text-[10px] text-gray-400"
                >
                    Scroll untuk memuat lebih banyak...
                </div>
            </div>


            <!-- Floating Compare Bar (Bottom Right) -->
            <div
                v-if="compareList?.length > 0"
                class="absolute z-40 bottom-24 lg:bottom-8 right-4 flex items-center bg-gray-900 dark:bg-gray-800 text-white pl-4 pr-1 py-1.5 gap-4 rounded-full shadow-xl animate-bounce-subtle border border-gray-700"
            >
                <div class="text-xs font-bold whitespace-nowrap">
                    {{ compareList.length }} Banding
                </div>
                <div class="flex items-center gap-1">
                     <button
                        @click="showCompareModal = true"
                        class="px-3 py-1.5 bg-lime-500 hover:bg-lime-400 text-black text-xs font-bold rounded-full transition"
                    >
                        Buka
                    </button>
                    <button
                        @click="clearCompare"
                        class="p-1.5 hover:bg-gray-700 rounded-full text-gray-400 hover:text-white transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
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
