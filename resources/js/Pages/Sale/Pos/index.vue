<script setup>
import { Head } from "@inertiajs/vue3";
import { ref, watch, onMounted } from "vue";
import { usePosState } from "@/Composables/POS/usePosState";
import { usePosDraft } from "@/Composables/POS/usePosDraft";
import { storeToRefs } from "pinia";
import { useWakeLock } from "@/Composable/useWakeLock";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";

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
    sale: Object,
    mode: String,
});

const posState = usePosState();
const posDraft = usePosDraft();

onMounted(() => {
    posDraft.initDrafts();
    posDraft.startSync();
    posState.loadProduct();
    posState.fetchServices();
});

const { isDimmed, resetIdleTimer } = useWakeLock();

const {
    drafts,
    activeTabIndex,
    activeDraft,
    form,
    isFetchingData,
    filteredProducts,
    compareList,
    grandTotal,
    changeAmount,
} = storeToRefs(posState);

const {
    addItem,
    loadMoreProducts,
    queryProduk,
    queryMember,
    submitTransaction,
    rp,
    toggleCompare,
    clearCompare,
    isInCompare,
    allProducts,
    addNewTab,
    removeTab,
    switchTab,
    switchPosMode,
    resetCartStep,
} = posState;

// --- LOCAL UI STATE ---
const toast = usePremiumAlert();
const showMobileCart = ref(false);
const showScanner = ref(false);
const showConfirmModal = ref(false);
const showSuccessModal = ref(false);
const isSubmitting = ref(false);
const showQtyModal = ref(false);
const showDetailModal = ref(false);
const showCompareModal = ref(false);
const scanType = ref("product");
const selectedDetailProduct = ref(null);
const lastPrintedId = ref(null);



const handlePrintStruk = () => {
    if (lastPrintedId.value) {
        window.open(`/sales/${lastPrintedId.value}/print-struk`, '_blank');
    }
};

const handleNewTransaction = () => {
    showSuccessModal.value = false;
};

const currentItem = ref({
    id: null, name: "", code: "", price: 0, quantity: 1, stock: 0, unit: "Pcs", image: null,
});

const prepareModalData = (productMaster) => {
    currentItem.value = {
        id: productMaster.id,
        name: productMaster.name,
        code: productMaster.code,
        image_url: productMaster.image_url,
        price: parseFloat(productMaster.selling_price || productMaster.price || 0),
        quantity: 1,
        stock: parseInt(productMaster.stock || 0),
        unit: productMaster.unit?.name || "Pcs",
    };
    showQtyModal.value = true;
};

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

const openDetail = (product) => {
    selectedDetailProduct.value = product;
    showDetailModal.value = true;
};

const openScanProduk = () => { scanType.value = "product"; showScanner.value = true; };
const openScanMember = () => { scanType.value = "member"; showScanner.value = true; };

const handleResScan = async (res) => {
    showScanner.value = false;
    if (scanType.value == "product") {
        try {
            const productData = await queryProduk(res);
            if (productData) prepareModalData(productData);
            else alert("Produk tidak ditemukan di database.");
        } catch (error) {
            console.error("Terjadi kesalahan saat query produk:", error);
            alert("Gagal mengambil data produk.");
        }
    } else if (scanType.value == "member") {
        queryMember(res);
    }
};

const addToCart = (retry = false) => {
    addItem(currentItem.value);
    showQtyModal.value = false;
    if (retry) openScanProduk();
};

const lastChangeAmount = ref(0);
const lastGrandTotal = ref(0);
const cartRef = ref(null);

const handleConfirmTransaction = (printInvoice) => {
    isSubmitting.value = true;
    submitTransaction(printInvoice).then((res) => {
        isSubmitting.value = false;
        showConfirmModal.value = false;
        if (res.success) {
            lastChangeAmount.value = res.change;
            lastGrandTotal.value = res.total;
            showSuccessModal.value = true;
            cartRef.value?.resetStep();
            showMobileCart.value = false;
            setTimeout(() => {
                showSuccessModal.value = false;
            }, 5000);
        } else {
            toast.success("Transaksi berhasil disimpan.");
        }
    }).catch((err) => {
        console.error("Error submitting transaction:", err);
        isSubmitting.value = false;
    });
};

const handleProductListScroll = () => {
    if (document.activeElement && ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        document.activeElement.blur();
    }
};

const dynamicBrands = storeToRefs(posState).dynamicBrands;
const dynamicSizes = storeToRefs(posState).dynamicSizes;
</script>

