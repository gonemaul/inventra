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
    form, // Ini Cart-nya (form.items)
    selectedMember,

    // Actions
    addItem,
    updateQty,
    removeItem,
    calculateSubtotal, // Penting buat fitur edit harga

    // Member Actions
    selectMember,

    // Validations & Computed
    grandTotal,
    changeAmount,
    isPaymentSufficient, // Validasi uang cukup
    hasInvalidQty,
    hasStockError,

    submitTransaction,
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

const moneySuggestions = [
    {
        label: "Uang Pas",
        value: "exact",
        class: "bg-lime-600 text-white border-lime-600",
    },
    { label: "+2.000", value: 2000 },
    { label: "+5.000", value: 5000 },
    { label: "+10.000", value: 10000 },
    { label: "+20.000", value: 20000 },
    { label: "+50.000", value: 50000 },
    { label: "+100.000", value: 100000 },
];

const handleMoneyClick = (suggestion) => {
    // 1. Jika klik "Uang Pas" -> Set sesuai total tagihan
    if (suggestion.value === "exact") {
        form.payment_amount = grandTotal.value;
    }
    // 2. Jika klik Pecahan -> Tambahkan ke nominal yang ada
    else {
        // Pastikan angka, jika kosong anggap 0
        let current = parseInt(form.payment_amount) || 0;
        form.payment_amount = current + suggestion.value;
    }
};
const resetPayment = () => {
    form.payment_amount = 0;
};
const setPayment = (amount) => {
    form.payment_amount = amount;
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
    if (hasStockError.value) return alert("Ada produk melebihi stok tersedia!");

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

// Helper Format View
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);
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
                    class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addItem(product)"
                        class="bg-white dark:bg-gray-800 rounded-2xl p-2.5 shadow-md border border-gray-200 dark:border-gray-700 active:border-lime-500 active:ring-2 active:ring-lime-500/30 active:scale-95 transition-all relative group cursor-pointer flex flex-col h-full"
                    >
                        <span
                            v-if="product.stock <= 5"
                            class="absolute top-2 right-2 bg-red-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-full z-10 shadow-sm"
                            >Sisa {{ product.stock }}</span
                        >

                        <div
                            class="mb-3 overflow-hidden border border-gray-100 aspect-square bg-gray-50 dark:bg-gray-700 rounded-xl dark:border-gray-600"
                        >
                            <img
                                v-if="product.image_path"
                                :src="'/storage/' + product.image_path"
                                class="object-cover w-full h-full"
                            />
                            <div
                                v-else
                                class="w-full h-full bg-gray-300 flex items-center justify-center text-[10px] font-semibold text-gray-700"
                            >
                                NO IMG
                            </div>
                        </div>

                        <div class="flex flex-col justify-between flex-1">
                            <h3
                                class="text-xs font-bold leading-snug text-gray-800 dark:text-gray-100 line-clamp-2"
                            >
                                {{ product.name }}
                            </h3>
                            <div class="flex items-end justify-between mt-2">
                                <p
                                    class="text-sm font-black text-lime-600 dark:text-lime-400"
                                >
                                    {{
                                        rp(
                                            product.selling_price ||
                                                product.price
                                        )
                                    }}
                                </p>
                                <div
                                    class="p-1 text-gray-400 transition bg-gray-100 rounded-full dark:bg-gray-700 group-hover:bg-lime-500 group-hover:text-white"
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
                                            stroke-width="3"
                                            d="M12 4v16m8-8H4"
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
                class="flex-1 p-5 space-y-4 overflow-y-auto bg-white custom-scroll dark:bg-gray-800"
            >
                <div
                    v-if="form.items.length === 0"
                    class="flex flex-col items-center justify-center h-full text-gray-300 dark:text-gray-600 opacity-60"
                >
                    <span class="mb-3 text-5xl">🛍️</span
                    ><span class="text-sm">Keranjang Kosong</span>
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="flex gap-4 pb-4 border-b border-gray-200 border-dashed dark:border-gray-700 last:border-0"
                >
                    <div
                        class="overflow-hidden bg-gray-100 border border-gray-100 w-14 h-14 dark:bg-gray-700 rounded-xl shrink-0 dark:border-gray-600"
                    >
                        <img
                            v-if="item.image"
                            :src="'/storage/' + item.image"
                            class="object-cover w-full h-full"
                        />
                        <div
                            v-else
                            class="w-full h-full bg-gray-300 flex items-center justify-center text-[8px] font-semibold text-gray-700"
                        >
                            NO IMG
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-1.5">
                            <h4
                                class="text-xs font-bold leading-snug text-gray-800 dark:text-gray-200 line-clamp-2"
                            >
                                {{ item.name }}
                            </h4>
                            <span
                                class="ml-2 text-xs font-bold text-lime-600 dark:text-lime-400 whitespace-nowrap"
                                >{{ rp(item.subtotal) }}</span
                            >
                        </div>

                        <div class="flex items-center justify-between">
                            <div
                                class="flex items-center gap-1 bg-gray-50 dark:bg-gray-700/50 rounded px-1.5 py-0.5 border border-gray-100 dark:border-gray-700"
                            >
                                <span class="text-[10px] text-gray-400">@</span>
                                <input
                                    type="number"
                                    v-model="item.selling_price"
                                    class="w-16 p-0 text-[11px] font-bold border-none bg-transparent text-gray-600 dark:text-gray-300 focus:ring-0 text-left"
                                    @focus="$event.target.select()"
                                    @input="calculateSubtotal(item)"
                                />
                            </div>

                            <div
                                class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-700 dark:border-gray-600 h-7"
                            >
                                <button
                                    @click="updateQty(index, -1)"
                                    class="flex items-center justify-center h-full text-gray-500 transition border-r border-gray-100 w-7 hover:text-red-500 dark:border-gray-600"
                                >
                                    -
                                </button>
                                <span
                                    class="w-8 text-xs font-bold text-center text-gray-800 dark:text-white"
                                    >{{ item.quantity }}</span
                                >
                                <button
                                    @click="updateQty(index, 1)"
                                    class="flex items-center justify-center h-full transition border-l border-gray-100 w-7 text-lime-600 hover:bg-lime-50 dark:hover:bg-gray-600 dark:border-gray-600"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="shrink-0 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] z-10 transition-all duration-300"
            >
                <button
                    @click="showPaymentOptions = !showPaymentOptions"
                    class="flex items-center justify-center w-full py-2 text-gray-400 transition border-b cursor-pointer hover:text-lime-600 hover:bg-gray-100 dark:hover:bg-gray-800 dark:border-gray-800"
                >
                    <span
                        class="text-[10px] font-bold uppercase tracking-widest mr-1"
                    >
                        {{
                            showPaymentOptions
                                ? "Tutup Opsi"
                                : "Metode & Uang Pas"
                        }}
                    </span>
                    <svg
                        :class="{ 'rotate-180': showPaymentOptions }"
                        class="w-4 h-4 transition-transform duration-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 15l7-7 7 7"
                        ></path>
                    </svg>
                </button>

                <div class="p-5 pt-2">
                    <div
                        v-show="showPaymentOptions"
                        class="pb-2 mb-4 border-b border-gray-200 border-dashed animate-fade-in-down dark:border-gray-700"
                    >
                        <label
                            class="text-[9px] font-bold text-gray-400 uppercase mb-2 block"
                            >Metode Pembayaran</label
                        >
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <button
                                v-for="method in ['cash', 'transfer', 'qris']"
                                :key="method"
                                @click="form.payment_method = method"
                                :class="
                                    form.payment_method === method
                                        ? 'bg-lime-500 text-white border-lime-500 shadow-md ring-2 ring-lime-500/20'
                                        : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                                "
                                class="h-9 rounded-lg text-[10px] font-bold uppercase border transition-all active:scale-95 flex items-center justify-center gap-1.5"
                            >
                                <span v-if="method === 'cash'">💵</span>
                                <span v-else-if="method === 'transfer'"
                                    >🏦</span
                                >
                                <span v-else>📱</span>
                                {{ method }}
                            </button>
                        </div>

                        <label
                            class="text-[9px] font-bold text-gray-400 uppercase mb-2 block"
                            >Pecahan Uang</label
                        >
                        <div class="grid grid-cols-4 gap-2">
                            <button
                                @click="resetPayment"
                                class="flex items-center justify-center text-xs font-bold text-red-500 transition border border-red-100 rounded-lg h-9 bg-red-50 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400 active:scale-95"
                            >
                                C
                            </button>
                            <button
                                v-for="suggestion in moneySuggestions"
                                :key="suggestion.label"
                                @click="handleMoneyClick(suggestion)"
                                :class="
                                    suggestion.class ||
                                    'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 hover:border-lime-500 hover:text-lime-600'
                                "
                                class="h-9 flex items-center justify-center rounded-lg text-[10px] font-bold shadow-sm transition active:scale-95"
                            >
                                {{ suggestion.label }}
                            </button>
                        </div>
                    </div>

                    <div
                        class="relative p-3 mb-4 transition bg-white border border-gray-200 shadow-sm dark:bg-gray-800 rounded-2xl dark:border-gray-700 focus-within:ring-2 focus-within:ring-lime-500 focus-within:border-lime-500"
                    >
                        <div class="flex justify-between mb-1">
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                                >Tagihan</label
                            >
                            <span
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                                >Bayar ({{ form.payment_method }})</span
                            >
                        </div>
                        <div class="flex items-center justify-between">
                            <span
                                class="text-base font-black text-gray-800 dark:text-white"
                                >{{ rp(grandTotal) }}</span
                            >
                            <div class="relative w-1/2">
                                <MoneyInput
                                    v-model="form.payment_amount"
                                    placeholder="0"
                                    class="w-full p-0 text-lg font-black text-right bg-transparent border-none text-lime-600 dark:text-lime-400 focus:ring-0"
                                />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="form.payment_amount > 0"
                        class="flex justify-between px-1 mb-4 text-sm animate-fade-in"
                    >
                        <span
                            :class="
                                form.payment_amount < grandTotal
                                    ? 'text-red-500 font-bold text-[14px]'
                                    : 'text-gray-500 text-[12px]'
                            "
                        >
                            {{
                                form.payment_amount < grandTotal
                                    ? "⚠️ KURANG"
                                    : "Kembalian"
                            }}
                        </span>
                        <span
                            :class="
                                form.payment_amount < grandTotal
                                    ? 'text-red-500'
                                    : 'text-gray-800 dark:text-white'
                            "
                            class="font-black text-[14px]"
                        >
                            {{ rp(Math.abs(changeAmount)) }}
                        </span>
                    </div>

                    <button
                        @click="handleCheckoutClick"
                        :disabled="!form.items.length"
                        class="w-full py-3.5 bg-lime-500 hover:bg-lime-600 text-white font-bold rounded-2xl shadow-lg shadow-lime-500/30 disabled:opacity-50 text-sm transition-all active:scale-95 flex justify-center items-center gap-2 group"
                    >
                        <span>PROSES SEKARANG</span>
                        <svg
                            class="w-4 h-4 transition group-hover:translate-x-1"
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
