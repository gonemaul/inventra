<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed, nextTick, onBeforeUnmount } from "vue";
import { usePosRealtime } from "@/Composable/usePosRealtime";
import MoneyInput from "../partials/MoneyInput.vue";
import { Html5Qrcode, Html5QrcodeSupportedFormats } from "html5-qrcode";

const props = defineProps({
    products: Array,
    categories: Array,
    customers: Array,
});

// --- PANGGIL COMPOSABLE ---
const {
    // State
    form,
    searchResults,
    isLoadingSearch,
    memberSearchResults,
    isLoadingMember,
    selectedMember,

    // Computed
    subTotal,
    discountAmount,
    grandTotal,
    changeAmount,
    isPaymentSufficient,
    hasInvalidQty,
    moneySuggestions,

    // Core Actions
    addItem,
    removeItem,
    updateQty,

    // Calculation Actions
    recalcFromQty,
    recalcFromSubtotal,
    recalcFromPrice,

    // Payment Actions
    handleMoneyClick,
    resetPayment,
    // handleCheckoutClick,

    // Search Actions
    handleSearch,
    handleSearchMember,
    selectMember,

    submitTransaction,
    rp,
} = usePosRealtime(props);

// --- STATE LOKAL UI (Client Side Search) ---
const searchQuery = ref("");
const selectedCategory = ref("all");
const showMobileCart = ref(false);
const showPaymentOptions = ref(false);

const memberSearch = ref("");
const showConfirmModal = ref(false);
const processingTransaction = ref(false);

// [BARU] State Scanner
const activeScannerType = ref(null); // nil, 'product', atau 'member'
const html5QrCode = ref(null);

// --- LOGIC SEARCH PRODUK (CLIENT SIDE) ---
// Kita filter dari props.products biar cepat (tanpa request server)
const filteredProducts = computed(() => {
    let items = props.products;

    // Filter Kategori
    if (selectedCategory.value !== "all") {
        items = items.filter((p) => p.category_id === selectedCategory.value);
    }

    // Filter Search
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        items = items.filter(
            (p) =>
                p.name.toLowerCase().includes(q) ||
                (p.code && p.code.toLowerCase().includes(q))
        );
    }
    return items;
});

// --- LOGIC SEARCH MEMBER (CLIENT SIDE) ---
const filteredCustomers = computed(() => {
    if (!memberSearch.value) return [];
    const q = memberSearch.value.toLowerCase();
    return props.customers
        .filter(
            (c) =>
                c.name.toLowerCase().includes(q) ||
                (c.member_code && c.member_code.toLowerCase().includes(q)) ||
                (c.phone && c.phone.includes(q))
        )
        .slice(0, 5);
});

const handleSelectMember = (c) => {
    selectMember(c); // Panggil fungsi dari composable
    memberSearch.value = "";
};

// --- [BARU] LOGIC CAMERA SCANNER ---
const startScanner = async (type) => {
    activeScannerType.value = type;
    await nextTick(); // Tunggu DOM render div id="reader"

    const formatsToSupport = [
        Html5QrcodeSupportedFormats.CODE_128,
        Html5QrcodeSupportedFormats.EAN_13,
        Html5QrcodeSupportedFormats.QR_CODE,
    ];
    const qrCode = new Html5Qrcode("reader");
    html5QrCode.value = qrCode;

    qrCode
        .start(
            { facingMode: "environment" }, // Pakai kamera belakang
            {
                fps: 10,
                qrbox: { width: 250, height: 150 },
                formatsToSupport: formatsToSupport,
            },
            (decodedText) => {
                // SAAT SCAN BERHASIL
                handleScanSuccess(decodedText);
            },
            (errorMessage) => {
                // ignore errors while scanning
            }
        )
        .catch((err) => {
            console.log(err);
            activeScannerType.value = null;
            alert("Gagal membuka kamera. Pastikan izin diberikan.");
        });
};

