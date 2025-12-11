<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { usePosRealtime } from "@/Composable/usePosRealtime";
import MoneyInput from "../partials/MoneyInput.vue";

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
const memberSearch = ref("");

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

// --- MODAL KONFIRMASI & PRINT ---
const showConfirmModal = ref(false);

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
        onSuccess: () => {
            // Tutup modal & drawer HP setelah sukses reset data
            showConfirmModal.value = false;
            showMobileCart.value = false;
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
        class="flex flex-col lg:flex-row h-[100dvh] w-full bg-gray-50 dark:bg-gray-900 overflow-hidden font-sans transition-colors duration-300"
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
                        placeholder="Cari Nama / Kode / Scan..."
                        class="w-full py-2 pr-4 text-sm transition-all bg-gray-100 border-none pl-9 dark:bg-gray-900 rounded-xl focus:ring-2 focus:ring-lime-500 dark:text-white"
                    />
                    <span class="absolute left-3 top-2.5 text-gray-400"
                        >🔍</span
                    >
                </div>
            </div>

            <div
                class="px-4 py-2 overflow-x-auto bg-white border-b shrink-0 dark:bg-gray-800 dark:border-gray-700 whitespace-nowrap scrollbar-hide"
            >
                <button
                    @click="selectedCategory = 'all'"
                    :class="
                        selectedCategory === 'all'
                            ? 'bg-lime-500 text-white shadow-lime-500/30'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                    "
                    class="px-4 py-1.5 rounded-full text-xs font-bold mr-2 transition-all"
                >
                    Semua
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat.id"
                    @click="selectedCategory = cat.id"
                    :class="
                        selectedCategory === cat.id
                            ? 'bg-lime-500 text-white shadow-lime-500/30'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300'
                    "
                    class="px-4 py-1.5 rounded-full text-xs font-bold mr-2 transition-all"
                >
                    {{ cat.name }}
                </button>
            </div>

            <div class="flex-1 p-4 overflow-y-auto custom-scroll pb-28 lg:pb-4">
                <div
                    class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addItem(product)"
                        class="relative p-2 transition-all bg-white border border-transparent shadow-sm cursor-pointer dark:bg-gray-800 rounded-xl active:border-lime-500 active:scale-95 group hover:shadow-md"
                    >
                        <span
                            v-if="product.stock <= 5"
                            class="absolute top-2 right-2 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded z-10"
                            >Sisa {{ product.stock }}</span
                        >

                        <div
                            class="mb-2 overflow-hidden bg-gray-100 rounded-lg aspect-square dark:bg-gray-700"
                        >
                            <img
                                v-if="product.image_path"
                                :src="'/storage/' + product.image_path"
                                class="object-cover w-full h-full"
                            />
                            <div
                                v-else
                                class="flex items-center justify-center w-full h-full text-xs text-gray-400"
                            >
                                No IMG
                            </div>
                        </div>

                        <h3
                            class="h-8 text-xs font-bold leading-tight text-gray-800 dark:text-gray-200 line-clamp-2"
                        >
                            {{ product.name }}
                        </h3>
                        <p
                            class="mt-1 text-sm font-bold text-lime-600 dark:text-lime-400"
                        >
                            {{ rp(product.selling_price || product.price) }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="filteredProducts.length === 0"
                    class="mt-10 text-sm text-center text-gray-400"
                >
                    Produk tidak ditemukan
                </div>
            </div>

            <div
                v-if="form.items.length > 0"
                class="absolute z-30 lg:hidden bottom-4 left-4 right-4"
            >
                <button
                    @click="showMobileCart = true"
                    class="flex items-center justify-between w-full p-3 text-white bg-gray-900 shadow-xl dark:bg-lime-600 rounded-xl animate-bounce-subtle"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-lime-500 dark:bg-white dark:text-lime-700"
                            >{{
                                form.items.reduce(
                                    (a, b) => a + parseFloat(b.quantity),
                                    0
                                )
                            }}</span
                        >
                        <div class="flex flex-col items-start leading-none">
                            <span
                                class="text-[10px] text-gray-400 dark:text-lime-100 uppercase"
                                >Total</span
                            >
                            <span class="font-bold">{{ rp(grandTotal) }}</span>
                        </div>
                    </div>
                    <span
                        class="px-3 py-1 text-xs font-bold bg-gray-700 rounded-lg dark:bg-lime-800/30"
                        >Bayar ➡</span
                    >
                </button>
            </div>
        </div>

        <div
            :class="[
                'lg:static lg:w-[400px] lg:border-l lg:border-gray-200 dark:lg:border-gray-700',
                'fixed inset-0 z-40 bg-white dark:bg-gray-800 transition-transform duration-300 ease-in-out flex flex-col h-full',
                showMobileCart
                    ? 'translate-y-0'
                    : 'translate-y-full lg:translate-y-0',
            ]"
        >
            <div
                class="flex items-center justify-between px-5 py-3 border-b shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 lg:bg-white lg:dark:bg-gray-800"
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
                        class="text-[10px] text-red-500 underline ml-2"
                    >
                        (Reset)
                    </button>
                </div>
                <button
                    @click="showMobileCart = false"
                    class="lg:hidden p-1.5 bg-gray-200 dark:bg-gray-700 rounded-full"
                >
                    ✕
                </button>
            </div>

            <div
                class="px-5 py-2 bg-white border-b shrink-0 dark:border-gray-700 dark:bg-gray-800"
            >
                <div v-if="!selectedMember" class="relative">
                    <input
                        v-model="memberSearch"
                        type="text"
                        placeholder="Scan Kartu / Cari Member..."
                        class="w-full p-2 text-xs transition border border-gray-200 rounded-lg dark:border-gray-600 dark:bg-gray-900 dark:text-white focus:ring-lime-500"
                    />

                    <div
                        v-if="filteredCustomers.length"
                        class="absolute left-0 z-50 w-full mt-1 bg-white border rounded-lg shadow-lg top-full dark:bg-gray-800 dark:border-gray-600"
                    >
                        <div
                            v-for="c in filteredCustomers"
                            :key="c.id"
                            @click="handleSelectMember(c)"
                            class="p-2 text-xs text-gray-800 border-b cursor-pointer hover:bg-lime-50 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-200"
                        >
                            <b>{{ c.name }}</b> ({{ c.phone }})
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex justify-between p-2 border rounded-lg bg-lime-50 dark:bg-gray-700 border-lime-200 dark:border-gray-600"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="flex items-center justify-center w-6 h-6 text-xs font-bold text-white rounded-full bg-lime-500"
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
                                class="text-[10px] text-gray-500 dark:text-gray-300"
                            >
                                {{ selectedMember.member_code }}
                            </div>
                        </div>
                    </div>
                    <button
                        @click="handleSelectMember(null)"
                        class="text-xs text-red-500 hover:text-red-700"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div
                class="flex-1 p-4 space-y-3 overflow-y-auto bg-white custom-scroll dark:bg-gray-800"
            >
                <div
                    v-if="form.items.length === 0"
                    class="flex flex-col items-center justify-center h-full text-gray-300 dark:text-gray-600"
                >
                    <span class="text-4xl opacity-50">🛍️</span
                    ><span class="mt-2 text-xs">Keranjang Kosong</span>
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="flex gap-3 pb-2 border-b dark:border-gray-700"
                >
                    <div
                        class="w-12 h-12 overflow-hidden bg-gray-100 rounded-md dark:bg-gray-700 shrink-0"
                    >
                        <img
                            v-if="item.image"
                            :src="'/storage/' + item.image"
                            class="object-cover w-full h-full"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center text-[8px] text-gray-400"
                        >
                            NO IMG
                        </div>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-start justify-between mb-1">
                            <h4
                                class="w-3/4 text-xs font-bold text-gray-800 dark:text-gray-200 line-clamp-1"
                            >
                                {{ item.name }}
                            </h4>
                            <span
                                class="text-xs font-bold text-lime-600 dark:text-lime-400"
                                >{{ rp(item.subtotal) }}</span
                            >
                        </div>

                        <div class="flex items-center justify-between mt-1">
                            <div class="flex items-center gap-1 group/price">
                                <span class="text-[10px] text-gray-400">@</span>
                                <input
                                    type="number"
                                    v-model="item.selling_price"
                                    class="w-20 p-0 px-1 text-xs font-medium text-left text-gray-600 bg-transparent border-b border-gray-300 border-dashed border-none rounded hover:bg-gray-50 dark:hover:bg-gray-700 dark:text-gray-300 focus:ring-0"
                                    @focus="$event.target.select()"
                                    @input="calculateSubtotal(item)"
                                />
                            </div>

                            <div
                                class="flex items-center bg-gray-100 dark:bg-gray-700 rounded p-0.5"
                            >
                                <button
                                    @click="updateQty(index, -1)"
                                    class="w-6 h-6 text-xs font-bold text-gray-600 transition bg-white rounded shadow dark:bg-gray-600 dark:text-white hover:bg-red-100 dark:hover:bg-red-900 hover:text-red-500"
                                >
                                    -
                                </button>
                                <span
                                    class="w-8 text-xs font-bold text-center text-gray-800 dark:text-white"
                                    >{{ item.quantity }}</span
                                >
                                <button
                                    @click="updateQty(index, 1)"
                                    class="w-6 h-6 text-xs font-bold text-white transition rounded shadow bg-lime-500 hover:bg-lime-600"
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="removeItem(index)"
                        class="self-center px-1 text-gray-300 hover:text-red-500"
                    >
                        ✕
                    </button>
                </div>
            </div>

            <div
                class="shrink-0 p-4 bg-gray-50 dark:bg-gray-900 border-t dark:border-gray-700 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] z-10"
            >
                <div class="flex gap-2 mb-3 overflow-x-auto scrollbar-hide">
                    <button
                        v-for="method in ['cash', 'transfer', 'qris']"
                        :key="method"
                        @click="form.payment_method = method"
                        :class="
                            form.payment_method === method
                                ? 'bg-lime-500 text-white border-lime-500'
                                : 'bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700'
                        "
                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase border transition flex-1 text-center whitespace-nowrap shadow-sm"
                    >
                        {{ method }}
                    </button>
                </div>

                <div class="flex justify-between mb-2">
                    <span class="text-xs font-bold text-gray-500"
                        >Total Tagihan</span
                    >
                    <span
                        class="text-xl font-black text-gray-900 dark:text-white"
                        >{{ rp(grandTotal) }}</span
                    >
                </div>

                <div class="relative mb-3">
                    <label class="text-[10px] font-bold text-gray-400 uppercase"
                        >Uang Diterima</label
                    >
                    <MoneyInput v-model="form.payment_amount" placeholder="0" />
                </div>

                <div
                    v-if="form.payment_amount > 0"
                    class="flex justify-between mb-3 text-sm font-bold"
                >
                    <span
                        :class="
                            form.payment_amount < grandTotal
                                ? 'text-red-500'
                                : 'text-lime-600'
                        "
                    >
                        {{
                            form.payment_amount < grandTotal
                                ? "Kurang"
                                : "Kembali"
                        }}
                    </span>
                    <span class="text-gray-800 dark:text-white">
                        {{ rp(Math.abs(changeAmount)) }}
                    </span>
                </div>

                <button
                    @click="handleCheckoutClick"
                    :disabled="!form.items.length"
                    class="flex items-center justify-center w-full gap-2 py-3 text-sm font-bold text-white transition-all shadow-lg bg-lime-500 hover:bg-lime-600 rounded-xl shadow-lime-500/30 disabled:opacity-50 active:scale-95"
                >
                    <span>PROSES PEMBAYARAN</span>
                </button>
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
            class="relative z-10 w-full max-w-sm overflow-hidden transition-all transform scale-100 bg-white shadow-2xl dark:bg-gray-800 rounded-2xl"
        >
            <div class="p-6 text-center">
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

                <h3
                    class="mb-2 text-lg font-black text-gray-800 dark:text-white"
                >
                    Simpan Transaksi?
                </h3>
                <p
                    class="pb-4 mb-6 text-sm text-gray-500 border-b dark:text-gray-400 dark:border-gray-700"
                >
                    Total:
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{
                        rp(grandTotal)
                    }}</span
                    ><br />
                    Metode:
                    <span class="font-bold uppercase text-lime-600">{{
                        form.payment_method
                    }}</span>
                </p>

                <div class="flex flex-col gap-3">
                    <button
                        @click="confirmTransaction(true)"
                        :disabled="form.processing"
                        class="flex items-center justify-center w-full gap-2 py-3 font-bold text-white transition bg-gray-900 shadow dark:bg-white dark:text-gray-900 rounded-xl hover:bg-gray-800 dark:hover:bg-gray-100"
                    >
                        <span
                            v-if="form.processing"
                            class="w-4 h-4 border-2 border-current rounded-full animate-spin border-t-transparent"
                        ></span>
                        <svg
                            v-else
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                            ></path>
                        </svg>
                        <span>Simpan & Cetak Struk</span>
                    </button>

                    <button
                        @click="confirmTransaction(false)"
                        :disabled="form.processing"
                        class="w-full py-3 font-bold text-white transition shadow bg-lime-500 rounded-xl hover:bg-lime-600"
                    >
                        Simpan Tanpa Cetak
                    </button>

                    <button
                        @click="showConfirmModal = false"
                        class="mt-2 text-xs text-gray-400 underline"
                    >
                        Batal / Revisi
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
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
