<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { Head, Link, useForm, router } from "@inertiajs/vue3";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";
import { useActionLoading } from "@/Composable/useActionLoading";
import { throttle } from "lodash";
import ImageModal from "@/Components/ImageModal.vue";

// Import Child Components
import MobileChecking from "./Components/Checking/Mobile/MobileChecking.vue";
import DesktopChecking from "./Components/Checking/Desktop/DesktopChecking.vue";
import ActionLoader from "@/Components/ActionLoader.vue";

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
const toast = usePremiumAlert();
const isProcessing = ref(false);
// Gambar Nota
const showImageModal = ref(false);
const selectedImageUrl = ref(null);
const selectedInvoiceCode = ref(null);
const openImageModal = (path, name) => {
    selectedImageUrl.value = path;
    selectedInvoiceCode.value = "Invoice-#" + name;
    showImageModal.value = true;
};

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
    product_id: null,
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
    return props.linkedItems.reduce((sum, item) => {
        const subtotal = item.subtotal || 0;
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

    return new Promise((resolve, reject) => {
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
                    resolve(true);
                },
                onError: (errors) => {
                    console.error("Kesalahan Sistem/Server:", errors);
                    toast.error("Gagal menautkan item. Periksa koneksi atau hubungi admin.");
                    reject(errors);
                },
                onFinish: () => {
                    isProcessing.value = false;
                    isActionLoading.value = false;
                },
            }
        );
    });
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

    return new Promise((resolve, reject) => {
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
                    resolve(true);
                },
                onError: (errors) => {
                    console.error("Kesalahan Sistem/Server:", errors);
                    toast.error("Gagal melepaskan tautan item. Periksa koneksi atau hubungi admin.");
                    reject(errors);
                },
                onFinish: () => {
                    isProcessing.value = false;
                    isActionLoading.value = false;
                },
            }
        );
    });
};

// 3. Add New / Substitute Item (Link barang baru via search)
const addNewSubstituteItem = (product) => {
    // Validasi lokal sederhana
    if (product && product.id) {
        singleLinkForm.product_id = product.id;
        singleLinkForm.newQty = product.newQty;
        singleLinkForm.newPrice = product.newPrice;
    } else {
        toast.error("Pilih produk yang valid untuk ditambahkan.");
        return;
    }

    const exists = props.linkedItems.some(
        (item) => item.product_id === product.id
    );
    if (exists) {
        toast.warning(
            "Produk ini sudah ada di daftar. Gunakan fitur edit untuk mengubah Qty."
        );
        return;
    }

    isActionLoading.value = true;
    isProcessing.value = true;

    return new Promise((resolve, reject) => {
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
                    resolve(true);
                },
                onError: (errors) => {
                    console.error("Kesalahan Sistem/Server:", errors);
                    toast.error("Gagal menambahkan produk baru. Periksa koneksi atau hubungi admin.");
                    reject(errors);
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
    });
};

