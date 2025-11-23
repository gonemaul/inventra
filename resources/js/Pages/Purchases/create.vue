<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import { throttle } from "lodash";
import { useActionLoading } from "@/Composable/useActionLoading";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import tableCreate from "./partials/tableCreate.vue";
import Recom from "./partials/recom.vue";
import Search from "./partials/search.vue";
import { usePurchaseCart } from "@/Composable/usePurchaseCart";
import { useToast } from "vue-toastification";

const { isActionLoading } = useActionLoading();
const toast = useToast();
const props = defineProps({
    dropdowns: Object, // Berisi { suppliers: [], statuses: [] }
    products: Array, // Katalog produk LENGKAP untuk autocomplete
    // recommendations: Array, // (Untuk Tahap 3)
});
const {
    cartItems,
    addToCart,
    updateCartItem,
    removeItem,
    clearCart,
    totalUnit,
    totalBelanja,
    totalMacam,
    addMultipleItems,
} = usePurchaseCart();

const formHeader = useForm({
    supplier_id: props.purchase?.supplier_id || "",
    transaction_date:
        props.purchase?.transaction_date ||
        new Date().toISOString().split("T")[0],
    status: props.purchase?.status || "draft", // Default 'draft' (Sesuai alur baru)
    notes: props.purchase?.notes || "",
    shipping_cost: props.purchase?.shipping_cost || 0,
    other_costs: props.purchase?.other_costs || 0,
});

// Ini menampung data yang sedang ditampilkan di form bagian atas
const stagingItem = ref({
    product_id: null, // Jika null = mode kosong, Jika ada ID = siap tambah/edit
    name: "",
    code: "",
    unit: "",
    size: "",
    category: "",
    image_path: null,
    current_stock: 0,

    // Input user
    quantity: 1,
    purchase_price: 0,
    // DSS data
    restock_recommendation: 0,
});

const isEditingMode = ref(false);

const searchKeyword = ref(""); // Teks yang diketik user
const searchResults = ref([]); // Hasil filter dari props.products
const isSearching = ref(false); // Untuk menampilkan "mencari..."
const showRecom = ref(false);
const SUPPLIER_KEY = "inventra_draft_supplier_id";

