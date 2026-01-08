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
    products: Array, // Master Produk (untuk pencarian barang baru)
    actions: Object, // Kumpulan fungsi dari parent (submitLinkage, addNewSubstituteItem, dll)
});

// --- STATE ---
const { isActionLoading } = useActionLoading();
const activeTab = ref("unlinked");
const showScanner = ref(false); // Kontrol kamera
const showScanModal = ref(false); // Kontrol modal verifikasi
const showSearch = ref(false); // Kontrol overlay pencarian manual
const searchQuery = ref("");

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

// 5. Callback dari BarcodeScanner
const onScanResult = (decodedText) => {
    showScanner.value = false;
    // code only
    const match = props.unlinkedItems?.find((u) => u.code === decodedText);
    if (match) {
        onSelectUnlinked(match);
    } else {
        // SearchProduct()
        const res = props.product.find((q) => q.code === decodedText);
        currentItem.value = {
            type: "create",
            id: null,
            product_id: res.id,
            name: res.name,
            code: res.code,
            unit: res.unit?.name || "Pcs",
            is_po_match: false,
            po_qty: 0,
            po_price: 0,
            quantity: 1,
            price: res.purchase_price,
            total: res.purchase_price,
        };
        showScanModal.value = true;
    }
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

        // Bulatkan agar rapi (pilih salah satu)
        currentItem.value.price = Math.round(newPrice);
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

// 3. Tombol Stepper (+ / -)
const adjustQty = (amount) => {
    let newQty = (currentItem.value.quantity || 0) + amount;
    if (newQty < 0) newQty = 0;
    currentItem.value.quantity = newQty;
};
</script>

<template>
    <div
        class="relative min-h-screen font-sans bg-gray-50 dark:bg-gray-950 pb-28"
    >
        <div
            class="sticky top-0 z-20 px-4 py-3 space-y-3 bg-gray-50/95 dark:bg-gray-950/95 backdrop-blur-sm"
        >
            <HeaderMobile
                :purchase="purchase"
                :invoice="invoice"
                :linked-items="linkedItems"
                :validateInvoice="actions.validateInvoice"
            />
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
                    Sisa PO
                    {{ unlinkedItems ? "(" + unlinkedItems?.length + ")" : "" }}
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
            <UnlinkMobile
                v-if="activeTab === 'unlinked'"
                :unlinked-items="unlinkedItems"
                :handleProductSelection="onSelectUnlinked"
            />

            <LinkedMobile
                v-if="activeTab === 'linked'"
                :linkedItems="linkedItems"
                :openEditModal="onSelectLinked"
            />
        </div>

        <SearchMobile
            v-if="showSearch"
            v-model="searchQuery"
            :filtered-products="filteredProducts"
            :unlinked-items="unlinkedItems"
            @close="showSearch = false"
            @onSelectSearch="onSelectSearch"
        />

        <BarcodeScanner
            v-if="showScanner"
            @result="onScanResult"
            @close="showScanner = false"
        />

        <div
            class="fixed z-30 w-full max-w-xs px-4 -translate-x-1/2 bottom-6 left-1/2"
            v-if="!showSearch && !showScanner && unlinkedItems"
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
                                    v-model.number="currentItem.price"
                                    min="1"
                                    class="w-full h-10 pr-3 text-base font-bold text-left transition-all bg-white border border-gray-200 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-900 dark:border-gray-600 dark:text-white"
                                    placeholder="0"
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
