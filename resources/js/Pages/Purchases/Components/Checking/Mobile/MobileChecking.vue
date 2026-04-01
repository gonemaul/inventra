<script setup>
import { defineAsyncComponent } from "vue";
import { ref, computed } from "vue";
import BottomSheet from "@/Components/BottomSheet.vue"; // Sesuaikan path komponen Anda
import BarcodeScanner from "@/Components/BarcodeScanner.vue";
import UnlinkMobile from "./UnlinkMobile.vue";
import LinkedMobile from "./LinkedMobile.vue";
import SearchMobile from "./SearchMobile.vue";
import HeaderMobile from "./HeaderMobile.vue";
import InputRupiah from "@/Components/InputRupiah.vue";
import { useActionLoading } from "@/Composable/useActionLoading";

// --- PROPS DARI PARENT ---
const props = defineProps({
    purchase: Object,
    invoice: Object, // Data Invoice
    unlinkedItems: Array, // Sisa PO (Purchase Items yang belum link)
    linkedItems: Array, // Barang yang sudah masuk (Pivot Items)
    actions: Object, // Kumpulan fungsi dari parent (submitLinkage, addNewSubstituteItem, dll)
    searchResults: Array,
    isSearching: Boolean,
});

// --- STATE ---
const { isActionLoading } = useActionLoading();
const activeTab = ref("unlinked");
const showScanner = ref(false); // Kontrol kamera
const showScanModal = ref(false); // Kontrol modal verifikasi
const showSearch = ref(false); // Kontrol overlay pencarian manual
const showValidationSheet = ref(false); // Kontrol konfirmasi BottomSheet
const searchQuery = ref("");

// --- SMART SEARCH (Persistent) ---
const searchKeywords = ref("");
const searchWords = computed(() => searchKeywords.value.toLowerCase().split(' ').filter(w => w));

const matchesSearch = (productName, productCode) => {
    if (searchWords.value.length === 0) return true;
    const nameLower = (productName || '').toLowerCase();
    const codeLower = (productCode || '').toLowerCase();
    const textToSearch = nameLower + ' ' + codeLower;
    
    // Semua kata (potongan kata) harus ada
    return searchWords.value.every(word => textToSearch.includes(word));
};

const localSearchQuery = ref("");

const filteredUnlinked = computed(() => {
    if (!props.unlinkedItems) return null;
    return props.unlinkedItems.filter(item => 
        matchesSearch(item.product?.name, item.product?.code)
    );
});

// Sort by updated_at (descending) so newest linked is at the top
const filteredLinked = computed(() => {
    if (!props.linkedItems) return [];
    let items = [...props.linkedItems].sort((a, b) => new Date(b.updated_at) - new Date(a.updated_at));

    if (localSearchQuery.value) {
        const queryLower = localSearchQuery.value.toLowerCase().trim().split(' ').filter(word => word.length > 0);
        items = items.filter(item => {
            const itemName = (item.product?.name || '').toLowerCase();
            const itemCode = (item.product?.code || '').toLowerCase();
            const fullText = itemName + ' ' + itemCode;
            
            return queryLower.every(word => fullText.includes(word));
        });
    }
    
    return items;
});

const blurSearchInput = () => {
    if (document.activeElement && document.activeElement.tagName === 'INPUT') {
        document.activeElement.blur();
    }
};

// State Item yang sedang diproses di Modal
const currentItem = ref({
    // Identitas
    type: "", // 'link' | 'create' | 'edit'
    id: null, // purchase_item_id / pivot_id / null
    product_id: null,

    name: "",
    code: "",
    unit: "",

    // Data Referensi (Read Only)
    is_po_match: false,
    po_qty: 0,
    po_price: 0,

    // Data Transaksi
    quantity: 0,
    price: 0,
    total: 0,
});

