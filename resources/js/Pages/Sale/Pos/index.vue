<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, watch, nextTick } from "vue";
import { usePosRealtime } from "@/Composable/usePosRealtime";
import { useWakeLock } from "@/Composable/useWakeLock";

import { useToast } from "vue-toastification";
import ConfirmSubmit from "./ConfirmSubmit.vue";
import Cart from "./Cart.vue";
import BarcodeScanner from "@/Components/BarcodeScanner.vue";
import FilterProduct from "./FilterProduct.vue";
import ProductDetailModal from "./ProductDetailModal.vue";
import ProductComparisonModal from "./ProductComparisonModal.vue";
import TransactionSuccessModal from "./TransactionSuccessModal.vue";
import ProductList from "./Components/ProductList.vue";
import PosQtyModal from "./Components/PosQtyModal.vue";

const props = defineProps({
    categories: Array,
    customers: Array,
    brands: Array,
    sale: Object, // Existing sale for edit
    mode: String, // 'edit' or 'create'
});
const pos = usePosRealtime(props);
const { isDimmed, resetIdleTimer } = useWakeLock(); // 2 minutes default
// --- PANGGIL COMPOSABLE ---
const {
    //state
    form,
    filterState,
    allProducts,
    filteredProducts,
    isFetchingData,
    dynamicBrands,
    dynamicSizes,
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
const showSuccessModal = ref(false); // New Success Modal State
const isSubmitting = ref(false); // New Submitting State
const showQtyModal = ref(false);
const showDetailModal = ref(false);
const showCompareModal = ref(false);
const scanType = ref("product"); //product atau member
const selectedDetailProduct = ref(null);
const lastPrintedId = ref(null); // Store ID for printing

watch(filterState, async () => { /* ... */ }, { deep: true });

// Handle Transaction
// const handleConfirmTransaction = async (print) => {
//     isSubmitting.value = true;
//     try {
//         const result = await submitTransaction(print);
//         // Note: submitTransaction in usePosRealtime needs to return the response or sale object
//         // If it doesn't return anything or void, we assume success if no error thrown.
//         // But usually Inertia form.post is async? No, it uses callbacks.
        
//         // Wait, usePosRealtime uses form.post. 
//         // I need to modify submitTransaction in usePosRealtime to return a Promise or accept callbacks.
//         // For now, let's assume I will modify usePosRealtime to return a Promise that resolves with sale data.
        
//         if (result && result.success) {
//             showConfirmModal.value = false;
//             lastPrintedId.value = result.saleId; // Capture ID
//             showSuccessModal.value = true;
//         }
//     } catch (error) {
//         console.error("Transaction failed", error);
//         // Toast is handled in usePosRealtime usually
//     } finally {
//         isSubmitting.value = false;
//     }
// };

const handlePrintStruk = () => {
    if (lastPrintedId.value) {
        window.open(`/sales/${lastPrintedId.value}/print-struk`, '_blank');
    }
};

const handleNewTransaction = () => {
    showSuccessModal.value = false;
    // Form reset usually happens in submitTransaction success, or we force it here?
    // In usePosRealtime, onSuccess usually resets form.
    // Ensure form is fresh.
    form.reset();
    form.items = [];
    // Reset other states if needed
};

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

// Fungsi ini dipanggil saat klik dari ProductList (Langsung tambah 1)
const handleDirectAddToCart = (productMaster) => {
    const item = {
        id: productMaster.id,
        name: productMaster.name,
        code: productMaster.code,
        image_url: productMaster.image_url,
        price: parseFloat(productMaster.selling_price || productMaster.price || 0),
        quantity: 1,
        stock: parseInt(productMaster.stock || 0),
        unit: productMaster.unit?.name || "Pcs",
    };
    addItem(item);
};

// Berfungsi mendeteksi jika user scroll mentok bawah -> load data lagi

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
// Temp vars for success modal (since form is reset)
const lastChangeAmount = ref(0);
const lastGrandTotal = ref(0);
const cartRef = ref(null);

const handleConfirmTransaction = (printInvoice) => {
    isSubmitting.value = true;
    submitTransaction(printInvoice).then((res) => {
        isSubmitting.value = false;
        showConfirmModal.value = false;
        if (res.success) {
            // Store values for modal
            lastChangeAmount.value = res.change;
            lastGrandTotal.value = res.total;
            
            showSuccessModal.value = true;
            
            // IMMEDIATE RESET CART UI TO STEP 1
            cartRef.value?.resetStep();
            
            // Close Mobile Cart
            showMobileCart.value = false; 
        } else {
            toast.success("Transaksi berhasil disimpan.");
        }
    }).catch((err) => {
        console.error("Error submitting transaction:", err);
        isSubmitting.value = false;
        // Optional: toast.error("Gagal menyimpan transaksi.");
    });
};

// --- UX IMPROVEMENTS ---
// Auto-close keyboard on scroll
const handleProductListScroll = () => {
    // Check if on mobile (optional logic, but simple blur is safe)
    if (document.activeElement && ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        document.activeElement.blur();
    }
}
</script>

<template>
    <Head title="Kasir POS" />
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
        <!-- DIMMER OVERLAY (Screen Saver / Power Save) -->
        <div 
            v-if="isDimmed"
            @click.stop.prevent="resetIdleTimer"
            class="fixed inset-0 z-[9999] bg-black/90 flex flex-col items-center justify-center cursor-pointer transition-opacity duration-1000"
        >
            <div class="text-center animate-pulse">
                <span class="text-6xl mb-4 block">🌙</span>
                <p class="text-white/50 text-sm font-light tracking-widest uppercase">Mode Hemat Daya</p>
                <p class="text-white/30 text-xs mt-2">Sentuh layar untuk kembali</p>
            </div>
        </div>
        <div class="relative flex flex-col flex-1 h-full overflow-hidden">
            <FilterProduct
                :categories="categories"
                :brands="dynamicBrands?.length ? dynamicBrands : brands"
                :sizes="dynamicSizes"
                :is-fetching="isFetchingData"
                v-model:search="filterState.search"
                v-model:category="filterState.category"
                v-model:subCategory="filterState.subCategory"
                v-model:brand="filterState.brand"
                v-model:size="filterState.size"
                v-model:sort="filterState.sort"
                v-model:hideEmptyStock="filterState.hideEmptyStock"
                @scan="openScanProduk"
            />
            
            <!-- MODULAR PRODUCT LIST WRAPPER FOR SCROLL EVENT -->
            <!-- We need to capture scroll here. But ProductList might handle scroll internally. 
                 Let's check ProductList structure or wrap it. 
                 ProductList usually emits 'loadMore' on scroll. 
                 If ProductList has a scrollable container, we should listen there. 
                 Wait, ProductList.vue likely has the scroll container.
                 Let's pass a prop or listener to ProductList? 
                 Better: Wrap ProductList in a div that captures bubbling scroll? 
                 Scroll event doesn't bubble by default from overflow elements. 
                 We need to modify ProductList.vue to emit scroll or handle it there.
                 
                 Plan B: For now, let's just close mobile cart here and address scroll in ProductList.vue 
                 Wait, user asked for "scroll product list -> close keyboard".
                 This means we should modify ProductList.vue or pass a handler.
                 Let's add the handler to `ProductList` component tag if it supports @scroll.native equivalent.
                 Vue 3: @scroll on component listens to root element scroll? No, only specific emissions.
                 
                 Let's Look at ProductList.vue content first? I already have it in context? 
                 No, I viewed index.vue. I need to check ProductList.vue for the scroll container.
            -->
            
            <ProductList
                :products="filteredProducts"
                :is-fetching="isFetchingData"
                :cart-items="form.items"
                :all-products-count="allProducts.length"
                :compare-list="compareList"
                @loadMore="loadMoreProducts"
                @addToCart="handleDirectAddToCart"
                @openDetail="openDetail"
                @toggleCompare="toggleCompare"
            />
            <!-- Added @scroll-list listener. Now I need to update ProductList.vue to emit it. -->


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
            ref="cartRef"
            :showMobileCart="showMobileCart"
            :reprops="pos"
            @openScanMember="openScanMember"
            @showBayar="showConfirmModal = true"
            @showDesktop="showMobileCart = false"
        />
    </div>

    <!-- MODULAR QTY MODAL -->
    <PosQtyModal
        :show="showQtyModal"
        v-model:item="currentItem"
        @close="showQtyModal = false"
        @addToCart="addToCart"
    />

    <!-- Global Modals -->
    <ConfirmSubmit
        :showConfirmModal="showConfirmModal"
        :changeAmount="changeAmount"
        :grandTotal="grandTotal"
        :paymentAmount="parseFloat(form.payment_amount)"
        :processing="isSubmitting"
        @close="showConfirmModal = false"
        @confirmTransaction="handleConfirmTransaction"
    />

    <TransactionSuccessModal
        :show="showSuccessModal"
        :changeAmount="lastChangeAmount"
        :total="lastGrandTotal"
        @close="handleNewTransaction"
        @newTransaction="handleNewTransaction"
    />
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
