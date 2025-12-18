<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";
import ImageModal from "@/Components/ImageModal.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { throttle } from "lodash";
import Search from "./Components/RAB/search.vue";

// --- PROPS DARI BACKEND ---
const props = defineProps({
    purchase: Object,
    invoice: Object,
    unlinkedItems: Object, // Produk PO yang belum punya invoice ID
    linkedItems: Object, // Produk PO yang tertaut ke invoice ini
    // Note: Anda perlu menambahkan prop products: Array, di Controller untuk search
    products: { type: Array, default: () => [] },
});
// --- STATE LINKAGE & MODE ---
const { isActionLoading } = useActionLoading();
const toast = useToast();
const isProcessing = ref(false);

// Mode Page (Edit jika 'checking', Detail jika lainnya)
const pageMode = computed(() =>
    props.purchase.status === "checking" ? "edit" : "detail"
);

// [KUNCI]: State untuk Qty dan Harga yang boleh diedit user
// Kita gunakan salinan linkedItems untuk menampung perubahan Qty/Harga
const editableLinkedItems = ref([]);

// State untuk Link/Unlink
const selectedLinkItemIds = ref([]);
const selectedUnlinkItemIds = ref([]);

// State untuk Barang Pengganti/Baru
const showNewItemForm = ref(false); // Kontrol visibility form input manual
const searchKeyword = ref("");
const searchResults = ref([]);
const isSearching = ref(false);

// --- FORMS UNTUK SUBMISSION ---
const linkForm = useForm({ ids: [], type: "link" });
const singleLinkForm = useForm({ product_id: [], type: "create" });
const unlinkForm = useForm({ item_ids: [] });
const correctionForm = useForm({ items: editableLinkedItems }); // Form untuk Simpan Koreksi Qty/Harga

// --- INIT LOGIC ---
onMounted(() => {
    // Buat salinan dalam (deep copy) dari linked items saat load
    // if (pageMode.value === "edit") {
    editableLinkedItems.value = JSON.parse(JSON.stringify(props.linkedItems));
    // }
});

// --- COMPUTED DATA & HELPERS ---
const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
const formatTanggal = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          })
        : "-";
const goBackToChecking = () =>
    router.get(route("purchases.checking", props.purchase.id));

const computedTotalNominal = computed(() => {
    // Menghitung total subtotal dari semua item yang dapat diedit
    return editableLinkedItems.value.reduce((sum, item) => {
        // [PENTING]: Hitung subtotal (Qty * Price) untuk setiap baris, lalu jumlahkan
        const subtotal = (item.quantity || 0) * (item.purchase_price || 0);
        return sum + subtotal;
    }, 0);
});
const computedTotalQty = computed(() => {
    // Menghitung total kuantitas dari semua item
    return editableLinkedItems.value.reduce((sum, item) => {
        return sum + (item.quantity || 0);
    }, 0);
});

// Gambar Nota
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedInvoiceCode = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedInvoiceCode.value = "Invoice-#" + name;
    showImageModal.value = true;
};

// --- FUNGSI UTAMA ---

// 1. [LINK ACTION] Menautkan item baru (sudah ada di props.unlinkedItems)
const submitLinkage = () => {
    if (selectedLinkItemIds.value.length === 0) {
        toast.error("Pilih minimal satu item untuk ditautkan.");
        return;
    }
    linkForm.ids = [];
    linkForm.ids = selectedLinkItemIds.value;
    console.log(linkForm.ids);
    isActionLoading.value = true;
    isProcessing.value = true;
    linkForm.post(
        route("purchases.linkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `${selectedLinkItemIds.value.length} item berhasil ditautkan. Harga otomatis diperbarui.`
                );
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onError: (error) => {
                toast.success(`Terdapat kesalahan : ${error}`);
                console.log(error);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
                selectedLinkItemIds.value = [];
            },
        }
    );
};