watch(
    () => formHeader.supplier_id,
    (newId) => {
        if (newId) {
            // Menyimpan nilai baru
            localStorage.setItem(SUPPLIER_KEY, newId);
        } else {
            // Jika user memilih opsi 'Pilih Supplier' (null), hapus cache
            localStorage.removeItem(SUPPLIER_KEY);
        }
    }
);
onMounted(() => {
    const savedSupplier = localStorage.getItem(SUPPLIER_KEY);

    // Cek jika formHeader.supplier_id belum terisi dan ada data yang tersimpan
    if (savedSupplier && !formHeader.supplier_id) {
        formHeader.supplier_id = savedSupplier;
    }
});
// --- WATCHER OTOMATIS (Setelah Supplier Dipilih) ---
// Watcher ini akan berjalan setiap kali formHeader.supplier_id berubah
watch(
    () => formHeader.supplier_id,
    (newId) => {
        // 1. Logic persistence supplier ID sudah ada di onMounted

        // 2. Tampilkan Modal hanya jika ID valid (tidak null/kosong)
        if (newId) {
            showRecom.value = true;
        }
        // Catatan: Jika Anda ingin modal TIDAK muncul otomatis saat load,
        // Anda bisa menambahkan guard !isMounted disini.
    }
);
const handleBulkAdd = (items) => {
    // Panggil fungsi bulk add dari engine
    addMultipleItems(items);

    // Beri notifikasi toast (opsional)
    toast.info(`${items.length} item rekomendasi ditambahkan ke keranjang.`);
};
const handleSearch = throttle(() => {
    if (searchKeyword.value.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    // Filter Client-side
    searchResults.value = availableProducts.value
        .filter(
            (p) =>
                p.name
                    .toLowerCase()
                    .includes(searchKeyword.value.toLowerCase()) ||
                p.code.toLowerCase().includes(searchKeyword.value.toLowerCase())
        )
        .slice(0, 10);
    isSearching.value = false;
}, 300);

watch(searchKeyword, (newVal) => handleSearch(newVal));

const availableProducts = computed(() => {
    const selectedId = formHeader.supplier_id;
    if (!selectedId) {
        return [];
    }

    return props.products.filter((p) => p.supplier_id == selectedId);
});

// Saat user memilih produk dari autocomplete
const selectProductFromSearch = (product) => {
    // Reset mode edit jika user mencari barang baru
    isEditingMode.value = false;

    // Isi Staging Area dengan data produk
    fillStagingArea(product);

    // Reset Search UI
    searchKeyword.value = "";
    searchResults.value = [];
};
// [PERBAIKAN] Helper untuk mengisi form atas
const fillStagingArea = (product, qty = 1, price = null) => {
    stagingItem.value = {
        product_id: product.id,
        name: product.name,
        code: product.code,
        category: product.category?.name || "-", // Kategori
        unit: product.unit?.name || "-",
        size: product.size?.name || "-",

        // [BARU] Tambahkan Merk dan Tipe Produk (sesuai Blueprint)
        brand: product.brand?.name || "-",
        type: product.product_type?.name || "-",

        image_path: product.image_path,
        current_stock: product.stock,

        quantity: qty,
        purchase_price: price !== null ? price : product.purchase_price,
        // [BARU] Tambahkan Rekomendasi Restock Sederhana (Logic sementara)
        restock_recommendation: Math.max(0, product.min_stock - product.stock),
    };
};

const handleSaveStaging = () => {
    // 1. Validasi Kuantitas dan Produk
    if (!stagingItem.value.product_id) {
        toast.error("Mohon pilih produk terlebih dahulu.");
        return;
    }
    if (stagingItem.value.quantity <= 0) {
        toast.error("Kuantitas minimal harus 1.");
        return;
    }

    // 2. Tentukan FLOW: Update atau Tambah Baru/Merge
    if (isEditingMode.value) {
        // --- FLOW A: UPDATE ITEM (Mode Edit) ---
        updateCartItem({
            product_id: stagingItem.value.product_id,
            quantity: stagingItem.value.quantity,
            purchase_price: stagingItem.value.purchase_price,
        });
        toast.success("Item berhasil diperbarui.");
    } else {
        // --- FLOW B: TAMBAH BARU / MERGE QTY (Mode Tambah) ---
        // Ambil objek produk lengkap untuk snapshot/merge di engine
        const fullProduct = props.products.find(
            (p) => p.id === stagingItem.value.product_id
        );

        addToCart(
            fullProduct,
            stagingItem.value.quantity,
            stagingItem.value.purchase_price
        );
        toast.success("Item ditambahkan ke keranjang.");
    }

    // 3. Reset Staging Area (Siap untuk input berikutnya)
    resetStaging();
};

const handleEditCartItem = (item) => {
    // Cari data produk asli di master data untuk info gambar/stok terkini
    const originalProduct = props.products.find(
        (p) => p.id === item.product_id
    );

    if (originalProduct) {
        // Isi form atas dengan data dari keranjang + data master
        fillStagingArea(originalProduct, item.quantity, item.purchase_price);

        // Aktifkan mode edit (agar tombol berubah jadi "Update")
        isEditingMode.value = true;

        // Scroll ke atas (opsional, UX bagus untuk mobile)
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

// [PERBAIKAN] Reset Staging
const resetStaging = () => {
    stagingItem.value = {
        product_id: null,
        name: "",
        code: "",
        unit: "",
        size: "",
        category: "",
        brand: "", // <-- Tambah reset
        type: "", // <-- Tambah reset
        image_path: null,
        current_stock: 0,
        restock_recommendation: 0, // <-- Tambah reset
        quantity: 1,
        purchase_price: 0,
    };
    isEditingMode.value = false;
};

const submitTransaction = () => {
    // 1. VALIDASI WAJIB: Form Header
    if (!formHeader.supplier_id) {
        toast.error("Mohon pilih Supplier terlebih dahulu!");
        return;
    }

    // 2. VALIDASI WAJIB: Keranjang Kosong
    if (cartItems.value.length === 0) {
        toast.error("Keranjang belanja masih kosong!");
        return;
    }

    // Aktifkan loader sebelum kirim
    isActionLoading.value = true;

    // 3. Gabungkan Header + Item dan kirim
    formHeader
        .transform((data) => ({
            ...data,
            items: cartItems.value, // Mengirim data dari composable
        }))
        .post(route("purchases.store"), {
            onSuccess: () => {
                // [KRITIS] Bersihkan LocalStorage setelah sukses simpan ke DB
                clearCart();
                // Inertia akan mengurus redirect dan toast success dari BE (flash message)
                formHeader.reset();
            },
            onError: (errors) => {
                // Tampilkan error validasi dari Laravel jika ada
                toast.error(
                    "Gagal menyimpan transaksi. Harap periksa input Anda."
                );
                console.error(errors);
            },
            onFinish: () => {
                localStorage.removeItem(SUPPLIER_KEY);
                isActionLoading.value = false;
            },
        });
};

// Format Rupiah (Helper)
function formatRupiah(number) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
}
const parseRupiah = (value) => {
    // Menghapus semua karakter non-digit (termasuk titik/koma)
    return parseInt(String(value).replace(/[^0-9]/g, "")) || 0;
};
// Computed property untuk memformat harga di input secara real-time
const displayedPrice = computed({
    get() {
        // Getter: Mengubah angka mentah menjadi string Rupiah untuk ditampilkan
        if (!stagingItem.value.purchase_price) return 0;
        return formatRupiah(stagingItem.value.purchase_price);
    },
    set(value) {
        // Setter: Mengubah string Rupiah kembali ke angka mentah (integer)
        stagingItem.value.purchase_price = parseRupiah(value);
    },
});
</script>
<template>
    <Head title="Rancangan Anggaran Belanja" />

    <AuthenticatedLayout
        headerTitle="Rancangan Anggaran Belanja"
        :showSidebar="false"
    >
        <Recom
            :show="showRecom"
            :supplierId="formHeader.supplier_id"
            @close="showRecom = false"
            @add-items="handleBulkAdd"
        />
        <form @submit.prevent="submitTransaction" class="">
            <div class="w-full min-h-screen space-y-6">
                <div class="space-y-6">
                    <div class="flex flex-col w-full gap-4 md:flex-row">
                        <div
                            class="flex flex-col gap-4 p-4 bg-gray-200 rounded-lg lg:w-1/2 dark:bg-customBg-tableDark"
                        >
                            <div class="flex flex-row gap-4">
                                <div class="flex flex-col w-full gap-3">
                                    <div
                                        class="flex flex-row justify-start gap-3"
                                    >
                                        <div class="w-full lg:w-2/3">
                                            <label
                                                class="block mb-1 text-sm font-medium"
                                                >Supplier</label
                                            >
                                            <select
                                                v-model="formHeader.supplier_id"
                                                class="w-full px-2 focus:border-lime-500 focus:ring-lime-500 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 bg-white dark:bg-gray-600 dark:text-white"
                                            >
                                                <option value="">
                                                    Pilih Supplier
                                                </option>
                                                <option
                                                    v-for="supplier in dropdowns.suppliers"
                                                    :key="supplier.id"
                                                    :value="supplier.id"
                                                >
                                                    {{ supplier.name }}
                                                </option>
                                            </select>
                                            <InputError
                                                :message="
                                                    formHeader.errors
                                                        .supplier_id
                                                "
                                                class="mt-1"
                                            />
                                        </div>
                                        <div class="hidden lg:block">
                                            <label
                                                class="block mb-1 text-sm font-medium"
                                                >Tanggal</label
                                            >
                                            <input
                                                v-model="
                                                    formHeader.transaction_date
                                                "
                                                type="date"
                                                class="w-full px-2 focus:border-lime-500 focus:ring-lime-500 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 dark:bg-gray-600 dark:text-white"
                                            />
                                            <!-- <TextInput
                                                v-model="
                                                    formHeader.transaction_date
                                                "
                                                type="date"
                                                class="text-sm w-full py-1.5 px-2"
                                            /> -->
                                            <InputError
                                                :message="
                                                    formHeader.errors
                                                        .transaction_date
                                                "
                                                class="mt-1"
                                            />
                                        </div>
                                        <!-- <div class="hidden w-1/5 mt-2 lg:block">
                                            <label
                                                class="block text-sm font-medium"
                                                >Last Update</label
                                            >
                                            <p
                                                class="text-xs text-gray-600 dark:text-gray-400"
                                            >
                                                12 Agustus 2025 10:22:30 WIB
                                            </p>
                                        </div> -->
                                    </div>

                                    <div
                                        class="flex flex-row justify-between gap-3"
                                    >
                                        <Search
                                            class="flex-1"
                                            v-model="searchKeyword"
                                            :results="searchResults"
                                            :isSearching="isSearching"
                                            @select="selectProductFromSearch"
                                        />
                                        <div class="w-1/5 lg:w-1/6">
                                            <label
                                                class="block mb-1 text-sm font-medium"
                                                >Qty</label
                                            >
                                            <input
                                                v-model.number="
                                                    stagingItem.quantity
                                                "
                                                type="number"
                                                placeholder="Qty"
                                                :disabled="
                                                    !stagingItem.product_id
                                                "
                                                class="w-full px-2 focus:border-lime-500 focus:ring-lime-500 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 dark:bg-gray-600 dark:text-white"
                                            />
                                        </div>
                                        <div class="w-1/4">
                                            <label
                                                class="block mb-1 text-sm font-medium"
                                                >Harga Beli</label
                                            >
                                            <input
                                                v-model="displayedPrice"
                                                type="text"
                                                placeholder="Rp"
                                                :disabled="
                                                    !stagingItem.product_id
                                                "
                                                class="w-full px-2 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 dark:bg-gray-600 dark:text-white focus:border-lime-500"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="flex-col items-center justify-center hidden w-1/5 rounded lg:flex"
                                >
                                    <img
                                        alt="Produk"
                                        class="object-cover w-32 h-32 rounded-md aspect-square"
                                        :src="
                                            stagingItem.image_path
                                                ? `/storage/${stagingItem.image_path}`
                                                : '/no-image.png'
                                        "
                                    />
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap justify-center gap-2 lg:justify-start"
                            >
                                <Link :href="route('purchases.index')">
                                    <SecondaryButton type="button">
                                        Kembali
                                    </SecondaryButton>
                                </Link>

                                <PrimaryButton
                                    type="button"
                                    @click="handleSaveStaging"
                                    :disabled="!stagingItem.product_id"
                                >
                                    {{
                                        isEditingMode
                                            ? "Update Item"
                                            : "Tambah Item"
                                    }}
                                </PrimaryButton>

                                <button
                                    @click="resetStaging"
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-red-500 border border-transparent rounded-md hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                >
                                    Reset
                                </button>

                                <PrimaryButton
                                    type="button"
                                    @click="submitTransaction"
                                    :disabled="
                                        formHeader.processing ||
                                        cartItems.length === 0
                                    "
                                >
                                    {{
                                        formHeader.processing
                                            ? "Menyimpan..."
                                            : "Simpan"
                                    }}
                                </PrimaryButton>

                                <button
                                    @click="showRecom = true"
                                    type="button"
                                    class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-yellow-500 border border-transparent rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2"
                                >
                                    Rekomendasi
                                </button>
                            </div>
                        </div>

                        <div
                            class="flex flex-col justify-between p-4 text-sm bg-gray-200 rounded-lg dark:bg-customBg-tableDark"
                        >
                            <div
                                class="text-base border-b-2 border-gray-400 text-semibold"
                            >
                                {{
                                    stagingItem.name
                                        ? stagingItem.name +
                                          " ( " +
                                          stagingItem.code +
                                          " )"
                                        : "Belum ada barang dipilih"
                                }}
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <strong>Kategori</strong>
                                    <strong>Satuan</strong>
                                    <strong>Ukuran</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{
                                        stagingItem.category || "-"
                                    }}</span>
                                    <span>{{ stagingItem.unit || "-" }}</span>
                                    <span>{{ stagingItem.size || "-" }}</span>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-3 gap-4 pt-2 border-t-2 border-gray-400"
                            >
                                <div>
                                    <p class="font-semibold">Sisa Stok</p>
                                    <p>
                                        {{ stagingItem.current_stock || "0" }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-semibold">Rekom Restok</p>
                                    <p>
                                        {{
                                            stagingItem.restock_recommendation ||
                                            "0"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-semibold">Stok Masuk</p>
                                    <p>
                                        {{ stagingItem.quantity || "0" }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-semibold">Total Stok</p>
                                    <p>
                                        {{
                                            (stagingItem.current_stock || 0) +
                                            (stagingItem.quantity || 0)
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-semibold">Harga Beli</p>
                                    <p>
                                        {{
                                            formatRupiah(
                                                stagingItem.purchase_price || 0
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-semibold">Total</p>
                                    <p>
                                        {{
                                            formatRupiah(
                                                (stagingItem.quantity || 0) *
                                                    (stagingItem.purchase_price ||
                                                        0)
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col justify-between flex-1 w-full p-4 bg-gray-200 rounded-lg dark:bg-customBg-tableDark"
                        >
                            <div class="flex justify-between">
                                <p class="text-lg font-bold">
                                    {{ totalUnit }} Unit
                                </p>
                                <p class="text-lg font-bold">
                                    {{ totalMacam }} Macam
                                </p>
                            </div>
                            <div class="mt-8 text-left">
                                <p
                                    class="text-gray-500 uppercase dark:text-gray-400"
                                >
                                    Total Belanja
                                </p>
                                <p class="text-3xl font-extrabold">
                                    {{ formatRupiah(totalBelanja) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div
                    class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
                >
                    <tableCreate
                        :items="cartItems"
                        @remove="removeItem"
                        @edit="handleEditCartItem"
                    />
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
