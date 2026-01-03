<script setup>
// --- 1. IMPORTS & COMPOSABLES ---
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import {
    ref,
    defineAsyncComponent,
    computed,
    watch,
    onMounted,
    onUnmounted,
} from "vue";
import { useToast } from "vue-toastification";

// Custom Composables
import { useActionLoading } from "@/Composable/useActionLoading";
import { usePurchaseCart } from "@/Composable/usePurchaseCart";

// UI Components
import CatalogView from "./Components/RAB/CatalogView.vue";
import PurchaseTable from "./Components/RAB/PurchaseTable.vue";
import Recom from "./Components/RAB/recom.vue";

// Async Components
const HeadRabMobile = defineAsyncComponent(() =>
    import("./Components/RAB/Mobile/HeadRabMobile.vue")
);
const HeadRabDesktop = defineAsyncComponent(() =>
    import("./Components/RAB/Desktop/HeadRabDesktop.vue")
);

// --- 2. CONFIG & PROPS ---
const props = defineProps({
    purchase: Object, // Data transaksi (null jika create)
    dropdowns: Object, // { suppliers: [], statuses: [] }
});

const toast = useToast();
const { isActionLoading } = useActionLoading();
const SUPPLIER_KEY = "inventra_draft_supplier_id";

// --- 3. STATUS & MODE FLAGS (LOGIC PENGGANTI) ---
// Cek apakah ini mode Edit berdasarkan ada tidaknya data purchase
const isEditMode = computed(() => !!props.purchase && !!props.purchase.id);
// Ambil status saat ini (default 'draft' jika baru)
const currentStatus = computed(() => props.purchase?.status || "draft");
// Full Update: Masih Draft (Bisa edit semua)
const isDraft = computed(() => currentStatus.value === "draft");

// Half Update: Sudah Dipesan (Item lama dikunci, cuma bisa tambah/edit item baru)
const isOrdered = computed(() => currentStatus.value === "dipesan");

// --- 4. VIEW & SCREEN STATE ---
const isMobile = ref(window.innerWidth < 1024);
const updateScreenSize = () => {
    isMobile.value = window.innerWidth < 1024;
};

const activeView = ref("table"); // 'table' | 'catalog'
const showRecom = ref(false);

// --- 5. CART & FORM INIT ---

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
} = usePurchaseCart(isEditMode.value, props.purchase);

// Form Header
const formHeader = useForm({
    supplier_id: props.purchase?.supplier_id || "",
    transaction_date:
        props.purchase?.transaction_date ||
        new Date().toISOString().split("T")[0],
    status: currentStatus.value,
    notes: props.purchase?.notes || "",
    shipping_cost: props.purchase?.shipping_cost || 0,
    other_costs: props.purchase?.other_costs || 0,
});

// Staging Area (Item yang sedang diedit/ditambah)
const isEditingItem = ref(false);
const defaultStagingState = {
    product_id: null,
    name: "",
    code: "",
    unit: "",
    size: "",
    category: "",
    brand: "",
    type: "",
    image_url: "",
    current_stock: 0,
    quantity: 1,
    purchase_price: 0,
    restock_recommendation: 0,
};
const stagingItem = ref({ ...defaultStagingState });

// --- 6. COMPUTED & WATCHERS ---

// Formatter Harga untuk Input
const displayedPrice = computed({
    get: () =>
        stagingItem.value.purchase_price
            ? formatRupiah(stagingItem.value.purchase_price)
            : 0,
    set: (val) => (stagingItem.value.purchase_price = parseRupiah(val)),
});

// Watcher: Supplier & View Logic
watch(
    () => formHeader.supplier_id,
    (newId) => {
        if (newId) {
            localStorage.setItem(SUPPLIER_KEY, newId);
            showRecom.value = true;
            activeView.value = "catalog";
        } else {
            localStorage.removeItem(SUPPLIER_KEY);
            showRecom.value = false;
            activeView.value = "table";
        }
    }
);

// --- 7. LIFECYCLE ---
onMounted(() => {
    window.addEventListener("resize", updateScreenSize);

    // Restore supplier jika create baru
    const savedSupplier = localStorage.getItem(SUPPLIER_KEY);
    if (savedSupplier && !formHeader.supplier_id && !isEditMode.value) {
        formHeader.supplier_id = savedSupplier;
    }
});

onUnmounted(() => {
    window.removeEventListener("resize", updateScreenSize);
});

// --- 8. METHODS: STAGING & CATALOG ---

const fillStagingArea = (product, qty = 1, price = null) => {
    const restockInsight = product.insights?.find((i) => i.type === "restock");
    const suggestedQty = restockInsight?.payload?.suggested_qty;

    stagingItem.value = {
        product_id: product.product_id ? product.product_id : product.id,
        name: product.name,
        code: product.code,
        category: product.category?.name || product.category || "-",
        unit: product.unit?.name || product.unit || "-",
        size: product.size?.name || product.size || "-",
        brand: product.brand?.name || product.brand || "-",
        type: product.type_name || product.type_name || "-",
        image_url: product.image_url,
        current_stock: product.stock || product.current_stock,
        quantity: suggestedQty || qty,
        purchase_price: price !== null ? price : product.purchase_price || 0,
        restock_recommendation: suggestedQty,
    };
};