const stopScanner = () => {
    if (html5QrCode.value) {
        html5QrCode.value
            .stop()
            .then(() => {
                html5QrCode.value.clear();
                activeScannerType.value = null;
            })
            .catch((err) => console.log(err));
    } else {
        activeScannerType.value = null;
    }
};

const handleScanSuccess = (code) => {
    // 1. JIKA SCAN PRODUK
    if (activeScannerType.value === "product") {
        const product = props.products.find(
            (p) => p.code == code || p.sku == code
        );
        if (product) {
            addItem(product);
            // Opsional: Beep Sound
        } else {
            alert(`Produk ${code} tidak ditemukan!`);
        }
    }
    // 2. JIKA SCAN MEMBER
    else if (activeScannerType.value === "member") {
        // Cari member berdasarkan Member Code ATAU No HP
        const member = props.customers.find(
            (c) => c.member_code == code || c.phone == code
        );

        if (member) {
            handleSelectMember(member); // Pilih member
            stopScanner(); // Langsung tutup kamera kalau member ketemu
            alert(`Member: ${member.name}`);
        } else {
            alert(`Member ${code} tidak terdaftar!`);
        }
    }
};

onBeforeUnmount(() => {
    if (html5QrCode.value && html5QrCode.value.isScanning) {
        html5QrCode.value.stop();
    }
});

const handleCheckoutClick = () => {
    // 1. Validasi Keranjang
    if (form.items.length === 0) return alert("Keranjang belanja kosong!");

    // 2. Validasi Stok & Qty (Ambil dari composable)
    if (hasInvalidQty.value) return alert("Ada produk dengan Qty 0!");
    // if (hasStockError.value) return alert("Ada produk melebihi stok tersedia!");

    // 3. Validasi Uang
    if (!isPaymentSufficient.value) return alert("Uang pembayaran kurang!");

    // Jika aman, buka modal
    showConfirmModal.value = true;
};

const confirmTransaction = (shouldPrint) => {
    submitTransaction(shouldPrint, {
        onSuccess: (page) => {
            // Tutup modal & drawer HP setelah sukses reset data
            showConfirmModal.value = false;
            showMobileCart.value = false;

            console.log("Flash Data:", page.props.flash);
            const printUrl = page.props.flash?.print_url;
            console.log("Print URL:", printUrl);
            if (shouldPrint && printUrl) {
                // Buka Tab Baru untuk Print
                // Gunakan setTimeout agar tidak dianggap pop-up spam oleh browser
                setTimeout(() => {
                    window.open(printUrl, "_blank", "width=300,height=600");
                }, 50);
            } else if (shouldPrint && !printUrl) {
                alert("Gagal mendapatkan URL Print dari server.");
            }
        },
    });
};
</script>