// --- CORE LOGIC ---
// Dari List Sisa PO (Komponen UnlinkedMobile)
const onSelectUnlinked = (item) => {
    currentItem.value = {
        type: "link",
        id: item.id,
        product_id: item.product_id,
        name: item.product_snapshot?.name,
        code: item.product_snapshot?.code,
        unit: item.product_snapshot.unit,
        is_po_match: true,
        po_qty: item.product_snapshot.quantity,
        po_price: item.product_snapshot.purchase_price,
        quantity: item.quantity,
        price: item.purchase_price,
        total: item.subtotal,
        image_url: item.product.image_url,
    };
    showScanModal.value = true;
};
// Dari Search / Scan
const onSelectSearch = (product) => {
    // Cek dulu, apakah barang ini sebenarnya ada di Sisa PO?
    const match = props.unlinkedItems?.find((u) => u.product_id === product.id);

    if (match) {
        // Jika ADA, arahkan ke logic Link
        onSelectUnlinked(match);
    } else {
        // Jika TIDAK ADA, arahkan ke logic Create
        currentItem.value = {
            type: "create",
            id: null,
            product_id: product.id,
            name: product.name,
            code: product.code,
            unit: product.unit?.name || "Pcs",
            is_po_match: false,
            po_qty: 0,
            po_price: 0,
            quantity: 1,
            price: product.purchase_price,
            total: product.purchase_price,
        };
        showScanModal.value = true;
    }
    showSearch.value = false;
    searchQuery.value = "";
};
// Dari List Sudah Masuk (Linked)
const onSelectLinked = (item) => {
    if (props.purchase.status !== "checking") {
        alert(
            "Purchase Order ini sudah selesai diperiksa. Tidak dapat menambahkan atau mengedit item."
        );
        return;
    }
    if (props.invoice.status === "validated") {
        alert(
            "Invoice ini sudah divalidasi. Tidak dapat menambahkan atau mengedit item."
        );
        return;
    }
    currentItem.value = {
        type: "edit",
        id: item.id,
        product_id: item.product_id,
        name: item.product_snapshot?.name,
        code: item.product_snapshot?.code,
        unit: item.product_snapshot?.unit,
        is_po_match: item.product_snapshot.quantity > 0,
        po_qty: item.product_snapshot.quantity,
        po_price: item.product_snapshot.purchase_price,
        quantity: item.quantity,
        price: item.purchase_price,
        total: item.subtotal,
    };
    showScanModal.value = true;
};
const handleMainSave = () => {
    isActionLoading.value = true;
    if (currentItem.value.type === "link") processLink();
    else if (currentItem.value.type === "create") processCreate();
    else if (currentItem.value.type === "edit") processEdit();

    showScanModal.value = false;
};

// A. TYPE = LINK (Barang Sisa PO)
const processLink = async () => {
    const item = currentItem.value;
    // Rule: id = purchase_item_id
    // Kirim ID Purchase Item ke Parent
    try {
        await props.actions.submitLinkage(item.id, item.quantity, item.price);
    } catch (error) {
        console.error("Gagal link", error);
    } finally {
        isActionLoading.value = false; // Set loading OFF (pasti jalan)
    }
};

// B. TYPE = CREATE (Barang Baru)
const processCreate = async () => {
    // Rule: id = null, product_id = ada
    // Kirim Object Product ({ id: ... }) ke Parent
    const item = currentItem.value;
    try {
        await props.actions.addNewSubstituteItem({
            id: item.product_id,
            newQty: item.quantity,
            newPrice: item.price,
        });
    } catch (error) {
        console.error("Gagal membuat", error);
    } finally {
        isActionLoading.value = false; // Set loading OFF (pasti jalan)
    }
};

// C. TYPE = EDIT (Barang Sudah Masuk)
const processEdit = async () => {
    // Rule: id = pivot_id
    const item = currentItem.value;
    // 1. Update data di props lokal (linkedItems) agar reaktif terbaca parent
    const target = props.linkedItems.find((i) => i.id === currentItem.value.id);
    if (target) {
        target.quantity = currentItem.value.quantity;
        target.purchase_price = currentItem.value.price;
    }
    // 2. Panggil fungsi batch update parent
    try {
        await props.actions.saveCorrections({
            id: item.id,
            quantity: item.quantity,
            purchase_price: item.price,
        });
    } catch (error) {
        console.error("Gagal edit", error);
    } finally {
        isActionLoading.value = false; // Set loading OFF (pasti jalan)
    }
};

