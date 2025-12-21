<!-- <script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import ImageModal from "@/Components/ImageModal.vue";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { throttle } from "lodash";

import MobileChecking from "./Components/Checking/MobileChecking.vue";
import DesktopChecking from "./Components/Checking/DesktopChecking.vue";

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
const isMobile = ref(window.innerWidth < 1024);
const updateScreenSize = () => {
    isMobile.value = window.innerWidth < 1024;
};

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
    window.addEventListener("resize", updateScreenSize);
    // Kunci logikanya: Jika status 'received' DAN belum ada ID invoice (props.invoice.id == 0 atau null)
    if (props.purchase.status === "diterima" && !props.invoice.id) {
        // [AKSI OTOMATIS] Buka modal Create Invoice
        openCreateInvoiceModal();
    }
});
onUnmounted(() => window.removeEventListener("resize", updateScreenSize));
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
const actions = {
    validateInvoice,
    addNewSubstituteItem,
    handleSearchNewItem,
    saveCorrections,
    submitUnlinkage,
    submitLinkage,
    formatTanggal,
    formatRupiah,
};
</script> -->
<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading";
import { throttle } from "lodash";

// Import Child Components
import MobileChecking from "./Components/Checking/Mobile/MobileChecking.vue";
import DesktopChecking from "./Components/Checking/Desktop/DesktopChecking.vue";

// --- PROPS ---
const props = defineProps({
    purchase: Object,
    invoice: Object,
    unlinkedItems: Array, // Produk PO yang belum punya invoice ID
    linkedItems: Array, // Produk PO yang tertaut ke invoice ini
    products: { type: Array, default: () => [] }, // Katalog Produk untuk Search
});
// --- STATE ---
const { isActionLoading } = useActionLoading();
const toast = useToast();
const isProcessing = ref(false);

// State responsive
const isMobile = ref(window.innerWidth < 1024);
const updateScreenSize = () => {
    isMobile.value = window.innerWidth < 1024;
};

// State untuk checkbox (Khusus Desktop / Bulk Action)
const selectedLinkItemIds = ref([]);
const selectedUnlinkItemIds = ref([]);

// State Linkage (Editable Items)
const editableLinkedItems = ref([]);

// State Search & Barang Baru
const searchKeyword = ref("");
const searchResults = ref([]);
const isSearching = ref(false);

// --- FORMS ---
const linkForm = useForm({ ids: [], type: "link", newQty: 0, newPrice: 0 });
const singleLinkForm = useForm({
    product_id: [],
    type: "create",
    newQty: 0,
    newPrice: 0,
});
const unlinkForm = useForm({ item_ids: [] });
const correctionForm = useForm({ items: [] });

// --- INIT ---
onMounted(() => {
    // Deep copy linkedItems agar bisa diedit tanpa merusak props asli sebelum save
    editableLinkedItems.value = JSON.parse(JSON.stringify(props.linkedItems));
    window.addEventListener("resize", updateScreenSize);
});
onUnmounted(() => window.removeEventListener("resize", updateScreenSize));

// Sync editable items jika props berubah (misal setelah reload inertia)
watch(
    () => props.linkedItems,
    (newVal) => {
        editableLinkedItems.value = JSON.parse(JSON.stringify(newVal));
    },
    { deep: true }
);

// --- COMPUTED HELPERS ---
const computedTotalNominal = computed(() => {
    return editableLinkedItems.value.reduce((sum, item) => {
        const subtotal = (item.quantity || 0) * (item.purchase_price || 0);
        return sum + subtotal;
    }, 0);
});

// --- FUNGSI UTAMA (REFACTORED UNTUK MENERIMA ARGUMEN) ---