const resetStaging = () => {
    stagingItem.value = { ...defaultStagingState };
    isEditingItem.value = false;
};

// Selection Handler
const handleCatalogSelection = (product) => {
    fillStagingArea(product);
    activeView.value = "catalog";
    window.scrollTo({ top: 0, behavior: "smooth" });
};

const handleEditCartItem = (item) => {
    fillStagingArea(item, item.quantity, item.purchase_price);
    isEditingItem.value = true;
    activeView.value = "table";
    window.scrollTo({ top: 0, behavior: "smooth" });
};

// --- CORE: SAVE STAGING LOGIC ---
const handleSaveStaging = () => {
    // Validasi
    if (!stagingItem.value.product_id)
        return toast.error("Mohon pilih produk dari katalog.");
    if (stagingItem.value.quantity <= 0)
        return toast.error("Kuantitas minimal harus 1.");

    const existingItem = cartItems.value.find(
        (item) => item.product_id === stagingItem.value.product_id
    );
    if (existingItem) {
        // --- UPDATE ITEM DI CART ---
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
            stagingItem.value.purchase_price
        );
        toast.success("Item ditambahkan.");
    }
    resetStaging();
};

const handleRemoveItem = (item) => {
    if (isOrdered.value && item.id) {
        return toast.warning(
            "Item lama tidak boleh dihapus saat status Dipesan! Silakan edit Qty saja."
        );
    }

    // Jika item baru atau status Draft, hapus saja
    removeItem(item.product_id);
};

const handleBulkAdd = (items) => {
    addMultipleItems(items);
    toast.info(`${items.length} item rekomendasi ditambahkan ke keranjang.`);
};

// --- 9. METHODS: SUBMIT TRANSACTION ---

const submitTransaction = () => {
    if (!formHeader.supplier_id) return toast.error("Pilih Supplier dulu!");
    if (cartItems.value.length === 0) return toast.error("Keranjang kosong!");

    isActionLoading.value = true;

    const transformData = (data) => ({
        ...data,
        items: cartItems.value,
    });

    const options = {
        onSuccess: () => {
            toast.success(
                isEditMode.value
                    ? "Transaksi berhasil diperbarui!"
                    : "Transaksi berhasil disimpan!"
            );

            if (!isEditMode.value) {
                localStorage.removeItem(SUPPLIER_KEY);
            }
            const currentKey = isEditMode.value
                ? `inventra_edit_${props.purchase.id}`
                : "inventra_create_draft";
            localStorage.removeItem(currentKey);
            clearCart();
            formHeader.reset();
        },
        onError: (errors) => {
            console.error(errors);
            const errorMsg = Object.values(errors)[0] || "Terjadi kesalahan";
            toast.error("Gagal: " + errorMsg);
        },
        onFinish: () => {
            isActionLoading.value = false;
        },
    };

    // Eksekusi berdasarkan isEditMode
    if (isEditMode.value) {
        formHeader
            .transform(transformData)
            .put(route("purchases.update", props.purchase.id), options);
    } else {
        formHeader
            .transform(transformData)
            .post(route("purchases.store"), options);
    }
};

// --- 10. UTILS ---
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
            <HeadRabMobile
                v-if="isMobile"
                :form-header="formHeader"
                :dropdowns="dropdowns"
                :staging-item="stagingItem"
                :is-edit="isEditMode"
                :is-editing-mode="isEditingItem"
                :cart-items="cartItems"
                :total-unit="totalUnit"
                :total-macam="totalMacam"
                :total-belanja="totalBelanja"
                v-model:displayed-price="displayedPrice"
                @save-staging="handleSaveStaging"
                @reset-staging="resetStaging"
                @submit-transaction="submitTransaction"
                @open-recommendation="showRecom = true"
            />
            <HeadRabDesktop
                v-else
                :form-header="formHeader"
                :dropdowns="dropdowns"
                :staging-item="stagingItem"
                :is-edit="isEditMode"
                :is-editing-mode="isEditingItem"
                :cart-items="cartItems"
                :total-unit="totalUnit"
                :total-macam="totalMacam"
                :total-belanja="totalBelanja"
                v-model:displayed-price="displayedPrice"
                @save-staging="handleSaveStaging"
                @reset-staging="resetStaging"
                @submit-transaction="submitTransaction"
                @open-recommendation="showRecom = true"
            />

            <!-- Table -->
            <div
                class="p-4 space-y-4 bg-white border border-gray-200 shadow-sm rounded-xl dark:bg-gray-900 dark:border-gray-700"
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
                            :stagingItem="stagingItem"
                            :isDraft="isDraft"
                            @remove="handleRemoveItem"
                            @edit="handleEditCartItem"
                            @select-product="handleCatalogSelection"
                        />
                    </KeepAlive>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