// D. Type Handle Unlink (Hapus Link)
const handleUnlink = async () => {
    if (confirm("Lepaskan item ini dari invoice?")) {
        // Panggil submitUnlinkage dengan ID Pivot
        try {
            await props.actions.submitUnlinkage(currentItem.value.id);
        } catch (error) {
            console.error("Gagal unlink", error);
        } finally {
            isActionLoading.value = false; // Set loading OFF (pasti jalan)
        }
        showScanModal.value = false;
    }
};


// 5. Callback dari BarcodeScanner
const onScanResult = (decodedText) => {
    showScanner.value = false;
    // code only
    const match = props.unlinkedItems?.find((u) => u.code === decodedText);
    if (match) {
        onSelectUnlinked(match);
    } else {
        searchKeywords.value = decodedText;
        props.actions.handleSearchNewItem(decodedText);
    }
};

// 6. Helper untuk menentukan status produk dari hasil pencarian
const getProductStatus = (product) => {
    // Check if it's currently linked (exists in linkedItems array)
    const isLinked = props.linkedItems?.some(item => item.product_id === product.id);
    if (isLinked) return { label: 'Tertaut', color: 'bg-green-100 text-green-700 border-green-200' };

    // Check if it's currently unlinked (in the PO but not linked)
    const isUnlinked = props.unlinkedItems?.some(item => item.product_id === product.id);
    if (isUnlinked) return { label: 'Sisa PO', color: 'bg-blue-100 text-blue-700 border-blue-200' };

    // Otherwise it's outside the PO (baru / substitute)
    return { label: 'Luar PO', color: 'bg-purple-100 text-purple-700 border-purple-200' };
};
// 1. Auto Calculate Total
// Writable Computed: Bisa membaca Total, dan jika diedit, dia mengupdate Harga Satuan
const calculatedTotal = computed(() => {
    return (currentItem.value.quantity || 0) * (currentItem.value.price || 0);
});

// 2. Fungsi dipanggil SAAT user SELESAI ketik Total (Enter/Blur)
const handleTotalChange = (event) => {
    // Ambil angka dari input
    const newTotal = parseFloat(event) || 0;
    const qty = currentItem.value.quantity || 0;

    // Cegah pembagian nol
    if (qty > 0) {
        // Rumus: Harga = Total / Qty
        const newPrice = newTotal / qty;

        // Simpan sebagai nilai pecahan (float) agar ketika dikalikan ulang hasilnya pas 100%
        // tanpa ada selisih pembulatan (misal 10000/3 = 3333.33)
        currentItem.value.price = newPrice;
    } else {
        // Jika Qty 0, anggap harga = total
        currentItem.value.price = newTotal;
        currentItem.value.quantity = 1; // Opsional: Auto set qty 1 jika 0
    }

    // Paksa update tampilan input agar sinkron dengan hasil hitungan
    // (Misal user ketik 10.000 -> Harga 3.333 -> Total jadi 9.999)
    event = calculatedTotal.value;
};

// 2. Helper Warna Border Qty (Indikator Visual)
const qtyStatusColor = computed(() => {
    // Jika Mode Create (Barang Baru), selalu netral
    if (!currentItem.value.is_po_match)
        return "border-gray-200 focus:border-lime-500";

    // Jika Mode Link/Edit (Bandingkan dengan PO)
    const diff = currentItem.value.quantity - currentItem.value.po_qty;
    if (diff === 0) return "border-lime-500 bg-lime-50 text-lime-700"; // Cocok
    if (diff < 0) return "border-red-300 bg-red-50 text-red-600"; // Kurang
    return "border-yellow-300 bg-yellow-50 text-yellow-700"; // Lebih
});

// --- STATS LOGIC (Moved from HeaderMobile) ---
const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const totalScanned = computed(() => {
    return (props.linkedItems || []).reduce((acc, item) => {
        return acc + (item.subtotal || 0);
    }, 0);
});

const invoiceBalance = computed(() => {
    const target = props.invoice.total_amount || 0;
    return target - totalScanned.value;
});

const isReadyToValidate = computed(() => {
    return Math.abs(invoiceBalance.value) < 100 && props.linkedItems.length > 0;
});

const progressPercentage = computed(() => {
    const target = props.invoice.total_amount || 1;
    let percent = (totalScanned.value / target) * 100;
    return Math.min(Math.max(percent, 0), 100);
});

