import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";

export function usePosRecap(props) {
    const toast = useToast();
    const STORAGE_KEY = "POS_RECAP_DRAFT_V2";

    // --- STATE ---
    const searchQuery = ref("");
    const selectedCategory = ref("all");
    const selectedBrand = ref("all"); // [NEW]
    const selectedSort = ref("name_asc"); // [NEW]
    const selectedProductType = ref("all");
    const cart = ref([]);

    // Default Tanggal hari ini (YYYY-MM-DD)
    const transactionDate = ref(new Date().toISOString().slice(0, 10));

    // --- LOCAL STORAGE LOGIC ---
    // ... (omitted, no change needed here, reusing existing) ...

    // --- COMPUTED ---
    const filteredProducts = computed(() => {
        let items = props.products || [];

        // 1. Filter Category
        if (selectedCategory.value !== 'all') {
            items = items.filter(p => p.category_id === selectedCategory.value);
        }

        // 2. Filter Brand
        if (selectedBrand.value !== 'all') {
            items = items.filter(p => p.brand_id === selectedBrand.value);
        }

        // 3. Search
        const q = searchQuery.value.toLowerCase().trim();
        if (q) {
            items = items.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.code && p.code.toLowerCase().includes(q))
            );
        }

        // 4. Sort
        items = [...items].sort((a, b) => {
            switch (selectedSort.value) {
                case 'name_asc': return a.name.localeCompare(b.name);
                case 'name_desc': return b.name.localeCompare(a.name);
                case 'price_high': return b.selling_price - a.selling_price;
                case 'price_low': return a.selling_price - b.selling_price;
                case 'most_sold': return (b.total_sold || 0) - (a.total_sold || 0);
                default: return 0;
            }
        });

        return items;
    });

    const totalRevenue = computed(() => {
        return cart.value.reduce((total, item) => total + (item.selling_price * item.quantity), 0);
    });

    const totalProfit = computed(() => {
        return cart.value.reduce((total, item) => {
            const revenue = item.selling_price * item.quantity;
            const cost = (item.purchase_price || 0) * item.quantity;
            return total + (revenue - cost);
        }, 0);
    });

    const currentProductType = computed(() => {
        // Placeholder if needed for subcategories
        return [];
    });

    // --- ACTIONS ---
    const addToCart = (product) => {
        if (product.stock <= 0) return toast.warning("Stok Habis!");

        const existing = cart.value.find(c => c.id === product.id);
        if (existing) {
            if (existing.quantity >= product.stock) {
                return toast.warning("Stok tidak cukup!");
            }
            existing.quantity++;
        } else {
            cart.value.push({
                id: product.id,
                name: product.name,
                code: product.code,
                selling_price: parseFloat(product.selling_price),
                purchase_price: parseFloat(product.purchase_price),
                stock: product.stock,
                quantity: 1
            });
        }
    };

    const removeItem = (index) => {
        cart.value.splice(index, 1);
    };

    const clearCart = () => {
        cart.value = [];
        localStorage.removeItem(STORAGE_KEY);
    };

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

    // Watcher dipindah ke sini agar 'form' sudah terdefinisi
    watch(
        [cart, transactionDate, () => form.notes],
        () => {
            if (props.mode !== 'edit') {
                localStorage.setItem(STORAGE_KEY, JSON.stringify({
                    cart: cart.value,
                    date: transactionDate.value,
                    notes: form.notes
                }));
            }
        },
        { deep: true }
    );

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
            quantity: item.quantity,
            selling_price: item.selling_price,
            purchase_price: item.purchase_price,
        }));

        form.total_revenue = parseFloat(totalRevenue.value);
        form.total_profit = parseFloat(totalProfit.value); // Needs calculation fix if purchase_price missing

        // Rekap dianggap uang pas
        form.payment_amount = parseFloat(totalRevenue.value);
        form.change_amount = 0;

        const options = {
            onSuccess: (page) => {
                if (page.props.flash.error) {
                    toast.error(page.props.flash.error);
                    return;
                }

                if (props.mode !== 'edit') {
                    cart.value = [];
                    localStorage.removeItem(STORAGE_KEY);
                }
                toast.success("Rekap Berhasil Disimpan!");
            },
            onError: (err) => {
                console.error(err);
                toast.error("Gagal menyimpan rekap.");
            },
        };

        if (props.mode === 'edit') {
            form.put(route("sales.update", props.sale.id), options);
        } else {
            form.post(route("sales.pos.store"), options);
        }
    };

    return {
        // State
        searchQuery,
        selectedCategory,
        selectedBrand, // [NEW]
        selectedSort, // [NEW]
        selectedProductType,
        // currentProductType,
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