// 1. Submit Linkage (Bisa Bulk dari State, atau Single ID dari Argumen)
const submitLinkage = (specificIds = null, newQty = 0, newPrice = 0) => {
    let idsToProcess = [];

    if (specificIds) {
        // Jika ada argumen (dari Mobile/Single action), gunakan itu
        idsToProcess = Array.isArray(specificIds) ? specificIds : [specificIds];
        linkForm.newQty = newQty;
        linkForm.newPrice = newPrice;
    } else {
        // Jika tidak ada argumen, ambil dari checkbox state (Desktop)
        idsToProcess = selectedLinkItemIds.value;
    }

    if (idsToProcess.length === 0) {
        toast.error("Pilih minimal satu item untuk ditautkan.");
        return;
    }

    linkForm.ids = idsToProcess;
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
                    `${idsToProcess.length} item berhasil ditautkan.`
                );
                // Reset selection
                selectedLinkItemIds.value = [];
                // Refresh halaman
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onError: (err) => {
                toast.error("Terjadi kesalahan pada server.");
                console.error(err);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 2. Submit Unlinkage (Bisa Bulk dari State, atau Single ID dari Argumen)
const submitUnlinkage = (specificIds = null) => {
    let idsToProcess = [];

    if (specificIds) {
        // Skenario Mobile / Single Delete: Gunakan ID yang dikirim
        idsToProcess = Array.isArray(specificIds) ? specificIds : [specificIds];
    } else {
        // Skenario Desktop / Bulk Delete: Gunakan checkbox state
        idsToProcess = selectedUnlinkItemIds.value;
    }

    if (idsToProcess.length === 0) {
        toast.error("Pilih minimal satu item untuk dilepaskan.");
        return;
    }

    unlinkForm.item_ids = idsToProcess;
    isActionLoading.value = true;
    isProcessing.value = true;

    unlinkForm.put(
        route("purchases.unlinkItems", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.warning(`${idsToProcess.length} item dilepaskan.`);
                selectedUnlinkItemIds.value = [];
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 3. Add New / Substitute Item (Link barang baru via search)
const addNewSubstituteItem = (product) => {
    // Validasi lokal sederhana
    const exists = linkedItems.value.some(
        (item) => item.product_id === product.id
    );
    if (exists) {
        toast.warning(
            "Produk ini sudah ada di daftar. Gunakan fitur edit untuk mengubah Qty."
        );
        return;
    }

    singleLinkForm.product_id = [product.id];
    singleLinkForm.newQty = [product.qty];
    singleLinkForm.newPrice = [product.price];
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
                toast.success("Produk tambahan berhasil dimasukkan.");
                router.visit(window.location.href, {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
                // Reset search di parent jika perlu
                searchKeyword.value = "";
                searchResults.value = [];
            },
        }
    );
};

// 4. Save Corrections (Simpan perubahan Qty/Harga)
const saveCorrections = () => {
    // Validasi input
    if (editableLinkedItems.value.some((item) => item.quantity < 0)) {
        toast.error("Qty tidak boleh minus.");
        return;
    }

    correctionForm.items = editableLinkedItems.value;
    isActionLoading.value = true;
    isProcessing.value = true;

    correctionForm.put(
        route("purchases.updateLinkedItemDetails", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Data berhasil diperbarui!");
                // Reload props linkedItems saja
                router.reload({ only: ["linkedItems"] });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 5. Validate Invoice (Finalisasi)
const validateInvoice = () => {
    if (props.invoice.status === "validated") return;

    if (computedTotalNominal.value !== props.invoice.total_amount) {
        toast.error(
            `Total nominal (${computedTotalNominal.value}) tidak sesuai dengan Target Nota (${props.invoice.total_amount}).`
        );
        return;
    }

    if (
        !confirm(
            "Apakah Anda yakin data sudah benar? Aksi ini tidak bisa dibatalkan."
        )
    )
        return;

    isActionLoading.value = true;
    isProcessing.value = true;

    router.put(
        route("purchases.validateInvoice", {
            purchase: props.purchase.id,
            invoice: props.invoice.id,
        }),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Invoice Validated!");
                router.visit(route("purchases.validate", props.purchase.id), {
                    preserveScroll: true,
                    preserveState: false,
                });
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 6. Handle Search (Watcher logic dipindah ke sini agar bisa dipakai desktop juga jika perlu)
const handleSearchNewItem = throttle((keyword) => {
    if (keyword.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    // Filter dari props.products (Client side filtering agar cepat)
    searchResults.value = props.products
        .filter(
            (p) =>
                p.name.toLowerCase().includes(keyword.toLowerCase()) ||
                p.code.toLowerCase().includes(keyword.toLowerCase())
        )
        .slice(0, 5);
    isSearching.value = false;
}, 300);

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

// --- ACTIONS OBJECT (Untuk dipass ke Child) ---
const actions = {
    validateInvoice, //untuk validasi invoice apabila tidak ada selisih
    addNewSubstituteItem, //untuk menambahkan produk yang belum ada ke purchase kemudian tautkan ke invoice
    handleSearchNewItem, //untuk pencarian client side
    saveCorrections, // untuk memperbarui qty dan price dari item yang tertaut ke invoie
    submitUnlinkage, // untuk melepas link produk ke invoice
    submitLinkage, // untuk link produk dari daftar purchase(tinggal link tanpa create baru)
    formatRupiah,
    formatTanggal,
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
    <div v-if="isMobile">
        <MobileChecking
            :purchase="purchase"
            :invoice="invoice"
            :unlinkedItems="unlinkedItems"
            :linkedItems="linkedItems"
            :products="products"
            :actions="actions"
        />
    </div>
    <div v-else>
        <DesktopChecking
            :purchase="purchase"
            :invoice="invoice"
            :unlinkedItems="unlinkedItems"
            :editableLinkedItems="editableLinkedItems"
            :products="products"
            :actions="actions"
            v-model:selectedLinkItemIds="selectedLinkItemIds"
            v-model:selectedUnlinkItemIds="selectedUnlinkItemIds"
            :searchResults="searchResults"
            :is-searching="isSearching"
            @update:search-keyword="
                (val) => {
                    searchKeyword = val;
                    handleSearchNewItem(val);
                }
            "
        />
    </div>
</template>