// --- FILTER LOGIC ---
const unlinkedFilter = ref("po"); // 'all' | 'po' | 'bukan_po'

const finalFilteredUnlinked = computed(() => {
    if (activeTab.value !== 'unlinked') return [];
    
    // Jika di filter PO, gunakan pencarian lokal (filteredUnlinked)
    if (unlinkedFilter.value === 'po') {
        return filteredUnlinked.value || [];
    }

    // Jika di filter Bukan PO, list utama dikosongkan (ditampilkan via searchResults di template)
    return [];
});

// 3. Tombol Stepper (+ / -)
const adjustQty = (amount) => {
    let newQty = (currentItem.value.quantity || 0) + amount;
    if (newQty < 0) newQty = 0;
    currentItem.value.quantity = newQty;
};
</script>

<template>
    <div
        class="relative min-h-screen font-sans bg-gray-50 dark:bg-gray-950"
    >
        <!-- 1. TOP HEADER (Supplier & PO - NOT STICKY) -->
        <HeaderMobile
            :purchase="purchase"
            :invoice="invoice"
            :linked-items="linkedItems"
            :validateInvoice="() => showValidationSheet = true"
        />

        <!-- 2. MASTER STICKY CONTROL (Progress, Stats, Tabs, Search, Filter) -->
        <div class="sticky top-0 z-30 bg-gray-50/95 dark:bg-gray-950/95 backdrop-blur-md shadow-sm border-b border-gray-200 dark:border-gray-800 pt-2 pb-3 px-4 space-y-3">
            <!-- Progress Bar -->
            <div class="relative h-2.5 overflow-hidden bg-gray-200 rounded-full dark:bg-gray-800">
                <div
                    class="absolute top-0 left-0 h-full transition-all duration-500 ease-out rounded-full"
                    :class="isReadyToValidate ? 'bg-lime-500 shadow-[0_0_8px_rgba(132,204,22,0.5)]' : 'bg-blue-500'"
                    :style="{ width: `${progressPercentage}%` }"
                ></div>
                <span class="absolute inset-0 flex items-center justify-center text-[8px] font-black tracking-tighter"
                    :class="progressPercentage > 50 ? 'text-white' : 'text-gray-500'"
                >
                    {{ Math.round(progressPercentage) }}% COLLECTED
                </span>
            </div>

            <!-- Stats Summary Row -->
            <div class="flex items-stretch gap-2 h-14">
                <div class="flex flex-col justify-center flex-1 px-3 border border-gray-200 bg-white/50 dark:bg-gray-900/50 rounded-xl dark:border-gray-800">
                    <p class="text-[9px] uppercase font-bold text-gray-400 tracking-wider">Total Nota</p>
                    <p class="text-xs font-black text-gray-800 dark:text-gray-100">{{ formatRupiah(invoice.total_amount) }}</p>
                </div>

                <div :class="[
                    'flex flex-col justify-center flex-1 px-3 border rounded-xl relative overflow-hidden transition-all',
                    Math.abs(invoiceBalance) < 100 
                        ? 'bg-lime-500 border-lime-500 text-white shadow-lg shadow-lime-500/20' 
                        : 'bg-white/50 dark:bg-gray-900/50 border-gray-200 dark:border-gray-800 text-red-500'
                ]">
                    <p class="text-[9px] uppercase font-bold opacity-70 tracking-wider">
                        {{ Math.abs(invoiceBalance) < 100 ? 'Status' : 'Selisih' }}
                    </p>
                    <p class="text-xs font-black truncate">
                        {{ Math.abs(invoiceBalance) < 100 ? 'BALANCE ✓' : formatRupiah(invoiceBalance) }}
                    </p>
                </div>

                <!-- Action Button inside Sticky -->
                <button 
                    v-if="isReadyToValidate && invoice.status !== 'validated'"
                    @click="showValidationSheet = true"
                    class="flex items-center justify-center px-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl shadow-lg active:scale-95 transition-all"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Tab Switcher -->
            <div class="flex p-1 bg-gray-200 rounded-xl dark:bg-gray-800">
                <button
                    @click="activeTab = 'unlinked'"
                    :class="[
                        'flex-1 py-1.5 text-xs font-black rounded-lg transition-all',
                        activeTab === 'unlinked'
                            ? 'bg-white dark:bg-gray-700 shadow-sm text-gray-900 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                    ]"
                >
                    SISA ({{ unlinkedItems?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'linked'"
                    :class="[
                        'flex-1 py-1.5 text-xs font-black rounded-lg transition-all',
                        activeTab === 'linked'
                            ? 'bg-white dark:bg-gray-700 shadow-sm text-gray-900 dark:text-white'
                            : 'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300',
                    ]"
                >
                    MASUK ({{ linkedItems.length }})
                </button>
            </div>

            <!-- Search Bar Block -->
            <div class="flex items-center gap-2">
                <div class="relative flex-grow">
                    <svg
                        class="absolute w-4 h-4 text-gray-400 -translate-y-1/2 left-3 top-1/2"
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

                    <!-- Global / Local Search -->
                    <input
                        v-if="activeTab === 'unlinked'"
                        v-model="searchKeywords"
                        @input="unlinkedFilter === 'bukan_po' ? actions.handleSearchNewItem($event.target.value) : null"
                        type="text"
                        :placeholder="unlinkedFilter === 'po' ? 'Cari di daftar PO...' : 'Cari di katalog global...'"
                        class="w-full pl-9 pr-9 py-2.5 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition-all"
                    />

                    <!-- Local Search (Linked Tab) -->
                    <input
                        v-else-if="activeTab === 'linked'"
                        v-model="localSearchQuery"
                        type="text"
                        placeholder="Cari dalam item tertaut..."
                        class="w-full pl-9 pr-9 py-2.5 text-sm bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white transition-all"
                    />

                    <button
                        v-if="activeTab === 'unlinked' ? searchKeywords : localSearchQuery"
                        @click="activeTab === 'unlinked' ? ((searchKeywords = ''), unlinkedFilter === 'bukan_po' ? actions.handleSearchNewItem('') : null) : (localSearchQuery = '')"
                        class="absolute text-gray-400 -translate-y-1/2 right-3 top-1/2 hover:text-gray-600"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <button
                    @click="showScanner = true"
                    class="p-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-xl shadow-sm active:scale-95 transition-all shrink-0"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                </button>
            </div>

            <!-- Sub Filters (Only for Unlinked) -->
            <div v-if="activeTab === 'unlinked'" class="flex gap-2">
                <button 
                    @click="unlinkedFilter = 'po'; searchKeywords = ''"
                    :class="[
                        'flex-1 py-1.5 px-3 text-[10px] font-bold rounded-full border transition-all',
                        unlinkedFilter === 'po' 
                            ? 'bg-lime-100 border-lime-200 text-lime-700 dark:bg-lime-900/30 dark:border-lime-700 dark:text-lime-400 font-black' 
                            : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 text-gray-400'
                    ]"
                >
                    <i class="fas fa-file-invoice mr-1"></i> PO ITEMS
                </button>
                <button 
                    @click="unlinkedFilter = 'bukan_po'; searchKeywords = ''"
                    :class="[
                        'flex-1 py-1.5 px-3 text-[10px] font-bold rounded-full border transition-all',
                        unlinkedFilter === 'bukan_po' 
                            ? 'bg-purple-100 border-purple-200 text-purple-700 dark:bg-purple-900/30 dark:border-purple-700 dark:text-purple-400 font-black' 
                            : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800 text-gray-400'
                    ]"
                >
                    <i class="fas fa-search-plus mr-1"></i> BUKAN PO
                </button>
            </div>
        </div>

        <!-- 3. LIST CONTENT -->
        <div class="px-4 mt-3 pb-32 space-y-3" @touchmove="blurSearchInput">
            <!-- HASIL PENCARIAN GLOBAL CARD VIEW -->
            <template v-if="activeTab === 'unlinked' && unlinkedFilter === 'bukan_po' && searchKeywords">
                <div v-if="isSearching" class="py-10 text-center">
                    <i class="mb-2 text-2xl text-lime-500 fas fa-spinner fa-spin"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mencari produk katalog...</p>
                </div>
                <div v-else-if="!searchResults?.length" class="py-10 text-center">
                    <i class="mb-2 text-3xl text-gray-300 fas fa-box-open dark:text-gray-600"></i>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada produk ditemukan</p>
                </div>
                <div v-else class="grid grid-cols-2 gap-3 pb-6">
                    <div
                        v-for="res in searchResults"
                        :key="res.id"
                        @click="onSelectSearch(res); searchKeywords = ''; actions.handleSearchNewItem('')"
                        class="relative flex flex-col h-full overflow-hidden transition-all bg-white border border-gray-200 cursor-pointer group dark:bg-gray-900 dark:border-gray-800 rounded-2xl active:scale-95 shadow-sm"
                    >
                        <!-- Card Content -->
                        <div class="relative w-full aspect-square bg-gray-50 dark:bg-gray-800 shrink-0 overflow-hidden">
                            <img
                                v-if="res.image_url"
                                :src="res.image_url"
                                alt="Product Image"
                                class="object-cover w-full h-full transition-transform group-hover:scale-110"
                            />
                            <div v-else class="flex items-center justify-center w-full h-full bg-gray-100 dark:bg-gray-800">
                                <span class="text-2xl font-black text-gray-300 dark:text-gray-700">
                                    {{ res.name?.substring(0, 2).toUpperCase() }}
                                </span>
                            </div>

                            <div class="absolute top-1.5 left-1.5 px-1.5 py-0.5 backdrop-blur-sm text-[9px] font-bold uppercase rounded-md border shadow-sm max-w-[70%] truncate" :class="getProductStatus(res).color">
                                {{ getProductStatus(res).label }}
                            </div>
                        </div>

                        <div class="flex flex-col flex-grow px-2.5 py-2">
                            <h4 class="text-[11px] font-bold leading-tight text-gray-800 dark:text-gray-100 line-clamp-2 min-h-[28px]">
                                {{ res.name }}
                            </h4>
                            <!-- Size & Unit Info -->
                            <div class="mt-1 flex flex-wrap gap-1">
                                <span v-if="res.size" class="px-1 text-[8px] bg-gray-100 dark:bg-gray-800 text-gray-500 rounded border dark:border-gray-700">
                                    {{ res.size?.name }}
                                </span>
                                <span class="px-1 text-[8px] bg-gray-100 dark:bg-gray-800 text-gray-500 rounded border dark:border-gray-700">
                                    {{ res.unit?.name || 'Pcs' }}
                                </span>
                            </div>
                        </div>

                        <!-- Price Info Footer -->
                        <div class="mt-auto border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 p-2 flex items-center justify-between">
                            <span class="text-[8px] font-bold text-gray-400 uppercase tracking-tighter">H.Beli</span>
                            <span class="text-[10px] font-black text-lime-600 dark:text-lime-400">
                                Rp {{ (res.purchase_price || 0).toLocaleString('id-ID') }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- TAMPILAN DEFAULT -->
            <template v-else>
                <div v-if="activeTab === 'unlinked' && unlinkedFilter === 'bukan_po' && !searchKeywords" class="py-12 text-center bg-white dark:bg-gray-900 border-2 border-dashed border-gray-100 dark:border-gray-800 rounded-3xl">
                    <div class="w-16 h-16 bg-purple-50 dark:bg-purple-900/20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">Cari Produk Katalog</h3>
                    <p class="text-[10px] text-gray-500 mt-1 px-8">Gunakan search bar di atas untuk mencari produk yang tidak ada dalam daftar PO asli.</p>
                </div>

                <UnlinkMobile
                    v-else-if="activeTab === 'unlinked'"
                    :unlinked-items="finalFilteredUnlinked"
                    :handleProductSelection="onSelectUnlinked"
                />

                <LinkedMobile
                    v-if="activeTab === 'linked'"
                    :linkedItems="filteredLinked"
                    :openEditModal="onSelectLinked"
                />
            </template>
        </div>

        <BarcodeScanner
            v-if="showScanner"
            @result="onScanResult"
            @close="showScanner = false"
        />

        <BottomSheet
            :show="showScanModal"
            @close="showScanModal = false"
            title="Verifikasi Barang"
        >
            <div class="space-y-6">
                <div
                    class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800"
                >
                    <div
                        class="flex items-center justify-center w-12 h-12 font-bold text-gray-400 border border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-800 dark:border-gray-700 shrink-0"
                    >
                        {{ currentItem.name?.substring(0, 2).toUpperCase() }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <span
                            :class="[
                                'text-[10px] px-2 py-0.5 rounded border font-bold uppercase inline-block mb-1 tracking-wide',
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
                            class="text-lg font-bold leading-tight text-gray-900 truncate dark:text-white"
                        >
                            {{ currentItem.name }}
                        </h3>
                        <p
                            class="text-xs font-medium text-gray-500 truncate dark:text-gray-400"
                        >
                            {{ currentItem.code || "-" }}
                        </p>
                    </div>
                </div>

                <div
                    class="p-1 border border-gray-200 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700"
                >
                    <div
                        class="grid grid-cols-2 pb-2 mb-3 text-center border-b border-gray-200 dark:border-gray-700"
                    >
                        <div
                            class="py-1 text-[10px] font-bold tracking-wider text-gray-400 uppercase"
                        >
                            Data PO
                        </div>
                        <div
                            class="py-1 text-[10px] font-bold tracking-wider uppercase text-lime-600 dark:text-lime-400"
                        >
                            Input Fisik
                        </div>
                    </div>

                    <div class="grid items-center grid-cols-2 px-2 mb-6">
                        <div
                            class="pr-2 text-center border-r border-gray-200 dark:border-gray-700"
                        >
                            <span
                                v-if="currentItem.is_po_match"
                                class="block text-2xl font-bold text-gray-400 dark:text-gray-500"
                            >
                                {{ currentItem.po_qty }}
                            </span>
                            <span
                                v-else
                                class="block text-2xl font-bold text-gray-300 dark:text-gray-600"
                            >
                                -
                            </span>
                            <span
                                class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                            >
                                {{ currentItem.unit }}
                            </span>
                        </div>

                        <div class="pl-2">
                            <div class="flex items-center shadow-sm rounded-xl">
                                <button
                                    @click="adjustQty(-1)"
                                    class="flex items-center justify-center w-10 h-12 text-gray-500 transition bg-white border border-gray-200 rounded-l-xl hover:bg-gray-50 active:bg-gray-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>

                                <input
                                    v-model.number="currentItem.quantity"
                                    type="number"
                                    :class="[
                                        'w-full h-12 text-center font-bold text-xl border-y border-x-0 focus:ring-0 z-10',
                                        qtyStatusColor,
                                    ]"
                                />

                                <button
                                    @click="adjustQty(1)"
                                    class="flex items-center justify-center w-10 h-12 text-gray-500 transition bg-white border border-gray-200 rounded-r-xl hover:bg-gray-50 active:bg-lime-50 active:text-lime-600 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-700"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="w-4 h-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <p
                                v-if="
                                    currentItem.is_po_match &&
                                    currentItem.quantity -
                                        currentItem.po_qty !==
                                        0
                                "
                                :class="[
                                    'text-[10px] text-center font-bold mt-1.5 px-2 py-0.5 rounded-md inline-block w-full',
                                    currentItem.quantity - currentItem.po_qty <
                                    0
                                        ? 'bg-red-50 text-red-600'
                                        : 'bg-yellow-50 text-yellow-600',
                                ]"
                            >
                                {{
                                    currentItem.quantity - currentItem.po_qty >
                                    0
                                        ? "Lebih"
                                        : "Kurang"
                                }}
                                {{
                                    Math.abs(
                                        currentItem.quantity -
                                            currentItem.po_qty
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="grid items-center grid-cols-2 px-2 pb-2 mb-2">
                        <div
                            class="pr-2 text-center border-r border-gray-200 dark:border-gray-700"
                        >
                            <span
                                v-if="currentItem.is_po_match"
                                class="block text-sm font-bold text-gray-500 dark:text-gray-400"
                            >
                                {{ actions.formatRupiah(currentItem.po_price) }}
                            </span>
                            <span
                                v-else
                                class="block text-sm font-bold text-gray-300"
                            >
                                -
                            </span>
                            <span
                                class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider mt-0.5"
                            >
                                Harga Satuan
                            </span>
                        </div>

                        <div class="pl-2 space-y-1">
                            <div class="relative">
                                <span
                                    class="absolute text-xs font-bold text-gray-400 -translate-y-1/2 pointer-events-none left-3 top-1/2"
                                >
                                    Rp
                                </span>
                                <InputRupiah
                                    id="price"
                                    v-model="currentItem.price"
                                    class="w-full h-12 text-sm text-gray-800 bg-white border border-gray-300 rounded-xl form-input dark:bg-gray-800 dark:border-gray-600 dark:text-gray-100 placeholder:text-gray-400 focus:border-lime-500 focus:ring-lime-500"
                                    placeholder="0"
                                    :disabled="currentItem.type === 'link'"
                                />
                            </div>
                            <p
                                class="text-[10px] text-right text-gray-400 font-medium"
                            >
                                {{ actions.formatRupiah(currentItem.price) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between p-4 border border-gray-100 shadow-sm bg-gray-50 rounded-xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div>
                        <p
                            class="text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                        >
                            Subtotal
                        </p>
                        <p class="text-xs font-medium text-gray-500 mt-0.5">
                            {{ currentItem.quantity }} x
                            {{ actions.formatRupiah(currentItem.price) }}
                        </p>
                    </div>

                    <div class="flex-1 max-w-[160px]">
                        <div class="relative">
                            <span
                                class="absolute text-xs font-bold text-gray-400 -translate-y-1/2 pointer-events-none left-3 top-1/2"
                            >
                                Rp
                            </span>
                            <InputRupiah
                                :model-value="calculatedTotal"
                                @update:model-value="handleTotalChange"
                                class="w-full py-2.5 pr-3 text-lg font-black text-left text-gray-800 bg-white border border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:text-white dark:bg-gray-900 dark:border-gray-600 transition-all"
                                placeholder="0"
                                min="0"
                            />
                        </div>
                    </div>
                </div>

                <p
                    v-if="currentItem.type !== 'edit'"
                    class="text-[10px] text-center text-gray-400 italic px-4"
                >
                    * Perubahan akan disimpan. Anda dapat mengeditnya kembali
                    nanti.
                </p>
            </div>

            <template #footer>
                <div class="flex gap-3">
                    <button
                        v-if="currentItem.type === 'edit'"
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
                        @click="handleMainSave"
                        class="flex-[2] py-3.5 bg-lime-500 text-white font-bold rounded-xl shadow-lg shadow-lime-500/30 active:scale-[0.98] transition"
                    >
                        {{
                            currentItem.type === "edit"
                                ? "Simpan Perubahan"
                                : "Tautkan Barang"
                        }}
                    </button>
                </div>
            </template>
        </BottomSheet>

        <!-- Bottom Sheet Konfirmasi Validasi Khusus Mobile -->
        <BottomSheet :show="showValidationSheet" @close="showValidationSheet = false" title="Konfirmasi Validasi Nota">
            <div class="px-4 pb-6 space-y-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-xl space-y-3">
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 p-2.5 rounded-lg shadow-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Item</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-100">{{ linkedItems?.length || 0 }} Produk</span>
                    </div>
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 p-2.5 rounded-lg shadow-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Tagihan (Nota)</span>
                        <span class="text-sm font-black text-gray-800 dark:text-gray-100">{{ actions.formatRupiah(invoice.total_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 p-2.5 rounded-lg shadow-sm">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Total Nilai Barang</span>
                        <span class="text-base font-black text-lime-600 drop-shadow-sm">
                            {{ actions.formatRupiah(linkedItems?.reduce((acc, item) => acc + (item.subtotal || 0), 0)) }}
                        </span>
                    </div>
                </div>
                
                <p class="text-xs text-center text-red-500 bg-red-50 dark:bg-red-900/30 dark:border-red-900/50 border border-red-100 p-3 rounded-lg font-medium leading-relaxed">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Pastikan fisik barang dan nota Supplier sudah sesuai. Validasi yang telah disetujui tidak dapat dibatalkan.
                </p>
                
                <button @click="showValidationSheet = false; actions.validateInvoice(true)" class="w-full flex justify-center items-center py-4 bg-lime-500 hover:bg-lime-600 active:scale-95 text-white font-black text-sm uppercase tracking-wider rounded-xl shadow-xl shadow-lime-500/30 transition-all border border-lime-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 -ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Validasi Sekarang
                </button>
            </div>
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
