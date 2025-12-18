<script setup>
import { ref, computed } from "vue";
import BottomSheet from "@/Components/BottomSheet.vue"; // Sesuaikan path komponen Anda
import BarcodeScanner from "@/Components/BarcodeScanner.vue"; // Sesuaikan path komponen Anda

// --- PROPS DARI PARENT ---
const props = defineProps({
    invoice: Object, // Data Invoice
    unlinkedItems: Array, // Sisa PO (Purchase Items yang belum link)
    linkedItems: Array, // Barang yang sudah masuk (Pivot Items)
    products: Array, // Master Produk (untuk pencarian barang baru)
    actions: Object, // Kumpulan fungsi dari parent (submitLinkage, addNewSubstituteItem, dll)
});

// --- STATE ---
const activeTab = ref("unlinked");
const showScanner = ref(false); // Kontrol kamera
const showScanModal = ref(false); // Kontrol modal verifikasi
const showSearch = ref(false); // Kontrol overlay pencarian manual
const searchQuery = ref("");

// State Item yang sedang diproses di Modal
const currentItem = ref({
    // Identitas
    product_id: null,
    name: "",
    sku: "",
    unit: "",

    // Data Referensi (Read Only)
    is_po_match: false,
    po_qty: 0,
    po_price: 0,

    // Data Transaksi
    quantity: 0,
    price: 0,

    // Context Actions
    mode: "link", // 'link' | 'create' | 'edit'
    target_id: null, // Bisa purchase_item_id, product_id, atau pivot_id tergantung mode
});

// --- HELPER ---
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);

const invoiceBalance = computed(() => {
    const target = props.invoice.total_amount || 0;
    // Hitung total dari linkedItems (karena parent me-refresh props ini setelah save)
    const current = props.linkedItems.reduce((acc, item) => {
        return acc + (item.quantity || 0) * (item.purchase_price || 0);
    }, 0);
    return target - current;
});

// Filter Pencarian Lokal (Cepat, tanpa request server)
const filteredProducts = computed(() => {
    if (searchQuery.value.length < 2) return [];
    const lower = searchQuery.value.toLowerCase();
    return props.products
        .filter(
            (p) =>
                p.name.toLowerCase().includes(lower) ||
                p.code.toLowerCase().includes(lower)
        )
        .slice(0, 10);
});

// --- CORE LOGIC ---

// 1. Handle Hasil Scan / Klik Produk dari Search
const handleProductSelection = (productData, isFromUnlinkedList = false) => {
    // Variable penampung
    let matchPoItem = null;

    if (isFromUnlinkedList) {
        // Jika diklik langsung dari tab "Sisa PO", datanya adalah purchase_item
        matchPoItem = productData;

        // Mapping data untuk Modal
        currentItem.value = {
            product_id: matchPoItem.product_id,
            name: matchPoItem.product?.name,
            sku: matchPoItem.product?.code || matchPoItem.product?.sku,
            unit: matchPoItem.unit?.name || "Pcs",

            is_po_match: true,
            po_qty: matchPoItem.quantity,
            po_price: matchPoItem.purchase_price,

            quantity: matchPoItem.quantity, // Default isi sesuai PO
            price: matchPoItem.purchase_price,

            mode: "link",
            target_id: matchPoItem.id, // ID Purchase Item (untuk submitLinkage)
        };
    } else {
        // Jika dari Scan / Global Search, datanya adalah master product
        // Cek dulu apakah produk ini ada di Sisa PO (unlinkedItems)
        matchPoItem = props.unlinkedItems.find(
            (u) => u.product_id === productData.id
        );

        if (matchPoItem) {
            // SKENARIO 1: Barang ada di PO (MATCH) -> Link Mode
            currentItem.value = {
                product_id: productData.id,
                name: productData.name,
                sku: productData.code,
                unit: productData.unit?.name || "Pcs",

                is_po_match: true,
                po_qty: matchPoItem.quantity,
                po_price: matchPoItem.purchase_price,

                quantity: matchPoItem.quantity,
                price: matchPoItem.purchase_price,

                mode: "link",
                target_id: matchPoItem.id, // ID Purchase Item
            };
        } else {
            // SKENARIO 2: Barang tidak ada di PO (BARU) -> Create Mode
            currentItem.value = {
                product_id: productData.id,
                name: productData.name,
                sku: productData.code,
                unit: productData.unit?.name || "Pcs",

                is_po_match: false, // Barang Tambahan
                po_qty: 0,
                po_price: 0,

                quantity: 1,
                price: 0, // Harga default 0 karena tidak ada di PO

                mode: "create",
                target_id: productData.id, // ID Product (untuk addNewSubstituteItem)
            };
        }
    }

    // Reset UI
    showSearch.value = false;
    searchQuery.value = "";
    showScanModal.value = true;
};

