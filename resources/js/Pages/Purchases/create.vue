<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
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
} = usePurchaseCart();

const formHeader = useForm({
    supplier_id: "", // Wajib diisi
    user_id: 1,
    transaction_date: new Date().toISOString().split("T")[0], // Default hari ini
    status: "dipesan",
    notes: "",
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
});

const isEditingMode = ref(false);

const searchKeyword = ref(""); // Teks yang diketik user
const searchResults = ref([]); // Hasil filter dari props.products
const isSearching = ref(false); // Untuk menampilkan "mencari..."
const showRecommendationModal = ref(false);

// const currentItem = ref({
//     product_id: null,
//     name: "",
//     code: "",
//     image_path: null,
//     unit: "",
//     size: "",
//     current_stock: 0,
//     restock_recommendation: 0, // (Fitur Pintar Tahap 3)

//     // Input User
//     quantity: 1,
//     purchase_price: 0,
// });

const handleSearch = throttle(() => {
    if (searchKeyword.value.length < 2) {
        searchResults.value = [];
        return;
    }
    isSearching.value = true;
    // Filter Client-side
    searchResults.value = props.products
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
// Helper untuk mengisi form atas
const fillStagingArea = (product, qty = 1, price = null) => {
    stagingItem.value = {
        product_id: product.id,
        name: product.name,
        code: product.code,
        category: product.category?.name || "-",
        unit: product.unit?.name || "-",
        size: product.size?.name || "-",
        category: product.category?.name || "-",
        image_path: product.image_path,
        current_stock: product.stock,

        quantity: qty,
        // Jika price null (dari search), pakai harga master.
        // Jika price ada (dari edit), pakai harga transaksi.
        purchase_price: price !== null ? price : product.purchase_price,
    };
};

// 1. Tombol "Simpan / Tambah" di Form Atas
const handleSaveStaging = () => {
    // Validasi Item
    if (!stagingItem.value.product_id) return;
    if (stagingItem.value.quantity <= 0) {
        toast.error("Kesalahan: Jumlah minimal 1.");
        return; // Ganti toast nanti
    }

    if (isEditingMode.value) {
        // FLOW EDIT: Update item yang ada di keranjang
        updateCartItem({
            product_id: stagingItem.value.product_id,
            quantity: stagingItem.value.quantity,
            purchase_price: stagingItem.value.purchase_price,
        });
        // Matikan mode edit
        isEditingMode.value = false;
    } else {
        // FLOW TAMBAH: Masukkan barang baru / merge
        // Kita kirim FULL OBJECT produk dari props untuk snapshot data
        const fullProduct = props.products.find(
            (p) => p.id === stagingItem.value.product_id
        );

        addToCart(
            fullProduct,
            stagingItem.value.quantity,
            stagingItem.value.purchase_price
        );
    }
    console.log(cartItems.value);

    // Reset Staging Area (Kosongkan form atas)
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

// 3. Reset Form Atas
const resetStaging = () => {
    stagingItem.value = {
        product_id: null,
        name: "",
        code: "",
        category: "",
        unit: "",
        size: "",
        category: "",
        image_path: null,
        current_stock: 0,
        quantity: 1,
        purchase_price: 0,
    };
    isEditingMode.value = false;
};

// 4. Submit Akhir (Simpan Transaksi)
const submitTransaction = () => {
    // VALIDASI WAJIB: Supplier
    if (!formHeader.supplier_id) {
        toast.error("Mohon pilih Supplier terlebih dahulu!");
        return;
    }

    // VALIDASI: Keranjang Kosong
    if (cartItems.value.length === 0) {
        toast.error("Keranjang belanja masih kosong!");
        return;
    }

    // Gabungkan data dan kirim
    formHeader
        .transform((data) => ({
            ...data,
            items: cartItems.value,
        }))
        .post(route("purchases.store"), {
            onSuccess: () => {
                clearCart(); // Bersihkan localstorage
                // Reset form header
                formHeader.reset();
            },
            onError: (errors) => {
                console.error(errors);
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
// Data rekomendasi produk (biasanya dari API backend)
const rekomendasi = ref([
    {
        id: 1,
        nama: "Oli Motor A",
        satuan: "Botol",
        stok: 6,
        rekom: 24,
        inRAB: false,
    },
    {
        id: 2,
        nama: "Kampas Rem",
        satuan: "Set",
        stok: 3,
        rekom: 10,
        inRAB: true,
    }, // contoh sudah ada di RAB
    {
        id: 3,
        nama: "Filter Oli",
        satuan: "Pcs",
        stok: 8,
        rekom: 15,
        inRAB: false,
    },
]);
</script>
<template>
    <Head title="Rancangan Anggaran Belanja" />

    <AuthenticatedLayout
        headerTitle="Rancangan Anggaran Belanja"
        :showSidebar="false"
    >
        <Recom
            :show="showRecom"
            :items="rekomendasi"
            @close="showRecom = false"
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
                                        <div class="hidden py-1.5 lg:block">
                                            <label
                                                class="block mb-1 text-sm font-medium"
                                                >Tanggal</label
                                            >
                                            <TextInput
                                                v-model="
                                                    formHeader.transaction_date
                                                "
                                                type="date"
                                                class="text-sm w-full py-1.5"
                                            />
                                            <InputError
                                                :message="
                                                    formHeader.errors
                                                        .transaction_date
                                                "
                                                class="mt-1"
                                            />
                                        </div>
                                        <div class="hidden w-1/5 mt-2 lg:block">
                                            <label
                                                class="block text-sm font-medium"
                                                >Last Update</label
                                            >
                                            <p
                                                class="text-xs text-gray-600 dark:text-gray-400"
                                            >
                                                12 Agustus 2025 10:22:30 WIB
                                            </p>
                                        </div>
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
                                    @click="showRecommendationModal = true"
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