<template>
    <Head title="Kasir POS" />

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

                    <button
                        @click="startScanner('product')"
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
                class="flex-1 p-4 overflow-y-auto bg-gray-100 custom-scroll pb-28 lg:pb-4 dark:bg-gray-900"
            >
                <div
                    class="grid grid-cols-2 gap-3 pb-20 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addItem(product)"
                        class="relative overflow-hidden transition-all bg-white border border-gray-200 shadow-sm cursor-pointer group dark:bg-gray-800 rounded-xl dark:border-gray-700 hover:shadow-md hover:border-lime-500 active:scale-95"
                    >
                        <div
                            class="relative w-full overflow-hidden bg-gray-100 aspect-square dark:bg-gray-700"
                        >
                            <img
                                v-if="product.image_path"
                                :src="product.image_url"
                                loading="lazy"
                                decoding="async"
                                alt=""
                                class="object-cover w-full h-full transition-opacity duration-500 opacity-0"
                                onload="this.classList.remove('opacity-0')"
                            />

                            <div
                                class="absolute inset-0 flex flex-col items-center justify-center text-gray-300 dark:text-gray-600 -z-10"
                            >
                                <svg
                                    class="w-8 h-8 opacity-50"
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
                    v-if="filteredProducts.length === 0"
                    class="mt-20 text-sm text-center text-gray-400"
                >
                    <p class="mb-2 text-4xl">🔍</p>
                    Produk tidak ditemukan
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

        <div
            :class="[
                'lg:static lg:w-[420px] lg:border-l lg:border-gray-200 dark:lg:border-gray-700',
                'fixed inset-0 z-40 bg-white dark:bg-gray-800 transition-transform duration-300 ease-in-out flex flex-col h-full',
                showMobileCart
                    ? 'translate-y-0'
                    : 'translate-y-full lg:translate-y-0',
            ]"
        >
            <div
                class="flex items-center justify-between px-5 py-4 border-b shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 lg:bg-white lg:dark:bg-gray-800"
            >
                <div class="flex items-center gap-2">
                    <h2
                        class="text-lg font-black text-gray-800 dark:text-white"
                    >
                        <span class="text-lime-500">🛒</span> Keranjang
                    </h2>
                    <button
                        v-if="form.items.length"
                        @click="form.items = []"
                        class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded ml-2 hover:bg-red-100 transition"
                    >
                        Reset
                    </button>
                </div>
                <button
                    @click="showMobileCart = false"
                    class="p-2 text-gray-500 bg-gray-100 rounded-full lg:hidden dark:bg-gray-700"
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

            <div
                class="px-5 py-3 bg-white border-b shrink-0 dark:border-gray-700 dark:bg-gray-800"
            >
                <div v-if="!selectedMember" class="relative">
                    <input
                        v-model="memberSearch"
                        type="text"
                        placeholder="Cari / Scan Member..."
                        class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 pr-10 bg-gray-50 dark:bg-gray-900 dark:text-white focus:ring-lime-500 transition"
                    />

                    <button
                        @click="startScanner('member')"
                        class="absolute right-1.5 top-1.5 p-1 bg-white dark:bg-gray-700 rounded-lg shadow-sm text-gray-500 dark:text-gray-300 hover:text-lime-600 border border-gray-200 dark:border-gray-600"
                        title="Scan Kartu Member"
                    >
                        <svg
                            class="w-4 h-4"
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
                    <div
                        v-if="filteredCustomers.length"
                        class="absolute left-0 z-50 w-full mt-2 overflow-hidden bg-white border shadow-xl top-full dark:bg-gray-800 dark:border-gray-600 rounded-xl"
                    >
                        <div
                            v-for="c in filteredCustomers"
                            :key="c.id"
                            @click="handleSelectMember(c)"
                            class="p-3 text-xs text-gray-800 border-b cursor-pointer hover:bg-lime-50 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-200 last:border-0"
                        >
                            <b>{{ c.name }}</b>
                            <span class="text-gray-400">({{ c.phone }})</span>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="flex justify-between bg-lime-50 dark:bg-gray-700 p-2.5 rounded-xl border border-lime-200 dark:border-gray-600"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full shadow-sm bg-lime-500"
                        >
                            {{ selectedMember.name[0] }}
                        </div>
                        <div>
                            <div
                                class="text-xs font-bold text-gray-800 dark:text-white"
                            >
                                {{ selectedMember.name }}
                            </div>
                            <div
                                class="text-[10px] text-gray-500 dark:text-gray-300 tracking-wide"
                            >
                                {{ selectedMember.member_code }}
                            </div>
                        </div>
                    </div>
                    <button
                        @click="handleSelectMember(null)"
                        class="p-1 text-gray-400 hover:text-red-500"
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
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div
                class="flex-1 px-4 py-2 space-y-3 overflow-y-auto bg-gray-50 custom-scroll dark:bg-gray-900"
            >
                <div
                    v-if="form.items.length === 0"
                    class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-600 opacity-70"
                >
                    <div
                        class="p-6 mb-4 bg-white rounded-full shadow-sm dark:bg-gray-800"
                    >
                        <span class="text-5xl">🛍️</span>
                    </div>
                    <span class="text-sm font-bold tracking-wide uppercase"
                        >Keranjang Kosong</span
                    >
                    <span class="mt-1 text-xs font-normal"
                        >Scan barcode atau cari produk...</span
                    >
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="relative overflow-hidden transition-colors duration-200 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700 group hover:border-lime-300"
                >
                    <div
                        class="flex items-start justify-between p-3 pb-2 border-b border-gray-50 dark:border-gray-700/50"
                    >
                        <div class="pr-2">
                            <h4
                                class="text-sm font-bold leading-tight text-gray-800 dark:text-gray-100"
                            >
                                {{ item.name }}
                            </h4>
                            <div class="flex items-center gap-2 mt-1">
                                <span
                                    class="text-[10px] font-mono text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded tracking-wide"
                                >
                                    {{ item.code }}
                                </span>
                                <span
                                    v-if="item.unit"
                                    class="text-[10px] font-bold text-gray-400"
                                >
                                    / {{ item.unit.name }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="removeItem(index)"
                            class="text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg p-1.5 transition"
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
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
                            </svg>
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-12 gap-0 divide-x divide-gray-100 dark:divide-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                    >
                        <div
                            class="flex flex-col justify-center col-span-4 p-2"
                        >
                            <label
                                class="text-[9px] font-bold text-gray-400 uppercase mb-0.5 ml-1"
                                >Harga @</label
                            >
                            <div class="relative group/price">
                                <input
                                    type="number"
                                    v-model="item.selling_price"
                                    @input="recalcFromPrice(item)"
                                    class="w-full py-1 pl-2 pr-1 text-xs font-bold text-gray-600 bg-transparent border-none rounded focus:ring-1 focus:ring-lime-500 dark:text-gray-300 tabular-nums"
                                    @focus="$event.target.select()"
                                />
                                <div
                                    class="absolute right-1 top-1.5 opacity-0 group-hover/price:opacity-100 transition-opacity"
                                >
                                    <svg
                                        class="w-3 h-3 text-gray-300"
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
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col items-center justify-center col-span-4 p-2"
                        >
                            <label
                                class="text-[9px] font-bold text-gray-400 uppercase mb-0.5"
                                >Qty</label
                            >
                            <div
                                class="flex items-center w-full overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm h-7 dark:bg-gray-800 dark:border-gray-600"
                            >
                                <button
                                    @click="updateQty(index, -1)"
                                    class="flex items-center justify-center w-8 h-full text-gray-400 transition hover:text-red-500 hover:bg-red-50 active:scale-95"
                                >
                                    -
                                </button>
                                <input
                                    type="number"
                                    v-model="item.quantity"
                                    @input="recalcFromQty(item)"
                                    :step="
                                        item.unit?.is_decimal ? '0.001' : '1'
                                    "
                                    class="z-10 w-full h-full p-0 text-xs font-black text-center text-gray-800 border-none focus:ring-0 dark:text-white tabular-nums"
                                    @focus="$event.target.select()"
                                />
                                <button
                                    @click="updateQty(index, 1)"
                                    class="flex items-center justify-center w-8 h-full text-gray-400 transition hover:text-lime-600 hover:bg-lime-50 active:scale-95"
                                >
                                    +
                                </button>
                            </div>
                        </div>

                        <div
                            class="relative flex flex-col justify-center col-span-4 p-2"
                        >
                            <label
                                class="text-[9px] font-bold text-gray-400 uppercase mb-0.5 text-right mr-1"
                                >Subtotal</label
                            >

                            <input
                                type="number"
                                v-model="item.subtotal"
                                @change="recalcFromSubtotal(item)"
                                class="w-full px-2 py-1 text-xs font-black text-right transition-all border rounded focus:ring-2 focus:ring-lime-500 tabular-nums"
                                :class="[
                                    item.unit?.is_decimal
                                        ? 'bg-lime-50 border-lime-200 text-lime-700 placeholder-lime-300 dark:bg-lime-900/20 dark:border-lime-800 dark:text-lime-400'
                                        : 'bg-white border-gray-200 text-gray-800 focus:bg-yellow-50 focus:border-yellow-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                                ]"
                                @focus="$event.target.select()"
                            />

                            <div
                                v-if="item.unit?.is_decimal"
                                class="absolute top-2 left-2 text-lime-500"
                                title="Aman: Ubah nominal, Qty menyesuaikan"
                            >
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
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                            </div>
                            <div
                                v-else
                                class="absolute transition-opacity opacity-0 pointer-events-none top-9 right-2 group-hover:opacity-100"
                            >
                                <span
                                    class="text-[8px] bg-yellow-100 text-yellow-800 px-1 rounded border border-yellow-200 shadow-sm"
                                >
                                    ⚠️ Ubah = Ganti Harga
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="shrink-0 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] z-20 relative"
            >
                <div
                    class="absolute left-0 z-10 flex justify-center w-full -top-3"
                >
                    <button
                        @click="showPaymentOptions = !showPaymentOptions"
                        class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-full px-4 py-0.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-lime-600 hover:border-lime-500 shadow-sm transition-all flex items-center gap-1"
                    >
                        {{ showPaymentOptions ? "Tutup" : "Opsi" }}
                        <svg
                            :class="{ 'rotate-180': showPaymentOptions }"
                            class="w-3 h-3 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 9l-7 7-7-7"
                            ></path>
                        </svg>
                    </button>
                </div>

                <div class="px-5 pt-3 pb-5">
                    <div
                        v-show="showPaymentOptions"
                        class="mb-4 space-y-4 animate-fade-in-down"
                    >
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block"
                                    >Pembayaran</label
                                >
                                <div class="flex flex-col gap-1.5">
                                    <button
                                        v-for="method in [
                                            'cash',
                                            'transfer',
                                            'qris',
                                        ]"
                                        :key="method"
                                        @click="form.payment_method = method"
                                        :class="
                                            form.payment_method === method
                                                ? 'bg-lime-100 text-lime-700 border-lime-300 font-bold'
                                                : 'bg-gray-50 text-gray-500 border-gray-100 hover:bg-gray-100'
                                        "
                                        class="px-3 py-1.5 rounded-lg text-xs uppercase border transition-all flex items-center gap-2"
                                    >
                                        <span v-if="method === 'cash'">💵</span
                                        ><span v-else-if="method === 'transfer'"
                                            >🏦</span
                                        ><span v-else>📱</span>
                                        {{ method }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block"
                                    >Diskon Global</label
                                >
                                <div class="flex mb-2">
                                    <button
                                        @click="form.discount_type = 'fixed'"
                                        :class="
                                            form.discount_type === 'fixed'
                                                ? 'bg-lime-500 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                        class="px-2 py-1 text-xs font-bold border border-r-0 border-gray-200 rounded-l-lg dark:border-gray-600"
                                    >
                                        Rp
                                    </button>
                                    <button
                                        @click="form.discount_type = 'percent'"
                                        :class="
                                            form.discount_type === 'percent'
                                                ? 'bg-lime-500 text-white'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                        class="px-2 py-1 text-xs font-bold border border-l-0 border-gray-200 rounded-r-lg dark:border-gray-600"
                                    >
                                        %
                                    </button>
                                    <input
                                        v-model="form.discount_value"
                                        type="number"
                                        placeholder="0"
                                        class="w-full px-2 py-1 ml-2 text-sm font-bold bg-white border border-gray-200 rounded-lg focus:ring-lime-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="relative">
                                <input
                                    v-model="form.notes"
                                    type="text"
                                    placeholder="Catatan transaksi (opsional)..."
                                    class="w-full py-2 pl-8 pr-3 text-xs transition-colors border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:ring-lime-500"
                                />
                                <span
                                    class="absolute left-2.5 top-2 text-gray-400"
                                    >📝</span
                                >
                            </div>
                        </div>

                        <div>
                            <div
                                class="flex gap-2 pb-1 overflow-x-auto scrollbar-hide"
                            >
                                <button
                                    @click="resetPayment"
                                    class="px-3 py-1.5 bg-red-50 text-red-500 border border-red-100 rounded-lg text-xs font-bold whitespace-nowrap active:scale-95"
                                >
                                    Reset
                                </button>
                                <button
                                    v-for="suggestion in moneySuggestions"
                                    :key="suggestion.label"
                                    @click="handleMoneyClick(suggestion)"
                                    class="px-3 py-1.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-xs font-bold whitespace-nowrap shadow-sm hover:border-lime-500 hover:text-lime-600 active:scale-95 transition"
                                >
                                    {{ suggestion.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3">
                        <div
                            class="flex items-end justify-between p-4 border border-gray-100 bg-gray-50 dark:bg-gray-900/50 rounded-2xl dark:border-gray-700"
                        >
                            <div class="flex flex-col">
                                <span
                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1"
                                    >Total Tagihan</span
                                >
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="text-3xl font-black leading-none tracking-tight text-gray-800 dark:text-white"
                                    >
                                        {{ rp(grandTotal) }}
                                    </span>
                                    <span
                                        v-if="discountAmount > 0"
                                        class="text-[10px] font-bold text-white bg-green-500 px-1.5 py-0.5 rounded-md animate-pulse"
                                    >
                                        Hemat {{ rp(discountAmount) }}
                                    </span>
                                </div>
                                <span
                                    class="text-[10px] text-gray-400 mt-1"
                                    v-if="subTotal !== grandTotal"
                                >
                                    Subtotal:
                                    <span class="line-through">{{
                                        rp(subTotal)
                                    }}</span>
                                </span>
                            </div>

                            <div class="w-40">
                                <label
                                    class="text-[10px] font-bold text-right text-gray-400 uppercase block mb-1"
                                >
                                    Bayar ({{ form.payment_method }})
                                </label>
                                <div class="relative">
                                    <MoneyInput
                                        v-model="form.payment_amount"
                                        placeholder="0"
                                        class="w-full p-0 text-2xl font-black text-right placeholder-gray-300 transition-colors bg-transparent border-none focus:ring-0"
                                        :class="
                                            isPaymentSufficient
                                                ? 'text-lime-600 dark:text-lime-400'
                                                : 'text-red-500'
                                        "
                                    />
                                    <div
                                        class="w-full h-1 mt-1 transition-colors rounded-full"
                                        :class="
                                            isPaymentSufficient
                                                ? 'bg-lime-500'
                                                : 'bg-red-200'
                                        "
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div
                                v-if="form.payment_amount > 0"
                                class="flex items-center justify-between flex-1 px-4 py-2 transition-colors border rounded-xl"
                                :class="
                                    isPaymentSufficient
                                        ? 'bg-lime-50 border-lime-200'
                                        : 'bg-red-50 border-red-200'
                                "
                            >
                                <span
                                    class="text-xs font-bold uppercase"
                                    :class="
                                        isPaymentSufficient
                                            ? 'text-lime-700'
                                            : 'text-red-500'
                                    "
                                >
                                    {{
                                        isPaymentSufficient
                                            ? "Kembalian"
                                            : "Kurang"
                                    }}
                                </span>
                                <span
                                    class="text-lg font-black"
                                    :class="
                                        isPaymentSufficient
                                            ? 'text-lime-700'
                                            : 'text-red-600'
                                    "
                                >
                                    {{ rp(Math.abs(changeAmount)) }}
                                </span>
                            </div>

                            <button
                                @click="handleCheckoutClick"
                                :disabled="!form.items.length"
                                :class="[
                                    !form.items.length
                                        ? 'opacity-50 cursor-not-allowed bg-gray-300'
                                        : !isPaymentSufficient &&
                                          form.payment_method === 'cash'
                                        ? 'bg-gray-800 text-white cursor-not-allowed opacity-80'
                                        : 'bg-lime-500 hover:bg-lime-600 shadow-lg shadow-lime-500/40',
                                ]"
                                class="flex-1 py-3.5 text-white font-bold rounded-xl text-sm transition-all active:scale-95 flex justify-center items-center gap-2 group h-[58px]"
                            >
                                <span
                                    v-if="
                                        !isPaymentSufficient &&
                                        form.payment_method === 'cash'
                                    "
                                    >UANG KURANG</span
                                >
                                <span v-else>PROSES</span>
                                <svg
                                    class="w-5 h-5 transition group-hover:translate-x-1"
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
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-if="activeScannerType"
        class="fixed inset-0 z-[70] bg-black flex flex-col animate-fade-in"
    >
        <div
            class="absolute top-0 z-10 flex items-center justify-between w-full p-4 text-white bg-black/50 backdrop-blur-sm"
        >
            <div class="flex items-center gap-2">
                <span v-if="activeScannerType === 'product'">📦</span>
                <span v-else>👤</span>

                <span class="text-lg font-bold">
                    Scan
                    {{ activeScannerType === "product" ? "Produk" : "Member" }}
                </span>
            </div>
            <button
                @click="stopScanner"
                class="p-2 transition rounded-full bg-gray-800/80 hover:bg-gray-700"
            >
                ✕
            </button>
        </div>

        <div id="reader" class="w-full h-full bg-black"></div>

        <div class="absolute w-full px-4 text-center bottom-10">
            <div
                class="inline-block px-4 py-2 text-sm text-white border rounded-full bg-black/60 backdrop-blur-md border-white/20"
            >
                Arahkan kamera ke
                {{
                    activeScannerType === "product"
                        ? "Barcode Barang"
                        : "Kartu/QR Member"
                }}
            </div>
        </div>
    </div>

    <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 animate-fade-in"
    >
        <div
            class="absolute inset-0 transition-opacity bg-gray-900/70 backdrop-blur-sm"
            @click="showConfirmModal = false"
        ></div>
        <div
            class="relative z-10 w-full max-w-sm p-6 overflow-hidden text-center transition-all transform scale-100 bg-white shadow-2xl dark:bg-gray-800 rounded-3xl"
        >
            <div
                class="flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-full bg-lime-100 dark:bg-lime-900/30 text-lime-600 dark:text-lime-400 animate-bounce-subtle"
            >
                <svg
                    class="w-8 h-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </div>

            <h3 class="mb-2 text-xl font-black text-gray-800 dark:text-white">
                Simpan Transaksi?
            </h3>
            <p
                class="pb-4 mb-6 text-sm text-gray-500 border-b dark:text-gray-400 dark:border-gray-700"
            >
                Kembalian:
                <span class="block mt-1 text-lg font-bold text-lime-600">{{
                    rp(Math.abs(changeAmount))
                }}</span>
            </p>

            <div class="flex flex-col gap-3">
                <button
                    @click="confirmTransaction(true)"
                    :disabled="processingTransaction"
                    class="w-full py-3.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold rounded-2xl shadow-lg transition active:scale-95 flex justify-center items-center gap-2"
                >
                    <span
                        v-if="processingTransaction"
                        class="w-4 h-4 border-2 border-current rounded-full animate-spin border-t-transparent"
                    ></span>
                    <span v-else>🖨️ Simpan & Cetak</span>
                </button>

                <button
                    @click="confirmTransaction(false)"
                    :disabled="processingTransaction"
                    class="w-full py-3.5 bg-lime-500 text-white font-bold rounded-2xl shadow-lg transition active:scale-95"
                >
                    💾 Simpan Saja
                </button>

                <button
                    @click="showConfirmModal = false"
                    class="mt-2 text-xs font-medium text-gray-400 hover:text-gray-600"
                >
                    Batal / Revisi
                </button>
            </div>
        </div>
    </div>
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
</style>