// 4. Save Corrections (Simpan perubahan Qty/Harga)
const saveCorrections = (product = null) => {
    if (product) {
        // Validasi Input Single
        if (product.quantity < 0) {
            toast.error("Qty tidak boleh minus.");
            return Promise.reject("Validation Error");
        }
        if (product.purchase_price < 0) {
            toast.error("Harga tidak boleh minus.");
            return Promise.reject("Validation Error");
        }

        // Update State Lokal (Agar UI reaktif segera berubah sebelum request selesai)
        const index = editableLinkedItems.value.findIndex(
            (item) => item.id === product.id
        );
        if (index !== -1) {
            editableLinkedItems.value[index].quantity = product.quantity;
            editableLinkedItems.value[index].purchase_price =
                product.purchase_price;
        }
        correctionForm.items = [product];
    }
    // 2. SKENARIO: UPDATE ALL (SAVE ALL CHANGES)
    else {
        // Validasi sederhana untuk memastikan tidak ada data minus di list
        const hasInvalidData = editableLinkedItems.value.some(
            (item) => item.quantity < 0 || item.purchase_price < 0
        );

        if (hasInvalidData) {
            toast.error(
                "Terdapat data Qty atau Harga yang minus. Cek kembali."
            );
            return Promise.reject("Validation Error");
        }

        // SET PAYLOAD: Kirim semua item
        correctionForm.items = editableLinkedItems.value;
    }

    // 3. EKSEKUSI REQUEST
    isActionLoading.value = true;
    isProcessing.value = true;

    return new Promise((resolve, reject) => {
        correctionForm.put(
            route("purchases.updateLinkedItemDetails", {
                purchase: props.purchase.id,
                invoice: props.invoice.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Tampilkan pesan berbeda tergantung update single atau all
                    const msg = product
                        ? "Item berhasil diperbarui!"
                        : "Semua data berhasil disimpan!";

                    toast.success(msg);

                    // Reload data 'linkedItems' agar sinkron dengan DB
                    router.reload({ only: ["linkedItems"] });
                    resolve(true);
                },
                onError: (errors) => {
                    console.error("Kesalahan Sistem/Server:", errors);
                    if (errors.items) {
                        toast.error(errors.items);
                    } else {
                        toast.error("Gagal menyimpan perubahan. Periksa koneksi atau hubungi admin.");
                    }
                    reject(errors);
                },
                onFinish: () => {
                    isProcessing.value = false;
                    isActionLoading.value = false;
                },
            }
        );
    });
};

// 5. Validate Invoice (Finalisasi)
const validateInvoice = (bypassConfirm = false) => {
    if (props.invoice.status === "validated") {
        toast.warning("Invoice ini sudah divalidasi sebelumnya.");
        return;
    }

    // Gunakan toleransi Rp 1 untuk mengakomodasi floating-point imprecision
    // (misal: 3333.33 x 3 = 9999.99 vs 10000, selisih < Rp 1 masih diterima)
    const selisih = Math.abs(
        computedTotalNominal.value - props.invoice.total_amount
    );
    if (selisih > 1) {
        toast.error(
            `Selisih Rp ${Math.round(selisih).toLocaleString('id-ID')} — Total item (${formatRupiah(computedTotalNominal.value)}) tidak cocok dengan Nota (${formatRupiah(props.invoice.total_amount)}). Koreksi Qty/Harga terlebih dahulu.`
        );
        return;
    }

    if (!bypassConfirm) {
        if (!confirm("Apakah Anda yakin data sudah benar? Aksi ini tidak bisa dibatalkan.")) return;
    }

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
            onSuccess: (page) => {
                if (page.props.flash && page.props.flash.error) {
                    toast.error(page.props.flash.error);
                    return;
                }
                
                toast.success("✅ Invoice berhasil divalidasi!");
                setTimeout(() => {
                    router.visit(
                        route("purchases.checking", props.purchase.id),
                        {
                            preserveScroll: true,
                            preserveState: false,
                        }
                    );
                }, 800); // Beri jeda agar toast sempat tampil
            },
            onError: (err) => {
                console.error("Kesalahan Sistem/Server:", err);
                toast.error(`Gagal validasi: Periksa koneksi atau hubungi admin.`);
            },
            onFinish: () => {
                isProcessing.value = false;
                isActionLoading.value = false;
            },
        }
    );
};

// 6. Handle Search (Server-Side)
const handleSearchNewItem = throttle(async (keyword) => {
    // keyword is passed from child or v-model
    const keywordStr = typeof keyword === 'string' ? keyword : searchKeyword.value;
    
    if (keywordStr.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    
    try {
        const response = await axios.get(route('api.products.search'), {
            params: {
                q: keywordStr,
                limit: 10,
                supplier_id: props.purchase?.supplier_id // Hanya cari produk untuk supplier po ini
            }
        });
        
        searchResults.value = response.data;
    } catch (error) {
        console.error("Gagal melakukan pencarian produk:", error);
        toast.error("Gagal melakukan pencarian produk karena kesalahan jaringan atau server.");
        searchResults.value = [];
    } finally {
        isSearching.value = false;
    }
}, 500);

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
    openImageModal,
};
</script>

<template>
    <ActionLoader />
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
            :actions="actions"
            :searchResults="searchResults"
            :isSearching="isSearching"
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
        />
    </div>
</template>
