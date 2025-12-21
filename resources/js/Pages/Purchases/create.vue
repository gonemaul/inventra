<script setup>
// --- 1. IMPORTS ---
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import { useToast } from "vue-toastification";

// Composables
import { useActionLoading } from "@/Composable/useActionLoading";
import { usePurchaseCart } from "@/Composable/usePurchaseCart";

// Components
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CatalogView from "./Components/RAB/CatalogView.vue"; // Komponen Katalog Baru
import PurchaseTable from "./Components/RAB/PurchaseTable.vue";
import Recom from "./Components/RAB/recom.vue";

// --- 2. SETUP & PROPS ---
const props = defineProps({
    isEdit: {
        type: Boolean,
        default: false,
    },
    purchase: Object, // Data edit (jika ada)
    dropdowns: Object, // { suppliers: [], statuses: [] }
});
console.log(props.purchase);
const toast = useToast();
const { isActionLoading } = useActionLoading();
const activeView = ref("table"); // options: 'table' | 'catalog'
// const isLocked = computed({
//     return props?.purchase?.status === 'dipesan'
// })

// Destructure Cart Logic
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
} = usePurchaseCart(props.isEdit, props.purchase);

// --- 3. STATE: FORM HEADER & VIEW ---
const formHeader = useForm({
    supplier_id: props.purchase?.supplier_id || "",
    transaction_date:
        props.purchase?.transaction_date ||
        new Date().toISOString().split("T")[0],
    status: props.purchase?.status || "draft",
    notes: props.purchase?.notes || "",
    shipping_cost: props.purchase?.shipping_cost || 0,
    other_costs: props.purchase?.other_costs || 0,
});

// --- 4. FEATURE: SUPPLIER MANAGEMENT & DSS ---
const showRecom = ref(false);
const SUPPLIER_KEY = "inventra_draft_supplier_id";

// Watcher Supplier: Handle Storage, Modal DSS, dan Reset View
watch(() => {
    formHeader.supplier_id,
        (newId) => {
            if (newId) {
                localStorage.setItem(SUPPLIER_KEY, newId);
                showRecom.value = true; // Buka rekomendasi saat supplier terpilih

                // Opsional: Otomatis buka katalog saat pilih supplier baru
                activeView.value = "catalog";
            } else {
                localStorage.removeItem(SUPPLIER_KEY);
                showRecom.value = false;
                activeView.value = "table";
            }
        };
});

onMounted(() => {
    const savedSupplier = localStorage.getItem(SUPPLIER_KEY);
    if (savedSupplier && !formHeader.supplier_id) {
        formHeader.supplier_id = savedSupplier;
    }
});

const handleBulkAdd = (items) => {
    addMultipleItems(items);
    toast.info(`${items.length} item rekomendasi ditambahkan ke keranjang.`);
};

// --- 5. FEATURE: STAGING AREA (INPUT/EDIT ITEM) ---
const isEditingMode = ref(false);
const defaultStagingState = {
    product_id: null,
    name: "",
    code: "",
    unit: "",
    size: "",
    category: "",
    brand: "",
    type: "",
    image_url: null,
    current_stock: 0,
    quantity: 1,
    purchase_price: 0,
    restock_recommendation: 0,
};

const stagingItem = ref({ ...defaultStagingState });

// Computed Harga Realtime
const displayedPrice = computed({
    get: () =>
        stagingItem.value.purchase_price
            ? formatRupiah(stagingItem.value.purchase_price)
            : 0,
    set: (val) => (stagingItem.value.purchase_price = parseRupiah(val)),
});

// Reset Form
const resetStaging = () => {
    stagingItem.value = { ...defaultStagingState };
    isEditingMode.value = false;
};

// [UTAMA] Handler saat Produk dipilih dari CATALOG VIEW
const handleCatalogSelection = (product) => {
    fillStagingArea(product);
    activeView.value = "catalog";
};

const fillStagingArea = (product, qty = 1, price = null) => {
    stagingItem.value = {
        product_id: product.id || product.product_id,
        name: product.name,
        code: product.code,
        category: product.category?.name || product.category || "-", // Sesuaikan nama key dari API JSON
        unit: product.unit?.name || product.unit || "-", // Sesuaikan nama key dari API JSON
        size: product.size?.name || product.size || "-",
        brand: product.brand?.name || product.brand || "-",
        type: product.type_name || product.type_name || "-",
        image_url: product.image_url,
        current_stock: product.stock || product.current_stock,
        quantity:
            product.insights?.find((i) => i.type === "restock")?.payload
                ?.suggested_qty || qty,
        // Gunakan purchase_price dari API, atau 0 jika null
        purchase_price: price !== null ? price : product.purchase_price || 0,
        restock_recommendation: product.insights?.find(
            (i) => i.type === "restock"
        )?.payload?.suggested_qty,
    };
};