<template>
    <Head title="Kasir POS" />
    <ProductDetailModal :show="showDetailModal" :product="selectedDetailProduct" @close="showDetailModal = false" @addToCart="(p) => { prepareModalData(p); }" />
    <ProductComparisonModal :show="showCompareModal" :products="compareList" @close="showCompareModal = false" @remove="toggleCompare" @addToCart="(p) => { prepareModalData(p); }" />
    <BarcodeScanner v-if="showScanner" @result="handleResScan" @close="showScanner = false" />

    <div class="flex flex-col h-[100dvh] w-full bg-gray-100 dark:bg-gray-900 font-sans transition-colors duration-300 overflow-hidden">

        <!-- ===== PREMIUM TOP BAR ===== -->
        <div class="flex-none flex items-center h-12 px-3 z-[60] w-full bg-gray-900 shadow-lg relative overflow-hidden">
            <!-- Subtle gradient accent based on mode -->
            <div class="absolute inset-0 opacity-20 pointer-events-none transition-colors duration-500"
                :class="activeDraft?.mode === 'bengkel' ? 'bg-gradient-to-r from-blue-600 to-blue-900' : 'bg-gradient-to-r from-lime-600 to-lime-900'">
            </div>

            <!-- Left: Tab Pills -->
            <div class="flex items-center gap-1 h-full min-w-0 overflow-x-auto scrollbar-hide relative z-10 mr-10">
                <div
                    v-for="(trx, index) in drafts"
                    :key="trx.id"
                    @click="switchTab(index)"
                    :class="[
                        'flex items-center gap-1.5 px-3 h-7 cursor-pointer transition-all rounded-full text-xs font-bold whitespace-nowrap',
                        activeTabIndex === index
                            ? activeDraft?.mode === 'bengkel'
                                ? 'bg-blue-500 text-white shadow-md shadow-blue-500/30'
                                : 'bg-lime-500 text-white shadow-md shadow-lime-500/30'
                            : 'text-gray-400 hover:text-white hover:bg-gray-800'
                    ]"
                >
                    <span class="truncate max-w-[80px]">Tab {{ index + 1 }}</span>
                    <span
                        v-if="trx.cart_items?.length"
                        class="text-[9px] text-lime-500 bg-white px-1.5 py-0.5 rounded-full leading-none"
                    >{{ trx.cart_items.length }}</span>
                    <button
                        v-if="drafts.length > 1"
                        @click.stop="removeTab(index)"
                        class="ml-0.5 p-0.5 text-white/40 hover:text-red-400 transition"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Add Tab -->
                <button
                    @click="addNewTab"
                    v-if="drafts.length < 5"
                    class="flex items-center justify-center w-7 h-7 text-gray-500 hover:text-lime-400 hover:bg-gray-800 rounded-full transition shrink-0"
                    title="Tambah Tab Baru"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
            </div>

            <!-- Right: Mode Toggle -->
            <div class="ml-auto flex items-center bg-gray-800 rounded-full p-0.5 border border-gray-700 relative z-10 shrink-0">
                <button
                    @click="switchPosMode('retail')"
                    :class="[
                        'px-3 py-1 text-[11px] hidden md:flex font-bold rounded-full transition-all',
                        activeDraft?.mode === 'retail'
                            ? 'bg-lime-500 text-white shadow-sm'
                            : 'text-gray-400 hover:text-white'
                    ]"
                >Retail</button>
                <button
                    @click="switchPosMode('bengkel')"
                    :class="[
                        'px-3 py-1 text-[11px] hidden md:flex font-bold rounded-full transition-all',
                        activeDraft?.mode === 'bengkel'
                            ? 'bg-blue-500 text-white shadow-sm'
                            : 'text-gray-400 hover:text-white'
                    ]"
                >Bengkel</button>
                <button
                    @click="switchPosMode(activeDraft?.mode === 'bengkel' ? 'retail' : 'bengkel')"
                    :class="[
                        'px-3 py-1 text-[11px] font-bold md:hidden rounded-full transition-all',
                        activeDraft?.mode === 'retail'
                            ? 'bg-blue-500 text-white shadow-sm'
                            : 'bg-lime-500 text-white shadow-sm'
                    ]"
                >{{ activeDraft?.mode === 'bengkel' ? 'Retail' : 'Bengkel' }}</button>
            </div>
        </div>

        <!-- Screen Saver -->
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

        <!-- ===== MAIN CONTENT ===== -->
        <div class="flex flex-col lg:flex-row flex-1 h-full w-full overflow-hidden">
            <!-- Left: Products -->
            <div class="flex flex-col flex-1 h-full md:overflow-hidden">
                <FilterProduct
                    :categories="categories"
                    :brands="dynamicBrands?.length ? dynamicBrands : brands"
                    :sizes="dynamicSizes"
                    :is-fetching="isFetchingData"
                    v-model:search="activeDraft.filterState.search"
                    v-model:category="activeDraft.filterState.category"
                    v-model:subCategory="activeDraft.filterState.subCategory"
                    v-model:brand="activeDraft.filterState.brand"
                    v-model:size="activeDraft.filterState.size"
                    v-model:sort="activeDraft.filterState.sort"
                v-model:hideEmptyStock="activeDraft.filterState.hideEmptyStock"
                    @scan="openScanProduk"
                />
                <div class="flex-1 overflow-y-auto w-full">
                    <ProductList
                        :products="filteredProducts"
                        :is-fetching="isFetchingData"
                        :cart-items="activeDraft?.cart_items || []"
                        :all-products-count="allProducts.length"
                        :compare-list="compareList"
                        :search-term="activeDraft.filterState.search"
                        @loadMore="loadMoreProducts"
                        @addToCart="handleDirectAddToCart"
                        @openDetail="openDetail"
                        @toggleCompare="toggleCompare"
                        @scroll="handleProductListScroll"
                    />
                </div>
            </div>

            <!-- Right: Cart -->
            <div :class="showMobileCart ? 'flex' : 'md:flex hidden'" class="flex-none w-full lg:w-[380px] xl:w-[420px] h-full flex-col border-t lg:border-t-0 lg:border-l border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 transition-all duration-300 relative z-[60]">
                <Cart
                    ref="cartRef"
                    :showMobileCart="showMobileCart"
                    @openScanMember="openScanMember"
                    @showBayar="showConfirmModal = true"
                    @showDesktop="showMobileCart = false"
                />
            </div>
        </div>
    </div>

    <!-- Floating Compare Bar -->
    <div
        v-if="compareList?.length > 0"
        class="absolute z-40 bottom-24 lg:bottom-8 right-6 flex items-center bg-gray-900 dark:bg-gray-800 text-white pl-4 pr-1 py-1.5 gap-4 rounded-full shadow-xl border border-gray-700"
    >
        <div class="text-xs font-bold whitespace-nowrap">{{ compareList.length }} Banding</div>
        <div class="flex items-center gap-1">
            <button @click="showCompareModal = true" :class="activeDraft.mode === 'bengkel'
            ? 'bg-blue-500 hover:bg-blue-400' : 'bg-lime-500 hover:bg-lime-400'" class="px-3 py-1.5  text-white text-xs font-bold rounded-full transition">Buka</button>
            <button @click="clearCompare" class="p-1.5 hover:bg-gray-700 rounded-full text-gray-400 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <!-- Mobile Floating Cart Button -->
    <div
        v-if="activeDraft.cart_items.length > 0 && !showMobileCart"
        class="fixed z-50 lg:hidden bottom-4 left-4 right-4"
    >
        <button
            @click="showMobileCart = true"
            class="w-full p-3.5 rounded-2xl shadow-xl flex justify-between items-center transition-all active:scale-[0.98]"
            :class="activeDraft.mode === 'bengkel'
                ? 'bg-gray-900 dark:bg-blue-600 text-white shadow-blue-900/40'
                : 'bg-gray-900 dark:bg-lime-600 text-white shadow-lime-900/40'"
        >
            <div class="flex items-center gap-3">
                <span class="flex items-center justify-center text-xs font-bold text-white rounded-full shadow-sm w-7 h-7"
                    :class="activeDraft.mode === 'bengkel' ? 'bg-blue-400' : 'bg-lime-500 dark:bg-white dark:text-lime-700'"
                >{{ activeDraft.cart_items.reduce((a, b) => a + parseFloat(b.quantity), 0) }}</span>
                <div class="flex flex-col items-start leading-none gap-0.5">
                    <span class="text-[9px] uppercase font-bold tracking-wider opacity-70">Total Belanja</span>
                    <span class="text-lg font-bold">{{ rp(grandTotal) }}</span>
                </div>
            </div>
            <span class="flex items-center gap-1 px-4 py-2 text-xs font-bold rounded-xl" :class="activeDraft.mode === 'bengkel' ? 'bg-blue-600/30' : 'bg-lime-600/30 dark:bg-lime-800/30'">
                Bayar
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </span>
        </button>
    </div>

    <!-- Modals -->
    <PosQtyModal :show="showQtyModal" v-model:item="currentItem" @close="showQtyModal = false" @addToCart="addToCart" />
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
.animate-fade-in-down { animation: fadeInDown 0.3s ease-out; }
@keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
.custom-scroll::-webkit-scrollbar { width: 3px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
.dark .custom-scroll::-webkit-scrollbar-thumb { background: #4b5563; }
.animate-bounce-subtle { animation: bounce-subtle 2s infinite; }
@keyframes bounce-subtle { 0%, 100% { transform: translateY(-3%); } 50% { transform: translateY(0); } }
.animate-fade-in { animation: fadeIn 0.2s ease-out; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
</style>
