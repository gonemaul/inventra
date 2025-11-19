import { ref, computed, watch, onMounted } from "vue";

const STORAGE_KEY = "inventra_purchase_cart_temp";

export function usePurchaseCart() {
    // State Keranjang
    const cartItems = ref([]);

    // 1. Load dari Cache Browser
    function loadCart() {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            try {
                cartItems.value = JSON.parse(saved);
            } catch (e) {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    }

    // 2. Tambah Item Baru (Mode: ADD)
    // Jika barang sudah ada, kita tambahkan jumlahnya (merge)
    function addToCart(product, quantity, price) {
        const targetId = product.id;
        const qty = parseInt(quantity);
        const buyPrice = parseFloat(price);

        const existingItem = cartItems.value.find(
            (item) => item.product_id === targetId
        );

        if (existingItem) {
            // Barang ada? Tambahkan quantity-nya
            existingItem.quantity += qty;
            existingItem.purchase_price = buyPrice; // Update harga ke input terakhir
            existingItem.subtotal = existingItem.quantity * buyPrice;
        } else {
            // Barang baru? Push ke array
            // Kita simpan SNAPSHOT data produk di sini untuk tabel & backend
            cartItems.value.push({
                product_id: targetId,
                // Snapshot Data Produk (Read-only di tabel)
                name: product.name,
                code: product.code,
                category: product.category?.name || "-",
                unit: product.unit?.name || "-",
                size: product.size?.name || "-",
                current_stock: product.stock,

                // Data Transaksi (Editable)
                quantity: qty,
                purchase_price: buyPrice,
                subtotal: qty * buyPrice,
            });
        }
    }

    // 3. Update Item (Mode: EDIT)
    // Ini dipanggil saat user selesai mengedit data di form atas
    // Sifatnya menimpa (overwrite), bukan menambah
    function updateCartItem(updatedItem) {
        const index = cartItems.value.findIndex(
            (item) => item.product_id === updatedItem.product_id
        );

        if (index !== -1) {
            // Kita update data transaksi-nya saja, snapshot produk biarkan tetap
            cartItems.value[index].quantity = parseInt(updatedItem.quantity);
            cartItems.value[index].purchase_price = parseFloat(
                updatedItem.purchase_price
            );
            cartItems.value[index].subtotal =
                cartItems.value[index].quantity *
                cartItems.value[index].purchase_price;
        }
    }

    // 4. Hapus Item
    function removeItem(productId) {
        cartItems.value = cartItems.value.filter(
            (item) => item.product_id !== productId
        );
    }

    // 5. Reset Keranjang
    function clearCart() {
        cartItems.value = [];
        localStorage.removeItem(STORAGE_KEY);
    }

    // 6. Computed Properties (Ringkasan)
    const totalUnit = computed(() =>
        cartItems.value.reduce((sum, item) => sum + item.quantity, 0)
    );
    const totalBelanja = computed(() =>
        cartItems.value.reduce((sum, item) => sum + item.subtotal, 0)
    );
    const totalMacam = computed(() => cartItems.value.length);

    // 7. Auto Save ke LocalStorage
    watch(
        cartItems,
        (newVal) => {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(newVal));
        },
        { deep: true }
    );

    onMounted(() => {
        loadCart();
    });

    return {
        cartItems,
        addToCart,
        updateCartItem,
        removeItem,
        clearCart,
        totalUnit,
        totalBelanja,
        totalMacam,
    };
}