// Handler: Edit item yang sudah ada di Cart
const handleEditCartItem = (item) => {
    fillStagingArea(item, item.quantity, item.purchase_price);
    isEditingMode.value = true;
    activeView.value = "table"; // Paksa view ke table
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// Handler: Simpan Staging ke Cart
const handleSaveStaging = () => {
    if (!stagingItem.value.product_id)
        return toast.error("Mohon pilih produk dari katalog.");
    if (stagingItem.value.quantity <= 0)
        return toast.error("Kuantitas minimal harus 1.");

    console.log(props.purchase?.status);
    if (isEditingMode.value && props.purchase?.status === "draft") {
        updateCartItem({
            product_id: stagingItem.value.product_id,
            quantity: stagingItem.value.quantity,
            purchase_price: stagingItem.value.purchase_price,
        });
        toast.success("Item diperbarui.");
    } else {
        addToCart(
            stagingItem.value,
            stagingItem.value.quantity,
            stagingItem.value.purchase_price,
            props.purchase?.status == "draft"
        );
        toast.success("Item ditambahkan.");
    }
    resetStaging();
};

const submitTransaction = () => {
    // 1. Validasi Umum
    if (!formHeader.supplier_id) return toast.error("Pilih Supplier dulu!");
    if (cartItems.value.length === 0) return toast.error("Keranjang kosong!");

    isActionLoading.value = true;

    // 2. Definisikan Transform Data (Sama untuk Store & Update)
    // Menggabungkan data header form dengan data cart items
    const transformData = (data) => ({
        ...data,
        items: cartItems.value, // Di mode Edit, ini sudah mengandung ID item
    });

    // 3. Opsi Handler (Callback)
    const options = {
        onSuccess: () => {
            toast.success(
                props.isEdit
                    ? "Transaksi berhasil diperbarui!"
                    : "Transaksi berhasil disimpan!"
            );

            // Jika mode CREATE, bersihkan form agar siap input lagi
            if (!props.isEdit) {
                clearCart();
                formHeader.reset();
                localStorage.removeItem(SUPPLIER_KEY); // Hapus cache supplier jika ada
            }
            // Jika mode EDIT, biasanya backend akan redirect ke index/show,
            // jadi kita tidak perlu clearCart manual di sini.
        },
        onError: (errors) => {
            toast.error("Gagal menyimpan. Periksa inputan merah.");
            console.error(errors);
            toast.error("Error : " + errors[0]);
        },
        onFinish: () => {
            isActionLoading.value = false;
        },
    };

    // 4. Eksekusi Berdasarkan Mode
    if (props.isEdit) {
        // --- MODE UPDATE (PUT) ---
        // Pastikan props.purchase.id tersedia dari parent
        console.log(props.purchase);
        formHeader
            .transform(transformData)
            .put(route("purchases.update", props.purchase.id), options);
    } else {
        // --- MODE STORE (POST) ---
        formHeader
            .transform(transformData)
            .post(route("purchases.store"), options);
    }
};

// --- 7. UTILS ---
function formatRupiah(number) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(number);
}
function parseRupiah(value) {
    return parseInt(String(value).replace(/[^0-9]/g, "")) || 0;
}
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
        <div class="w-full min-h-screen space-y-6">
            <div class="space-y-6">
                <div class="flex flex-col w-full gap-4 md:flex-row">
                    <div
                        class="flex flex-col gap-4 p-4 bg-gray-200 rounded-lg lg:w-1/2 dark:bg-customBg-tableDark"
                    >
                        <div
                            class="flex flex-col-reverse items-start gap-4 md:flex-row"
                        >
                            <div class="flex flex-col w-full gap-4">
                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-3"
                                >
                                    <div class="md:col-span-2">
                                        <label
                                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Supplier</label
                                        >
                                        <select
                                            :disabled="isEdit"
                                            v-model="formHeader.supplier_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
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
                                                formHeader.errors.supplier_id
                                            "
                                            class="mt-1"
                                        />
                                    </div>

                                    <div class="md:col-span-1">
                                        <label
                                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Tanggal</label
                                        >
                                        <input
                                            v-model="
                                                formHeader.transaction_date
                                            "
                                            type="date"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                        />
                                        <InputError
                                            :message="
                                                formHeader.errors
                                                    .transaction_date
                                            "
                                            class="mt-1"
                                        />
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-1 gap-4 md:grid-cols-3"
                                >
                                    <div class="md:col-span-1">
                                        <label
                                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Qty</label
                                        >
                                        <input
                                            v-model.number="
                                                stagingItem.quantity
                                            "
                                            type="number"
                                            min="1"
                                            placeholder="0"
                                            :disabled="!stagingItem.product_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label
                                            class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >Harga Beli</label
                                        >
                                        <div
                                            class="relative rounded-md shadow-sm"
                                        >
                                            <div
                                                class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                                            >
                                                <span
                                                    class="text-gray-500 sm:text-sm"
                                                    >Rp</span
                                                >
                                            </div>
                                            <input
                                                v-model="displayedPrice"
                                                type="text"
                                                placeholder="0"
                                                :disabled="
                                                    !stagingItem.product_id
                                                "
                                                class="w-full px-3 py-2 pl-10 font-mono text-right border border-gray-300 rounded-md shadow-sm focus:ring-lime-500 focus:border-lime-500 disabled:bg-gray-100 disabled:text-gray-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex-shrink-0 w-full md:w-32">
                                <label
                                    class="block mb-1 text-sm font-medium text-center text-gray-700 dark:text-gray-300 md:text-left"
                                >
                                    Preview
                                </label>

                                <div
                                    class="relative w-full overflow-hidden bg-gray-100 border border-gray-300 rounded-lg aspect-square dark:bg-gray-700 dark:border-gray-600 group"
                                >
                                    <div
                                        class="absolute inset-0 z-0 flex flex-col items-center justify-center w-full h-full transition-colors duration-300"
                                    >
                                        <svg
                                            class="w-10 h-10 mb-1 text-gray-400 dark:text-gray-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                        <span
                                            class="text-[10px] font-bold text-gray-400 dark:text-gray-500 tracking-wider"
                                        >
                                            NO IMG
                                        </span>
                                    </div>

                                    <img
                                        v-if="stagingItem.image_url"
                                        :src="stagingItem.image_url"
                                        alt="Preview"
                                        loading="lazy"
                                        class="absolute inset-0 z-10 object-cover w-full h-full transition-opacity duration-300 opacity-0"
                                        onload="this.classList.remove('opacity-0')"
                                        onerror="this.style.display='none'"
                                    />
                                </div>
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
                                <span>{{ stagingItem.category || "-" }}</span>
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
                class="p-4 space-y-4 bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex p-1 space-x-1 bg-gray-100 rounded-lg dark:bg-gray-700"
                >
                    <button
                        type="button"
                        @click="activeView = 'table'"
                        class="flex items-center justify-center w-1/2 py-2.5 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none"
                        :class="
                            activeView === 'table'
                                ? 'bg-white text-gray-900 shadow dark:bg-gray-600 dark:text-white'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
                        "
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            ></path>
                        </svg>
                        Daftar Belanja

                        <span
                            v-if="cartItems.length > 0"
                            class="ml-2 bg-lime-100 text-lime-700 py-0.5 px-2 rounded-full text-xs font-bold"
                        >
                            {{ cartItems.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeView = 'catalog'"
                        :disabled="!formHeader.supplier_id"
                        class="flex items-center justify-center w-1/2 py-2.5 text-sm font-medium rounded-md transition-all duration-200 focus:outline-none"
                        :class="[
                            activeView === 'catalog'
                                ? 'bg-white text-gray-900 shadow dark:bg-gray-600 dark:text-white'
                                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200',
                            !formHeader.supplier_id
                                ? 'opacity-50 cursor-not-allowed'
                                : '',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 mr-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                            ></path>
                        </svg>

                        <span v-if="!formHeader.supplier_id"
                            >Pilih Supplier Dulu</span
                        >
                        <span v-else>Katalog Produk</span>
                    </button>
                </div>

                <div class="min-h-[400px]">
                    <KeepAlive>
                        <component
                            :is="
                                activeView === 'table'
                                    ? PurchaseTable
                                    : CatalogView
                            "
                            :supplier-id="formHeader.supplier_id"
                            :items="cartItems"
                            :isDraft="purchase?.status == 'draft'"
                            @remove="removeItem"
                            @edit="handleEditCartItem"
                            @select-product="handleCatalogSelection"
                        />
                    </KeepAlive>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