// 2. Handle Edit Item (Klik dari Tab "Sudah Masuk")
const openEditModal = (linkedItem) => {
    // Cek apakah item ini awalnya dari PO atau tambahan (biasanya ada flag atau po_item_id)
    // Kita asumsikan jika purchase_item_id ada, berarti dari PO
    const isPo = !!linkedItem.purchase_item_id;

    currentItem.value = {
        product_id: linkedItem.product_id,
        name: linkedItem.product?.name,
        sku: linkedItem.product?.code,
        unit: linkedItem.unit?.name,

        is_po_match: isPo,
        po_qty: 0, // Di linked item biasanya kita tidak simpan qty awal PO kecuali dicustom, jd 0 atau ambil dari ref lain
        po_price: 0,

        quantity: linkedItem.quantity,
        price: linkedItem.purchase_price,

        mode: "edit",
        target_id: linkedItem.id, // ID Pivot (untuk update/unlink)
    };
    showScanModal.value = true;
};

// 3. Simpan Data (Routing ke Fungsi Parent yang Sesuai)
const handleSave = () => {
    if (currentItem.value.mode === "edit") {
        // --- MODE EDIT ---
        // Mutasi data lokal (props) agar correctionForm di parent mendeteksi perubahan
        const targetItem = props.linkedItems.find(
            (i) => i.id === currentItem.value.target_id
        );
        if (targetItem) {
            targetItem.quantity = currentItem.value.quantity;
            targetItem.purchase_price = currentItem.value.price;
        }
        // Panggil fungsi parent untuk PUT batch update
        props.actions.saveCorrections();
    } else if (currentItem.value.mode === "link") {
        // --- MODE LINK (Barang PO) ---
        // Panggil submitLinkage dengan ID Purchase Item
        // Note: Qty/Harga akan masuk default sesuai PO dulu.
        // Jika user mengubah Qty/Harga di modal ini, logic idealnya adalah Link dulu -> lalu Edit.
        props.actions.submitLinkage(currentItem.value.target_id);
    } else if (currentItem.value.mode === "create") {
        // --- MODE CREATE (Barang Baru) ---
        // Panggil addNewSubstituteItem dengan Object Product (sesuai spesifikasi parent)
        props.actions.addNewSubstituteItem({ id: currentItem.value.target_id });
    }

    showScanModal.value = false;
};

// 4. Handle Unlink (Hapus Link)
const handleUnlink = () => {
    if (confirm("Lepaskan item ini dari invoice?")) {
        // Panggil submitUnlinkage dengan ID Pivot
        props.actions.submitUnlinkage(currentItem.value.target_id);
        showScanModal.value = false;
    }
};

// 5. Callback dari BarcodeScanner
const onScanResult = (decodedText) => {
    showScanner.value = false;
    // Masukkan ke search query & buka overlay search
    // Logic filteredProducts otomatis jalan mencari produk yang cocok
    searchQuery.value = decodedText;
    showSearch.value = true;
};

// header
// Hitung Realisasi (Total item yang sudah discan/link)
const totalScanned = computed(() => {
    return props.linkedItems.reduce((acc, item) => {
        return acc + (item.quantity || 0) * (item.purchase_price || 0);
    }, 0);
});

const isReadyToValidate = computed(() => {
    // Balance 0 (toleransi floating point < 100 perak) DAN ada item yg discan
    return Math.abs(invoiceBalance.value) < 100 && props.linkedItems.length > 0;
});

// Hitung Persentase Progress
const progressPercentage = computed(() => {
    const target = props.invoice.total_amount || 1; // Hindari bagi 0
    let percent = (totalScanned.value / target) * 100;
    return Math.min(Math.max(percent, 0), 100); // Clamp 0-100%
});
</script>

