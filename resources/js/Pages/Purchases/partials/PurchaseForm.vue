<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { usePurchaseCart } from "@/Composable/usePurchaseCart"; // Engine Keranjang
import { throttle } from "lodash";
// Asumsi Anda memiliki import komponen UI lain (TextInput, PrimaryButton, dll.)

// 1. PROPS
const props = defineProps({
    dropdowns: Object, // { suppliers: [...], statuses: [...] }
    products: Array, // Full catalog for search
    purchase: Object, // Data Transaksi (Hanya ada di mode Edit)
    isEdit: Boolean, // Penanda Mode (true untuk Edit)
});

// 2. ENGINE KERANJANG
const {
    cartItems,
    addToCart,
    updateCartItem,
    removeItem,
    clearCart,
    fillCart,
    totalUnit,
    totalBelanja,
    totalMacam,
} = usePurchaseCart();

// 3. STATE FORM HEADER (Data Transaksi)
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

// 4. STATE INPUT BARANG (STAGING AREA & MODE)
const isEditingMode = ref(false); // Mode Edit untuk Staging Area
const searchKeyword = ref("");
const searchResults = ref([]);
const isSearching = ref(false);

const stagingItem = ref({
    product_id: null,
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

// 5. LOGIKA INITIALISASI (Load DB data atau Cache)
onMounted(() => {
    if (props.isEdit && props.purchase) {
        // MODE EDIT: Isi form header dan keranjang dari data DB
        if (props.purchase.items) {
            fillCart(props.purchase.items); // Isi keranjang dari DB
        }
        // Pastikan formHeader sudah terisi di awal props
    } else {
        // MODE CREATE: usePurchaseCart sudah otomatis load dari LocalStorage
        // Kita hanya perlu memastikan formHeader memiliki default yang benar
    }
});

// --- FUNGSI UTAMA ---

// 6. LOGIKA SEARCH (Client-side filtering)
// ... (handleSearch, watch, dan selectProductFromSearch sama seperti sebelumnya)

// 7. FUNGSI HANDLE EDIT (Dipanggil dari tombol "Edit" di tabel)
const handleEditCartItem = (item) => {
    const originalProduct = props.products.find(
        (p) => p.id === item.product_id
    );
    if (originalProduct) {
        // Isi Staging Area dengan data dari Keranjang (item) + data terbaru (originalProduct)
        stagingItem.value = {
            product_id: item.product_id,
            name: item.name,
            code: item.code,
            image_path: originalProduct.image_path,
            unit: item.unit,
            size: item.size,
            category: item.category,
            current_stock: originalProduct.stock, // Tampilkan stok terkini

            // Data Transaksi yang mau di-edit
            quantity: item.quantity,
            purchase_price: item.purchase_price,

            // Rekomendasi Restock Sederhana (untuk visual)
            restock_recommendation: Math.max(
                0,
                originalProduct.min_stock - originalProduct.stock
            ),
        };
        isEditingMode.value = true;
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

// 8. FUNGSI SAVE (Tambah/Update Item)
const handleSaveStaging = () => {
    if (!stagingItem.value.product_id || stagingItem.value.quantity <= 0)
        return;

    if (isEditingMode.value) {
        // FLOW UPDATE: Update item yang sudah ada
        updateCartItem({
            product_id: stagingItem.value.product_id,
            quantity: stagingItem.value.quantity,
            purchase_price: stagingItem.value.purchase_price,
        });
    } else {
        // FLOW TAMBAH: Tambah atau Merge Item
        const fullProduct = props.products.find(
            (p) => p.id === stagingItem.value.product_id
        );

        addToCart(
            fullProduct,
            stagingItem.value.quantity,
            stagingItem.value.purchase_price
        );
    }

    // Reset Staging
    resetStaging();
};

// 9. FUNGSI SUBMIT UTAMA
const submitTransaction = () => {
    // Validasi Wajib
    if (!formHeader.supplier_id) {
        alert("Pilih Supplier!");
        return;
    }
    if (cartItems.value.length === 0) {
        alert("Keranjang kosong!");
        return;
    }

    // Tentukan URL dan Method (PUT untuk Edit, POST untuk Create)
    const url = props.isEdit
        ? route("purchases.update", props.purchase.id)
        : route("purchases.store");
    const method = props.isEdit ? "put" : "post";

    formHeader
        .transform((data) => ({
            ...data,
            items: cartItems.value,
            _method: method,
        }))
        .post(url, {
            onSuccess: () => {
                if (!props.isEdit) clearCart(); // Hanya clear cache jika Create
            },
            onError: (err) => console.error(err),
        });
};

// ... (Sisa fungsi: resetStaging, formatRupiah, computed properties) ...

// Ini adalah logika yang akan kita gunakan untuk membangun template
</script>