// 2. [UNLINK ACTION] Melepaskan tautan item (sudah ada di linkedItems)
const submitUnlinkage = () => {
    if (selectedUnlinkItemIds.value.length === 0) {
        toast.error("Pilih minimal satu item untuk dilepaskan.");
        return;
    }
    unlinkForm.item_ids = [];
    unlinkForm.item_ids = selectedUnlinkItemIds.value;
    isActionLoading.value = true;
    isProcessing.value = true;
    // Gunakan method PUT untuk aksi UPDATE (mengubah link menjadi NULL)
    unlinkForm.put(
        route("purchases.unlinkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.warning(
                    `${selectedUnlinkItemIds.value.length} item dilepaskan. Harga item lain tidak berubah.`
                );
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
                selectedUnlinkItemIds.value = [];
            },
        }
    );
};

// 3. [BARU] SIMPAN KOREKSI QTY/HARGA (Update Qty/Price di DB)
const saveCorrections = () => {
    isActionLoading.value = true;
    isProcessing.value = true;

    // Perlu validasi FE: Qty tidak boleh < 0
    const invalidQty = editableLinkedItems.value.some(
        (item) => item.quantity < 0
    );
    if (invalidQty) {
        toast.error("Kuantitas yang diterima tidak boleh kurang dari nol.");
        isProcessing.value = false;
        isActionLoading.value = false;
        return;
    }

    correctionForm.items = editableLinkedItems.value;

    // [ENDPOINT BARU DIBUTUHKAN]: purchases.updateLinkedItemDetails (PUT)
    correctionForm.put(
        route("purchases.updateLinkedItemDetails", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    `${editableLinkedItems.value.length} Produk berhasil diperbarui!`
                );
                // Reload hanya items yang tertaut
                router.reload({ only: ["linkedItems"] });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 4. [BARU] Logika Search & Penambahan Barang Pengganti
const handleSearchNewItem = throttle(() => {
    if (searchKeyword.value.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    // Filter props.products (katalog master) berdasarkan supplier yang sama
    searchResults.value = props.products
        .filter(
            (p) =>
                p.name
                    .toLowerCase()
                    .includes(searchKeyword.value.toLowerCase()) ||
                p.code.toLowerCase().includes(searchKeyword.value.toLowerCase())
        )
        .slice(0, 5); // Tampilkan 5 hasil teratas
    isSearching.value = false;
}, 300);

watch(searchKeyword, handleSearchNewItem);

// Aksi: Menambahkan produk pengganti ke daftar editableLinkedItems
const addNewSubstituteItem = (product) => {
    // Cek apakah item sudah ada di daftar
    const exists = editableLinkedItems.value.some(
        (item) => item.product_id === product.id
    );
    const unlinked = props.unlinkedItems.some(
        (item) => item.product_id === product.id
    );
    if (unlinked) {
        toast.warning(
            "Produk ini sudah ada di daftar pembelian. Tautkan untuk menambahkan ke invoice."
        );
        return;
    }
    if (exists) {
        toast.warning(
            "Produk ini sudah ada di daftar. Gunakan input Qty untuk menambah."
        );
        return;
    }

    singleLinkForm.product_id = [];
    singleLinkForm.product_id = [product.id];
    isActionLoading.value = true;
    isProcessing.value = true;
    singleLinkForm.post(
        route("purchases.linkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Produk berhasil ditambahkan kedalam Invoice`);
                // router.reload({
                //     only: ["unlinkedItems", "linkedItems", "invoice"],
                // });
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onError: (error) => {
                toast.success(`Terdapat kesalahan : ${error}`);
                console.log(error);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
                selectedLinkItemIds.value = [];
            },
        }
    );
    searchKeyword.value = "";
    searchResults.value = [];
    showNewItemForm.value = false;
};

const validateInvoice = () => {
    if (props.invoice.status === "validated") {
        toast.error(
            "Invoice yang sudah berstatus 'validated' tidak dapat divalidasi ulang."
        );
        return;
    }
    if (editableLinkedItems.value.length === 0) {
        toast.error("Tidak dapat memvalidasi invoice tanpa item tertaut.");
        return;
    }
    if (computedTotalNominal.value !== props.invoice.total_amount) {
        toast.error(
            "Total nominal item tertaut tidak sesuai dengan total nota. Silakan koreksi terlebih dahulu."
        );
        return;
    }
    if (props.purchase.status !== "checking") {
        toast.error(
            "Hanya invoice pada pembelian dengan status 'checking' yang dapat divalidasi."
        );
        return;
    }
    isActionLoading.value = true;
    isProcessing.value = true;

    router.put(
        route("purchases.validateInvoice", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(`Invoice berhasil divalidasi!`);
                router.visit(route("purchases.validate", props.purchase.id), {
                    preserveScroll: true,
                    preserveState: false, // Wajib agar semua prop di-refresh
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};
</script>

<template>
    <ImageModal
        :show="showImageModal"
        :imageUrl="selectedImageUrl"
        :productName="selectedInvoiceCode"
        @close="showImageModal = false"
    />
    <Head :title="`Invoice #${invoice.invoice_number}`" />
    <AuthenticatedLayout
        :headerTitle="`Invoice : #${invoice.invoice_number}`"
        :showSidebar="false"
    >
        <div class="flex items-center justify-between mb-4">
            <SecondaryButton @click="goBackToChecking">
                ← Kembali ke Validasi Utama
            </SecondaryButton>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div
                    class="flex flex-col h-full bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
                    >
                        <div>
                            <h3
                                class="text-lg font-bold text-gray-800 dark:text-white"
                            >
                                Item Terhubung
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Sesuaikan Qty dan Harga sesuai fisik barang.
                            </p>
                        </div>

                        <span
                            v-if="linkedItems.length === 0"
                            class="px-2 py-1 text-[10px] font-bold bg-gray-100 text-gray-500 rounded border"
                        >
                            KOSONG
                        </span>
                    </div>

                    <div
                        v-if="linkedItems.length === 0"
                        class="flex flex-col items-center justify-center flex-1 py-12 text-center"
                    >
                        <div
                            class="flex items-center justify-center w-12 h-12 mb-3 bg-gray-100 rounded-full dark:bg-gray-700"
                        >
                            <svg
                                class="w-6 h-6 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                                ></path>
                            </svg>
                        </div>
                        <p
                            class="text-sm font-medium text-gray-600 dark:text-gray-300"
                        >
                            Belum ada item tertaut.
                        </p>
                        <p class="mt-1 text-xs text-gray-400">
                            Pilih item dari daftar di sebelah kanan.
                        </p>
                    </div>

                    <form
                        v-else
                        @submit.prevent="saveCorrections"
                        class="flex flex-col flex-1 min-h-0"
                    >
                        <div class="flex-1 overflow-auto custom-scrollbar">
                            <table class="w-full text-sm text-left">
                                <thead
                                    class="sticky top-0 z-10 text-xs font-bold text-gray-500 uppercase border-b bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                                >
                                    <tr>
                                        <th class="px-4 py-3">Produk</th>
                                        <th class="w-24 px-2 py-3 text-center">
                                            Qty Fisik
                                        </th>
                                        <th class="w-32 px-2 py-3 text-right">
                                            Harga Satuan
                                        </th>
                                        <th
                                            v-if="pageMode === 'edit'"
                                            class="w-10 px-2 py-3 text-center"
                                        >
                                            <svg
                                                class="w-4 h-4 mx-auto text-gray-400"
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
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="divide-y divide-gray-100 dark:divide-gray-700"
                                >
                                    <tr
                                        v-for="item in editableLinkedItems"
                                        :key="item.id"
                                        class="transition group hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                    >
                                        <td class="px-4 py-3 align-top">
                                            <div>
                                                <p
                                                    class="text-sm font-bold text-gray-800 dark:text-white line-clamp-2"
                                                >
                                                    {{
                                                        item.product_snapshot
                                                            .brand +
                                                        " " +
                                                        item.product_snapshot
                                                            .name +
                                                        " " +
                                                        item.product_snapshot
                                                            .size
                                                    }}
                                                </p>
                                                <div
                                                    class="text-[13px] text-gray-600 dark:text-gray-200 mt-0.5 flex flex-wrap gap-1"
                                                >
                                                    <span
                                                        class="bg-gray-100 dark:bg-gray-700 px-1.5 rounded"
                                                        >{{
                                                            item
                                                                .product_snapshot
                                                                .code
                                                        }}</span
                                                    >
                                                    <span>{{
                                                        item.product_snapshot
                                                            .brand
                                                    }}</span>
                                                    <span
                                                        v-if="
                                                            item
                                                                .product_snapshot
                                                                .size
                                                        "
                                                        >•
                                                        {{
                                                            item
                                                                .product_snapshot
                                                                .size
                                                        }}</span
                                                    ><span
                                                        >|
                                                        {{
                                                            item
                                                                .product_snapshot
                                                                .category
                                                        }}
                                                        |
                                                        {{
                                                            item
                                                                .product_snapshot
                                                                .productType
                                                        }}</span
                                                    >
                                                </div>

                                                <div class="mt-2 space-y-1">
                                                    <div
                                                        v-if="
                                                            item.purchase_price >
                                                            (item
                                                                .product_snapshot
                                                                .purchase_price ||
                                                                0)
                                                        "
                                                        class="flex items-center text-[10px] text-red-600 font-bold bg-red-50 w-fit px-1.5 rounded"
                                                    >
                                                        <span class="mr-1"
                                                            >📈</span
                                                        >
                                                        Naik
                                                        {{
                                                            formatRupiah(
                                                                item.purchase_price -
                                                                    item
                                                                        .product_snapshot
                                                                        .purchase_price
                                                            )
                                                        }}
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            item.purchase_price <
                                                            (item
                                                                .product_snapshot
                                                                .purchase_price ||
                                                                0)
                                                        "
                                                        class="flex items-center text-[10px] text-green-600 font-bold bg-green-50 w-fit px-1.5 rounded"
                                                    >
                                                        <span class="mr-1"
                                                            >📉</span
                                                        >
                                                        Turun
                                                        {{
                                                            formatRupiah(
                                                                item
                                                                    .product_snapshot
                                                                    .purchase_price -
                                                                    item.purchase_price
                                                            )
                                                        }}
                                                    </div>

                                                    <div
                                                        v-if="
                                                            item.quantity <
                                                            (item
                                                                .product_snapshot
                                                                .stock || 0)
                                                        "
                                                        class="text-[10px] text-orange-600 font-bold flex items-center"
                                                    >
                                                        <span
                                                            class="w-1.5 h-1.5 bg-orange-500 rounded-full mr-1"
                                                        ></span>
                                                        Kurang
                                                        {{
                                                            (item
                                                                .product_snapshot
                                                                .stock || 0) -
                                                            item.quantity
                                                        }}
                                                        (Parsial)
                                                    </div>
                                                    <div
                                                        v-else-if="
                                                            item.quantity >
                                                            (item
                                                                .product_snapshot
                                                                .stock || 0)
                                                        "
                                                        class="text-[10px] text-purple-600 font-bold flex items-center"
                                                    >
                                                        <span
                                                            class="w-1.5 h-1.5 bg-purple-500 rounded-full mr-1"
                                                        ></span>
                                                        Lebih
                                                        {{
                                                            item.quantity -
                                                            (item
                                                                .product_snapshot
                                                                .stock || 0)
                                                        }}
                                                        (Bonus)
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-2 py-3 align-top">
                                            <input
                                                type="number"
                                                v-model.number="item.quantity"
                                                :disabled="pageMode !== 'edit'"
                                                class="w-full text-sm font-bold text-center border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                min="0"
                                            />
                                            <div
                                                class="text-[10px] text-center text-gray-400 mt-1"
                                            >
                                                Target:
                                                {{
                                                    item.product_snapshot
                                                        .stock || 0
                                                }}
                                            </div>
                                        </td>

                                        <td class="px-2 py-3 align-top">
                                            <div class="relative">
                                                <span
                                                    class="absolute text-xs text-gray-400 left-2 top-2"
                                                    >Rp</span
                                                >
                                                <input
                                                    type="number"
                                                    v-model.number="
                                                        item.purchase_price
                                                    "
                                                    :disabled="
                                                        pageMode !== 'edit'
                                                    "
                                                    class="w-full pl-7 pr-2 py-1.5 text-right text-sm font-medium border-gray-300 rounded-lg focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                            </div>
                                            <div
                                                class="text-[10px] text-right text-gray-400 mt-1"
                                            >
                                                Awal:
                                                {{
                                                    formatRupiah(
                                                        item.product_snapshot
                                                            .purchase_price || 0
                                                    )
                                                }}
                                            </div>
                                        </td>

                                        <td
                                            v-if="pageMode === 'edit'"
                                            class="px-2 py-3 text-center align-middle"
                                        >
                                            <input
                                                type="checkbox"
                                                :value="item.id"
                                                v-model="selectedUnlinkItemIds"
                                                class="w-4 h-4 text-red-600 transition border-gray-300 rounded cursor-pointer focus:ring-red-500 hover:scale-110"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div
                            v-if="pageMode === 'edit'"
                            class="flex items-center justify-between gap-3 p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
                        >
                            <button
                                @click.prevent="submitUnlinkage"
                                :disabled="
                                    selectedUnlinkItemIds.length === 0 ||
                                    isProcessing
                                "
                                class="flex items-center gap-2 px-3 py-2 text-xs font-bold text-red-600 transition border border-red-200 rounded-lg bg-red-50 hover:bg-red-100 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                <span
                                    >Lepas ({{
                                        selectedUnlinkItemIds.length
                                    }})</span
                                >
                            </button>

                            <div class="flex gap-2">
                                <button
                                    @click.prevent="validateInvoice"
                                    :disabled="
                                        editableLinkedItems.length === 0 ||
                                        props.invoice.status === 'validated'
                                    "
                                    class="px-4 py-2 text-xs font-bold text-white transition bg-teal-500 rounded-lg shadow-sm hover:bg-teal-600 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Validasi Invoice
                                </button>

                                <button
                                    type="submit"
                                    :disabled="isProcessing"
                                    class="flex items-center gap-2 px-4 py-2 text-xs font-bold text-white transition transform rounded-lg shadow-md bg-lime-500 hover:bg-lime-600 shadow-lime-200 dark:shadow-none active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg
                                        v-if="isProcessing"
                                        class="w-4 h-4 animate-spin"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        ></circle>
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        ></path>
                                    </svg>
                                    <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6 lg:col-span-1">
                <div
                    class="relative flex flex-col p-5 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div
                        class="flex items-start justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-700"
                    >
                        <div>
                            <p
                                class="text-[10px] font-bold tracking-widest text-gray-400 uppercase mb-1"
                            >
                                INVOICE TARGET
                            </p>

                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-xl font-black tracking-tight text-gray-800 dark:text-white"
                                >
                                    #{{ invoice.invoice_number }}
                                </h3>

                                <div
                                    v-if="invoice.status === 'validated'"
                                    class="flex items-center justify-center w-6 h-6 text-teal-500 border border-teal-100 rounded-full bg-teal-50 dark:bg-teal-900/30 dark:border-teal-800"
                                    title="Tervalidasi (Data Fisik & Sistem Cocok)"
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
                                            d="M5 13l4 4L19 7"
                                        ></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <span
                            class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide border shadow-sm"
                            :class="{
                                'bg-green-100 text-green-700 border-green-200':
                                    invoice.payment_status === 'paid',
                                'bg-yellow-100 text-yellow-700 border-yellow-200':
                                    invoice.payment_status === 'partial',
                                'bg-red-100 text-red-700 border-red-200':
                                    invoice.payment_status === 'unpaid',
                            }"
                        >
                            {{ invoice.payment_status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-3 mb-4 text-sm">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-8 h-8 text-gray-500 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-100"
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
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                    ></path>
                                </svg>
                            </div>
                            <div>
                                <p
                                    class="font-bold text-gray-800 dark:text-white"
                                >
                                    {{ purchase.supplier?.name || "Umum/Cash" }}
                                </p>
                                <p
                                    class="w-40 text-xs text-gray-500 truncate dark:text-gray-400"
                                >
                                    {{ purchase.supplier?.address || "-" }} |
                                    {{ purchase.supplier?.phone || "-" }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between p-2 text-xs border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-700/50 dark:border-gray-600"
                        >
                            <div class="flex flex-col">
                                <span class="text-gray-400">Terbit</span>
                                <span
                                    class="font-bold text-gray-700 dark:text-gray-300"
                                    >{{
                                        formatTanggal(invoice.invoice_date)
                                    }}</span
                                >
                            </div>
                            <div
                                class="w-px h-6 bg-gray-300 dark:bg-gray-600"
                            ></div>
                            <div class="flex flex-col text-right">
                                <span class="text-gray-400">Jatuh Tempo</span>
                                <span
                                    class="font-bold text-red-600 dark:text-red-400"
                                    >{{ formatTanggal(invoice.due_date) }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative p-3 mb-4 overflow-hidden border rounded-xl"
                        :class="
                            Math.abs(
                                invoice.total_amount - computedTotalNominal
                            ) < 100
                                ? 'bg-lime-50 border-lime-200 dark:bg-lime-900/20 dark:border-lime-800'
                                : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800'
                        "
                    >
                        <div
                            class="absolute top-0 right-0 px-2 py-0.5 rounded-bl-lg text-[9px] font-bold uppercase tracking-widest text-white shadow-sm"
                            :class="
                                Math.abs(
                                    invoice.total_amount - computedTotalNominal
                                ) < 100
                                    ? 'bg-lime-500'
                                    : 'bg-red-500'
                            "
                        >
                            {{
                                Math.abs(
                                    invoice.total_amount - computedTotalNominal
                                ) < 100
                                    ? "Balanced (Cocok)"
                                    : "Unbalanced (Selisih)"
                            }}
                        </div>

                        <div class="mt-2 space-y-2">
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span
                                    class="font-medium text-gray-500 dark:text-gray-400"
                                    >Target Nota Fisik</span
                                >
                                <span
                                    class="font-bold text-gray-800 dark:text-white"
                                    >{{
                                        formatRupiah(invoice.total_amount)
                                    }}</span
                                >
                            </div>

                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span
                                    class="font-medium text-gray-500 dark:text-gray-400"
                                    >Total Item Terhubung ({{
                                        computedTotalQty
                                    }})</span
                                >
                                <span
                                    class="font-bold text-blue-600 dark:text-blue-400"
                                    >{{
                                        formatRupiah(computedTotalNominal)
                                    }}</span
                                >
                            </div>

                            <div
                                class="my-1 border-t border-gray-300/50 dark:border-gray-600"
                            ></div>

                            <div
                                class="flex items-center justify-between text-sm font-black"
                            >
                                <span
                                    :class="
                                        Math.abs(
                                            invoice.total_amount -
                                                computedTotalNominal
                                        ) < 100
                                            ? 'text-lime-700 dark:text-lime-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{
                                        Math.abs(
                                            invoice.total_amount -
                                                computedTotalNominal
                                        ) < 100
                                            ? "Selisih Rp 0"
                                            : "Selisih"
                                    }}
                                </span>
                                <span
                                    :class="
                                        Math.abs(
                                            invoice.total_amount -
                                                computedTotalNominal
                                        ) < 100
                                            ? 'text-lime-700 dark:text-lime-400'
                                            : 'text-red-600 dark:text-red-400'
                                    "
                                >
                                    {{
                                        formatRupiah(
                                            invoice.total_amount -
                                                computedTotalNominal
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="invoice.invoice_url" class="mt-auto">
                        <p
                            class="mb-2 text-[10px] font-bold text-gray-400 uppercase tracking-wider"
                        >
                            Lampiran Bukti
                        </p>
                        <div
                            class="relative overflow-hidden border border-gray-200 cursor-pointer group rounded-xl dark:border-gray-600"
                            @click="
                                openImageModal(
                                    invoice.invoice_url,
                                    invoice.invoice_number
                                )
                            "
                        >
                            <img
                                :src="invoice.invoice_url"
                                alt="Bukti Nota"
                                class="object-cover w-full h-24 transition duration-500 group-hover:scale-110 group-hover:opacity-75"
                            />

                            <div
                                class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 bg-black/30 backdrop-blur-[1px]"
                            >
                                <svg
                                    class="w-6 h-6 text-white drop-shadow-md"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="pageMode === 'edit'"
                    class="p-4 border-2 rounded-lg shadow-lg border-lime-100 bg-lime-50 dark:bg-lime-900/30"
                >
                    <h4
                        class="mb-3 font-bold text-yellow-800 dark:text-yellow-200"
                    >
                        Tambah Item Baru / Pengganti
                    </h4>
                    <Search
                        :isSearching="isSearching"
                        v-model="searchKeyword"
                        :results="searchResults"
                        @select="addNewSubstituteItem"
                        placeholder="Cari produk ..."
                    />
                </div>

                <div
                    v-if="pageMode === 'edit'"
                    class="p-6 bg-white border-2 border-gray-100 rounded-lg shadow-lg dark:bg-gray-800"
                >
                    <h4 class="mb-3 font-bold dark:text-gray-200">
                        Produk Belum Tertaut
                    </h4>
                    <form @submit.prevent="submitLinkage">
                        <p
                            v-if="
                                pageMode === 'edit' && unlinkedItems.length == 0
                            "
                            class="py-4 text-center text-gray-500"
                        >
                            Semua produk sudah ditautkan.
                        </p>
                        <div
                            v-else
                            class="overflow-y-auto border rounded max-h-48 dark:border-gray-700"
                        >
                            <ul class="p-2 space-y-1">
                                <li
                                    v-for="item in unlinkedItems"
                                    :key="item.id"
                                    class="flex items-center justify-between w-full p-2 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <label
                                        :for="'link_item_' + item.id"
                                        class="flex items-start w-full gap-2 text-sm cursor-pointer dark:text-gray-300"
                                    >
                                        <input
                                            :id="'link_item_' + item.id"
                                            type="checkbox"
                                            :value="item.id"
                                            v-model="selectedLinkItemIds"
                                            class="rounded text-lime-600 mt-1.5"
                                        />

                                        <div class="flex flex-col flex-1">
                                            <div
                                                class="font-medium text-gray-900 dark:text-white"
                                            >
                                                {{ item.product.name }} ({{
                                                    item.product.code
                                                }})
                                            </div>

                                            <div
                                                class="flex items-center justify-between mt-1 text-xs"
                                            >
                                                <span
                                                    class="text-gray-600 dark:text-gray-400"
                                                >
                                                    ({{ item.quantity }}x) @{{
                                                        formatRupiah(
                                                            item.purchase_price
                                                        )
                                                    }}
                                                </span>

                                                <span
                                                    class="font-bold text-gray-800 dark:text-gray-200"
                                                >
                                                    Subtotal:
                                                    {{
                                                        formatRupiah(
                                                            item.subtotal
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                </li>
                            </ul>
                        </div>

                        <div
                            v-if="unlinkedItems.length > 0"
                            class="flex justify-end mt-4"
                        >
                            <PrimaryButton
                                type="submit"
                                :disabled="
                                    selectedLinkItemIds.length === 0 ||
                                    isProcessing
                                "
                            >
                                Tautkan {{ selectedLinkItemIds.length }} Item
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