<template>
    <div
        class="relative min-h-screen font-sans bg-gray-50 dark:bg-gray-950 pb-28"
    >
        <header
            class="sticky top-0 z-30 bg-white border-b border-gray-100 shadow-lg dark:bg-gray-900 shadow-gray-200/50 dark:shadow-none dark:border-gray-800 rounded-b-3xl"
        >
            <div class="px-5 pt-5 pb-6">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="flex items-start">
                            <button
                                @click="$emit('back')"
                                class="p-2 mr-3 -ml-2 text-gray-400 transition-colors rounded-full hover:text-gray-800 hover:bg-gray-100 dark:text-gray-500 dark:hover:text-white dark:hover:bg-gray-800"
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
                                        d="M15 19l-7-7 7-7"
                                    />
                                </svg>
                            </button>
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    :class="[
                                        'w-1.5 h-8 rounded-full',
                                        isReadyToValidate
                                            ? 'bg-lime-500 animate-pulse'
                                            : 'bg-gray-300 dark:bg-gray-700',
                                    ]"
                                ></span>

                                <h1
                                    class="text-xl font-black tracking-tight text-gray-800 dark:text-white"
                                >
                                    #{{ invoice.invoice_number }}
                                </h1>
                            </div>
                        </div>
                        <div
                            class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 pl-3.5"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3.5 w-3.5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-2 2H6a1 1 0 01-2-2V4zm2 0h8v12H6V4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <p
                                class="text-xs font-medium truncate max-w-[150px]"
                            >
                                {{ invoice.supplier?.name || "Supplier Umum" }}
                            </p>
                        </div>
                    </div>

                    <div class="text-right">
                        <span
                            :class="[
                                'px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border',
                                invoice.status === 'validated'
                                    ? 'bg-teal-50 text-teal-700 border-teal-200'
                                    : 'bg-orange-50 text-orange-700 border-orange-200',
                            ]"
                        >
                            {{ invoice.status }}
                        </span>
                        <p class="text-[10px] text-gray-400 mt-1">
                            Jatuh Tempo:
                            <span class="font-bold text-red-500">{{
                                actions.formatTanggal(invoice.due_date)
                            }}</span>
                        </p>
                    </div>
                </div>

                <div
                    class="relative h-2 mb-4 overflow-hidden bg-gray-100 rounded-full dark:bg-gray-800"
                >
                    <div
                        class="absolute top-0 left-0 h-full transition-all duration-500 ease-out rounded-full"
                        :class="
                            isReadyToValidate
                                ? 'bg-lime-500 shadow-[0_0_10px_rgba(132,204,22,0.6)]'
                                : 'bg-blue-500'
                        "
                        :style="{ width: `${progressPercentage}%` }"
                    ></div>
                </div>

                <div class="grid grid-cols-2 gap-3 h-[72px]">
                    <div
                        class="flex flex-col justify-center p-3 border border-gray-100 bg-gray-50 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
                    >
                        <p
                            class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-0.5"
                        >
                            Total Nota
                        </p>
                        <p
                            class="text-sm font-bold text-gray-800 truncate dark:text-gray-200"
                        >
                            {{ rp(invoice.total_amount) }}
                        </p>
                    </div>

                    <transition name="fade" mode="out-in">
                        <button
                            v-if="
                                isReadyToValidate &&
                                invoice.status !== 'validated'
                            "
                            @click="actions.validateInvoice"
                            class="relative flex items-center justify-between w-full h-full px-4 overflow-hidden text-white transition-all shadow-lg bg-lime-500 hover:bg-lime-600 active:scale-95 rounded-2xl shadow-lime-500/40 group"
                        >
                            <div
                                class="absolute inset-0 transition-transform duration-300 translate-y-full bg-white/10 group-hover:translate-y-0"
                            ></div>

                            <div class="relative z-10 text-left">
                                <p
                                    class="text-[10px] font-bold opacity-90 uppercase tracking-wider"
                                >
                                    Data Cocok
                                </p>
                                <p class="text-sm font-black leading-none">
                                    VALIDASI
                                </p>
                            </div>
                            <div
                                class="bg-white/20 p-1.5 rounded-full relative z-10 group-hover:translate-x-1 transition-transform"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="w-5 h-5"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>

                        <div
                            v-else
                            :class="[
                                'w-full h-full p-3 rounded-2xl border flex flex-col justify-center relative overflow-hidden',
                                Math.abs(invoiceBalance) < 100
                                    ? 'bg-teal-50 border-teal-200 text-teal-800' // Sudah Validated
                                    : 'bg-white dark:bg-gray-800 border-red-100 text-red-600', // Belum Balance
                            ]"
                        >
                            <div class="relative z-10">
                                <p
                                    class="text-[10px] uppercase font-bold tracking-wider mb-0.5 opacity-70"
                                >
                                    {{
                                        Math.abs(invoiceBalance) < 100
                                            ? "Status"
                                            : "Selisih"
                                    }}
                                </p>
                                <p class="text-sm font-black truncate">
                                    {{
                                        Math.abs(invoiceBalance) < 100
                                            ? "SELESAI"
                                            : rp(invoiceBalance)
                                    }}
                                </p>
                            </div>

                            <svg
                                v-if="Math.abs(invoiceBalance) >= 100"
                                xmlns="http://www.w3.org/2000/svg"
                                class="absolute w-10 h-10 -right-2 -bottom-2 opacity-10"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="absolute w-10 h-10 -right-2 -bottom-2 opacity-10"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                    </transition>
                </div>
            </div>
        </header>

        <div
            class="px-4 py-3 sticky top-[108px] z-20 bg-gray-50/95 dark:bg-gray-950/95 backdrop-blur-sm"
        >
            <div class="flex p-1 bg-gray-200 rounded-lg dark:bg-gray-800">
                <button
                    @click="activeTab = 'unlinked'"
                    :class="[
                        'flex-1 py-2 text-xs font-bold rounded-md transition',
                        activeTab === 'unlinked'
                            ? 'bg-white dark:bg-gray-700 shadow text-gray-800 dark:text-white'
                            : 'text-gray-500',
                    ]"
                >
                    Sisa PO ({{ unlinkedItems.length }})
                </button>
                <button
                    @click="activeTab = 'linked'"
                    :class="[
                        'flex-1 py-2 text-xs font-bold rounded-md transition',
                        activeTab === 'linked'
                            ? 'bg-white dark:bg-gray-700 shadow text-lime-600 dark:text-lime-400'
                            : 'text-gray-500',
                    ]"
                >
                    Sudah Masuk ({{ linkedItems.length }})
                </button>
            </div>
        </div>

        <div class="px-4 space-y-3">
            <div v-if="activeTab === 'unlinked'">
                <div
                    v-if="unlinkedItems.length === 0"
                    class="flex flex-col items-center py-10 text-sm text-center text-gray-400"
                >
                    <svg
                        class="w-12 h-12 mb-2 text-gray-300"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                    <p>Semua item PO sudah ditemukan!</p>
                </div>

                <div
                    v-for="item in unlinkedItems"
                    :key="item.id"
                    @click="handleProductSelection(item, true)"
                    class="bg-white dark:bg-gray-900 p-3 rounded-xl border border-gray-100 dark:border-gray-800 flex justify-between items-center opacity-75 hover:opacity-100 transition cursor-pointer active:scale-[0.99]"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-10 h-10 text-xs font-bold text-gray-400 bg-gray-100 border border-gray-200 rounded dark:bg-gray-800 dark:border-gray-700"
                        >
                            {{
                                item.product?.name
                                    ?.substring(0, 2)
                                    .toUpperCase()
                            }}
                        </div>
                        <div>
                            <h4
                                class="text-sm font-bold text-gray-800 dark:text-gray-100"
                            >
                                {{ item.product?.name }}
                            </h4>
                            <p class="text-xs text-gray-400">
                                Order: <b>{{ item.quantity }}</b>
                                {{ item.unit?.name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="activeTab === 'linked'">
                <div
                    v-if="linkedItems.length === 0"
                    class="py-10 text-sm text-center text-gray-400"
                >
                    <p>Belum ada item yang di-scan.</p>
                </div>

                <div
                    v-for="item in linkedItems"
                    :key="item.id"
                    @click="openEditModal(item)"
                    class="bg-white dark:bg-gray-900 mb-1 p-3 rounded-xl border border-lime-200 dark:border-lime-900/50 relative overflow-hidden active:scale-[0.98] transition cursor-pointer shadow-sm"
                >
                    <div
                        class="absolute top-0 bottom-0 left-0 w-1 bg-lime-500"
                    ></div>
                    <div class="flex items-start justify-between pl-2">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <h4
                                    class="text-sm font-bold text-gray-800 dark:text-white"
                                >
                                    {{ item.product?.name }}
                                </h4>
                                <span
                                    v-if="!item.purchase_item_id"
                                    class="text-[9px] px-1.5 rounded bg-purple-100 text-purple-600 font-bold border border-purple-200"
                                    >TAMBAHAN</span
                                >
                            </div>
                            <div class="flex gap-3 text-xs">
                                <span class="text-gray-500"
                                    >Qty:
                                    <b
                                        class="text-gray-800 dark:text-gray-200"
                                        >{{ item.quantity }}</b
                                    ></span
                                >
                                <span class="text-gray-500"
                                    >@
                                    <b
                                        class="text-gray-800 dark:text-gray-200"
                                        >{{ rp(item.purchase_price) }}</b
                                    ></span
                                >
                            </div>
                        </div>
                        <div class="text-gray-300">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="showSearch"
            class="fixed inset-0 z-40 p-4 pt-20 bg-white dark:bg-gray-900 animate-fade-in"
        >
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Ketik nama / kode (atau hasil scan)..."
                    class="w-full py-3 pl-4 pr-10 bg-gray-100 border-none dark:bg-gray-800 rounded-xl focus:ring-2 focus:ring-lime-500 dark:text-white"
                    autoFocus
                />
                <button
                    @click="showSearch = false"
                    class="absolute text-gray-400 -translate-y-1/2 right-3 top-1/2"
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
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <div class="mt-4 space-y-2 overflow-y-auto max-h-[80vh]">
                <p
                    v-if="searchQuery.length < 2"
                    class="mt-10 text-sm text-center text-gray-400"
                >
                    Ketik minimal 2 huruf...
                </p>

                <div
                    v-else-if="filteredProducts.length === 0"
                    class="mt-10 text-sm text-center text-gray-400"
                >
                    Produk tidak ditemukan di database.
                </div>

                <div
                    v-for="prod in filteredProducts"
                    :key="prod.id"
                    @click="handleProductSelection(prod, false)"
                    class="flex items-center justify-between p-3 transition bg-white border border-gray-100 dark:bg-gray-800 rounded-xl dark:border-gray-700 active:bg-lime-50 dark:active:bg-lime-900/20"
                >
                    <div>
                        <h4
                            class="text-sm font-bold text-gray-800 dark:text-white"
                        >
                            {{ prod.name }}
                        </h4>
                        <p class="text-xs text-gray-500">{{ prod.code }}</p>
                    </div>
                    <span
                        v-if="
                            unlinkedItems.find((u) => u.product_id === prod.id)
                        "
                        class="text-[9px] bg-lime-100 text-lime-700 px-2 py-0.5 rounded font-bold"
                    >
                        MATCH PO
                    </span>
                </div>
            </div>
        </div>

        <BarcodeScanner
            v-if="showScanner"
            @result="onScanResult"
            @close="showScanner = false"
        />

        <div
            class="fixed z-30 w-full max-w-xs px-4 -translate-x-1/2 bottom-6 left-1/2"
            v-if="!showSearch && !showScanner"
        >
            <div class="flex gap-3">
                <button
                    @click="showSearch = true"
                    class="flex-1 py-4 font-bold text-gray-800 bg-white border border-gray-100 rounded-full shadow-lg dark:bg-gray-800 dark:text-white dark:border-gray-700"
                >
                    CARI
                </button>
                <button
                    @click="showScanner = true"
                    class="flex-[2] flex items-center justify-center gap-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 py-4 rounded-full shadow-xl font-bold text-lg active:scale-95 transition"
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
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                        />
                    </svg>
                    SCAN
                </button>
            </div>
        </div>

        <BottomSheet
            :show="showScanModal"
            @close="showScanModal = false"
            title="Verifikasi Barang"
        >
            <div class="space-y-5">
                <div class="flex gap-4">
                    <div
                        class="flex items-center justify-center font-bold text-gray-400 bg-gray-100 border border-gray-200 rounded-lg w-14 h-14 dark:bg-gray-800 dark:border-gray-700 shrink-0"
                    >
                        {{ currentItem.name.substring(0, 2).toUpperCase() }}
                    </div>
                    <div class="flex-1">
                        <span
                            :class="[
                                'text-[10px] px-2 py-0.5 rounded border font-bold uppercase inline-block mb-1',
                                currentItem.is_po_match
                                    ? 'bg-lime-50 text-lime-700 border-lime-200 dark:bg-lime-900/20 dark:text-lime-400'
                                    : 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400',
                            ]"
                        >
                            {{
                                currentItem.is_po_match
                                    ? "Sesuai Purchase Order"
                                    : "Produk Tambahan / Baru"
                            }}
                        </span>
                        <h3
                            class="text-lg font-bold leading-tight text-gray-800 dark:text-white"
                        >
                            {{ currentItem.name }}
                        </h3>
                        <p class="text-xs text-gray-500">
                            {{ currentItem.sku }}
                        </p>
                    </div>
                </div>

                <div
                    class="p-1 border border-gray-200 bg-gray-50 dark:bg-gray-800/50 rounded-xl dark:border-gray-700"
                >
                    <div
                        class="grid grid-cols-2 text-center text-[10px] font-bold text-gray-400 border-b border-gray-200 dark:border-gray-700 pb-1 mb-2 tracking-wider"
                    >
                        <div>DATA PO</div>
                        <div class="text-lime-600 dark:text-lime-400">
                            INPUT FISIK
                        </div>
                    </div>

                    <div class="grid items-center grid-cols-2 mb-4">
                        <div
                            class="text-center border-r border-gray-200 dark:border-gray-700"
                        >
                            <span
                                v-if="currentItem.is_po_match"
                                class="text-xl font-bold text-gray-400"
                                >{{ currentItem.po_qty }}</span
                            >
                            <span v-else class="text-xl font-bold text-gray-300"
                                >-</span
                            >
                            <span
                                class="text-[10px] text-gray-400 block uppercase"
                                >{{ currentItem.unit }}</span
                            >
                        </div>
                        <div class="px-2">
                            <input
                                v-model.number="currentItem.quantity"
                                type="number"
                                class="w-full py-2 text-2xl font-bold text-center text-gray-900 bg-white border rounded-lg dark:bg-gray-900 border-lime-300 dark:border-lime-700 focus:ring-2 focus:ring-lime-500 dark:text-white"
                            />
                        </div>
                    </div>

                    <div class="grid items-center grid-cols-2 pb-2">
                        <div
                            class="px-1 text-center border-r border-gray-200 dark:border-gray-700"
                        >
                            <span
                                v-if="currentItem.is_po_match"
                                class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                >{{ rp(currentItem.po_price) }}</span
                            >
                            <span
                                v-else
                                class="text-sm font-medium text-gray-300"
                                >-</span
                            >
                            <span
                                class="text-[9px] text-gray-400 block uppercase"
                                >Harga Satuan</span
                            >
                        </div>
                        <div class="px-2">
                            <div class="relative">
                                <span
                                    class="absolute text-xs font-bold text-gray-400 -translate-y-1/2 left-3 top-1/2"
                                    >Rp</span
                                >
                                <input
                                    v-model.number="currentItem.price"
                                    type="number"
                                    class="w-full py-2 pl-8 pr-3 text-sm font-bold text-right text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-900 dark:border-gray-600 focus:ring-lime-500 focus:border-lime-500 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <p
                    v-if="currentItem.mode !== 'edit'"
                    class="text-[10px] text-gray-400 text-center italic"
                >
                    * Perubahan Qty/Harga untuk link baru akan disimpan sebagai
                    default. Anda bisa mengeditnya kembali di tab 'Sudah Masuk'.
                </p>
            </div>

            <template #footer>
                <div class="flex gap-3">
                    <button
                        v-if="currentItem.mode === 'edit'"
                        @click="handleUnlink"
                        class="p-3.5 bg-red-50 text-red-600 rounded-xl border border-red-100 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400"
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
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                    <button
                        @click="showScanModal = false"
                        class="flex-1 py-3.5 bg-gray-100 text-gray-600 font-bold rounded-xl dark:bg-gray-800 dark:text-gray-300"
                    >
                        Batal
                    </button>
                    <button
                        @click="handleSave"
                        class="flex-[2] py-3.5 bg-lime-500 text-white font-bold rounded-xl shadow-lg shadow-lime-500/30 active:scale-[0.98] transition"
                    >
                        {{
                            currentItem.mode === "edit"
                                ? "Simpan Perubahan"
                                : "Tautkan Barang"
                        }}
                    </button>
                </div>
            </template>
        </BottomSheet>
    </div>
</template>

<style scoped>
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
