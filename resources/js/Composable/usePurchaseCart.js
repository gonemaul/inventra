import { ref, computed, watch, onMounted } from "vue";

export function usePurchaseCart(isEdit = false, purchase = {}) {
    // State Keranjang
    const cartItems = ref([]);
    const getStorageKey = () => {
        // Jika Mode Edit: "cart_edit_123"
        if (isEdit && purchase?.id) {
            return `inventra_edit_${purchase.id}`;
        }
        // Jika Mode Create: "cart_draft_new"
        return "inventra_create_draft";
    };

    const loadCart = () => {
        const storageKey = getStorageKey();
        const savedLocal = localStorage.getItem(storageKey);

        // 1. CEK LOCAL STORAGE DULU (Prioritas Tertinggi)
        // Jika ada data di local, artinya user pernah mengedit ini sebelumnya (dan belum submit)
        // Kita pakai data ini agar perubahan dia tidak hilang tertimpa data DB lama.
        if (savedLocal) {
            try {
                cartItems.value = JSON.parse(savedLocal);
                return; // STOP DISINI. Jangan load dari DB.
            } catch (e) {
                console.error("Local data corrupt, clearing...");
                localStorage.removeItem(storageKey);
            }
        }

        // 2. JIKA LOCAL KOSONG & ADA DATA DB (Initial Load Edit)
        // Ini terjadi saat pertama kali user klik tombol Edit dari halaman Index
        if (isEdit && purchase && purchase.items) {
            cartItems.value = [];
            // Mapping Data DB -> Format Cart
            addMultipleItems(purchase.items);
        }

        // 3. JIKA CREATE MODE
        else {
            // Karena di step 1 sudah dicek savedLocal (dan kosong),
            // berarti ini benar-benar buat baru bersih.
            cartItems.value = [];
        }
    };

    // 2. Tambah Item Baru (Mode: ADD)
    // Jika barang sudah ada, kita tambahkan jumlahnya (merge)
    function addToCart(product, quantity, price) {
        console.log(product);
        const targetId = product.product_id;
        const qty = parseInt(quantity);
        const buyPrice = parseFloat(price);

        // Barang baru? Push ke array
        // Kita simpan SNAPSHOT data produk di sini untuk tabel & backend
        cartItems.value.push({
            id: targetId ? product.id : "",
            product_id: targetId ?? product.id,
            // Snapshot Data Produk (Read-only di tabel)
            name: product.name,
            code: product.code,

            // Robust Mapping for Nested Objects (Catalog vs Recom vs DB)
            brand: product.brand?.name ?? product.brand ?? "-",
            category: product.category?.name ?? product.category ?? "-",
            unit: product.unit?.name ?? product.unit ?? "-",
            size: product.size?.name ?? product.size ?? "-",

            current_stock: product.stock ?? product.current_stock ?? 0,
            min_stock: product.min_stock ?? 0,
            image_url: product.image_url ?? product.image_path ?? "",
            image_path: product.image_path ?? "",

            // Data Transaksi (Editable)
            quantity: qty,
            purchase_price: buyPrice,
            subtotal: qty * buyPrice,
        });
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

    function removeMultipleItems(productIds) {
        console.log("Removing multiple:", productIds);
        cartItems.value = cartItems.value.filter(
            (item) => !productIds.includes(item.product_id)
        );
    }

    // 5. Reset Keranjang
    function clearCart() {
        cartItems.value = [];
        localStorage.removeItem(getStorageKey);
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
                brand: item.product_snapshot?.brand ?? item.brand,
                category: item.product_snapshot?.category ?? item.category,
                stock: item.product_snapshot?.stock ?? item.current_stock, // Master data stock
                image_url: item.product_snapshot?.image_url ?? item.image_url,
                image_path: item.product_snapshot?.image_path ?? item.image_path,
                min_stock: item.product_snapshot?.min_stock ?? item.min_stock,
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
            const key = getStorageKey();
            localStorage.setItem(key, JSON.stringify(newVal));
        },
        { deep: true } // Deep watch agar perubahan properti qty di dalam object terdeteksi
    );

    onMounted(() => {
        loadCart();
    });

    return {
        cartItems,
        addToCart,
        updateCartItem,
        removeItem,
        removeMultipleItems,
        clearCart,
        totalUnit,
        totalBelanja,
        totalMacam,
        addMultipleItems,
    };
}
