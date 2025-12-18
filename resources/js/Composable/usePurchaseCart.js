import { ref, computed, watch, onMounted } from "vue";

const STORAGE_KEY = "inventra_purchase_cart_temp";

export function usePurchaseCart(isEdit = false, purchase = {}) {
    // State Keranjang
    const cartItems = ref([]);

    // 1. Load dari Cache Browser
    function loadCart() {
        // 1. LOGIKA EDIT MODE
        // Jika sedang Edit, kita Wajib pakai data dari Database (props purchase)
        // Abaikan LocalStorage agar draft lama tidak menimpa data edit yang valid
        if (isEdit) {
            if (purchase && purchase.items) {
                // Pastikan cart bersih dulu sebelum diisi (menghindari duplikasi jika fungsi dipanggil 2x)
                cartItems.value = [];

                // Masukkan item dari database ke cart
                // Pastikan addMultipleItems menangani mapping field (id, qty, price) dengan benar
                addMultipleItems(purchase.items);
            }
            return; // PENTING: Berhenti di sini. Jangan jalankan kode di bawahnya.
        }

        // 2. LOGIKA CREATE MODE
        // Jika bukan Edit, baru kita cek apakah ada draft tersimpan di LocalStorage
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            try {
                cartItems.value = JSON.parse(saved);
            } catch (e) {
                // Jika JSON rusak, hapus storage biar bersih
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    }

    // 2. Tambah Item Baru (Mode: ADD)
    // Jika barang sudah ada, kita tambahkan jumlahnya (merge)
    function addToCart(product, quantity, price, isDraft = false) {
        const targetId = product.product_id;
        const qty = parseInt(quantity);
        const buyPrice = parseFloat(price);

        const existingItem = cartItems.value.find(
            (item) => item.product_id === targetId
        );

        if (existingItem && isDraft) {
            // Barang ada? Tambahkan quantity-nya
            existingItem.quantity += qty;
            existingItem.purchase_price = buyPrice; // Update harga ke input terakhir
            existingItem.subtotal = existingItem.quantity * buyPrice;
        } else {
            // Barang baru? Push ke array
            // Kita simpan SNAPSHOT data produk di sini untuk tabel & backend
            cartItems.value.push({
                id: targetId ? product.id : "",
                product_id: targetId ?? product.id,
                // Snapshot Data Produk (Read-only di tabel)
                name: product.name,
                code: product.code,
                category: product.category?.name || product.category || "-",
                unit: product.unit?.name || product.unit || "-",
                size: product.size?.name || product.size || "-",
                current_stock: product.stock || product.current_stock,
                image_url: product.image_url || "",

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

    function addMultipleItems(itemsArray) {
        itemsArray.forEach((item) => {
            // Kita format ulang objek agar cocok dengan parameter 'addItem'
            const productData = {
                id: item.id ?? "",
                product_id: item.product_id,
                name: item.product_snapshot?.name ?? item.name,
                code: item.product_snapshot?.code ?? item.code,
                unit: item.product_snapshot?.unit ?? item.unit,
                size: item.product_snapshot?.size ?? item.size,
                category: item.product_snapshot?.category ?? item.category,
                stock: item.product_snapshot?.stock ?? item.current_stock, // Master data stock
                image_url: item.product_snapshot?.image_url ?? "",
                // Master data fields lain bisa ditambahkan di sini
            };
            // Panggil fungsi addItem yang sudah ada untuk setiap item
            addToCart(productData, item.quantity, item.purchase_price);
        });
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
        addMultipleItems,
    };
}
