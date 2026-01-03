import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";

export function usePosRecap(props) {
    const toast = useToast();
    const STORAGE_KEY = "POS_RECAP_DRAFT_V2";

    // --- STATE ---
    const searchQuery = ref("");
    const selectedCategory = ref("all");
    const selectedProductType = ref("all");
    const cart = ref([]);

    // Default Tanggal hari ini (YYYY-MM-DD)
    const transactionDate = ref(new Date().toISOString().slice(0, 10));

    // --- LOCAL STORAGE LOGIC ---
    onMounted(() => {
        const saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                cart.value = parsed.cart || [];
                transactionDate.value =
                    parsed.date || new Date().toISOString().slice(0, 10);
            } catch (e) {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    });

    watch(
        [cart, transactionDate],
        () => {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    cart: cart.value,
                    date: transactionDate.value,
                })
            );
        },
        { deep: true }
    );

    // --- CART ACTIONS ---

    // 1. Tambah Produk
    const addToCart = (product) => {
        if (product.stock <= 0) {
            toast.error(`Stok ${product.name} Habis!`);
            return;
        }

        const existing = cart.value.find((item) => item.id === product.id);

        if (existing) {
            // Cek stok sebelum nambah
            if (existing.quantity < product.stock) {
                existing.quantity++;
            } else {
                toast.warning(`Stok maksimal tercapai (${product.stock})`);
            }
        } else {
            // Item Baru
            cart.value.push({
                id: product.id,
                name: product.name,
                code: product.code,
                image: product.image_path, // Pastikan sesuai nama kolom di DB
                selling_price: product.selling_price, // Harga Jual (Bisa diedit nanti)
                purchase_price: product.purchase_price, // HPP (Disimpan untuk profit)
                stock: product.stock, // Untuk validasi
                quantity: 1,
            });
        }
    };

    // 2. Hapus Item
    const removeItem = (index) => {
        cart.value.splice(index, 1);
    };

    // 3. Reset Cart
    const clearCart = () => {
        if (confirm("Hapus semua data rekap?")) {
            cart.value = [];
            localStorage.removeItem(STORAGE_KEY);
        }
    };

    // --- SEARCH & FILTER ---
    const currentProductType = computed(() => {
        if (selectedCategory.value === "all") return [];

        const category = props.categories.find(
            (c) => c.id === selectedCategory.value
        );
        return category ? category.product_types : [];
    });

    watch(selectedCategory, () => {
        selectedProductType.value = "all";
    });

    const filteredProducts = computed(() => {
        let items = props.products || [];

        // Filter Kategori
        if (selectedCategory.value !== "all") {
            items = items.filter(
                (p) => p.category_id === selectedCategory.value
            );
        }
        // Filter Product Type
        if (selectedProductType.value !== "all") {
            items = items.filter(
                (p) => p.product_type_id === selectedProductType.value
            );
        }

        // Filter Search (Nama atau Kode)
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            items = items.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    (p.code && p.code.toLowerCase().includes(q))
            );
        }
        return items;
    });

    // --- CALCULATIONS ---
    const totalRevenue = computed(() => {
        return cart.value.reduce(
            (acc, item) => acc + item.selling_price * item.quantity,
            0
        );
    });

    const totalProfit = computed(() => {
        return cart.value.reduce((acc, item) => {
            const margin = item.selling_price - item.purchase_price;
            return acc + margin * item.quantity;
        }, 0);
    });

    // --- SUBMIT RECAP ---
    const form = useForm({
        input_type: "recap",
        report_date: null, // Diisi saat submit
        items: [],
        total_revenue: 0,
        total_profit: 0,

        // Data Pembayaran (Untuk Rekap dianggap Tunai Pas)
        payment_method: "cash",
        payment_amount: 0,
        change_amount: 0,

        customer_id: null,
        notes: "",
    });

    const processRecap = () => {
        // Validasi Dasar
        if (cart.value.length === 0) return toast.error("Data masih kosong!");
        if (!transactionDate.value) return toast.error("Pilih tanggal rekap!");

        // Validasi Qty Invalid
        const hasInvalidQty = cart.value.some((item) => item.quantity <= 0);
        if (hasInvalidQty) return toast.error("Ada produk dengan jumlah 0!");

        // Mapping Data ke Form
        form.report_date = transactionDate.value;
        form.items = cart.value.map((item) => ({
            product_id: item.id,
            quantity: item.quantity, // Frontend pakai 'qty', Controller harus mapping ke 'quantity' atau sebaliknya
            selling_price: item.selling_price,
            purchase_price: item.purchase_price,
        }));

        form.total_revenue = parseFloat(totalRevenue.value);
        form.total_profit = parseFloat(totalProfit.value);

        // Rekap dianggap uang pas
        form.payment_amount = parseFloat(totalRevenue.value);
        form.change_amount = 0;

        // Kirim
        form.post(route("sales.pos.store"), {
            onSuccess: () => {
                cart.value = [];
                localStorage.removeItem(STORAGE_KEY);
                // toast.success("Rekap Berhasil Disimpan!");
            },
            onError: (err) => {
                console.error(err);
                toast.error("Gagal menyimpan rekap.");
            },
        });
    };

    return {
        // State
        searchQuery,
        selectedCategory,
        selectedProductType,
        currentProductType,
        cart,
        transactionDate,
        form, // Untuk loading state (form.processing)

        // Computed
        filteredProducts,
        totalRevenue,
        totalProfit,

        // Actions
        addToCart,
        removeItem,
        clearCart,
        processRecap,
    };
}
