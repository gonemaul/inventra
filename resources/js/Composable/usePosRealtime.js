import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import debounce from "lodash/debounce";

export function usePosRealtime(props) {
    const toast = useToast();
    const STORAGE_KEY = "POS_REALTIME_DRAFT_V3";

    // =========================================================================
    // 1. STATE & FORM DEFINITION
    // =========================================================================

    // A. Product & Filter State
    const allProducts = ref([]);
    const isFetchingData = ref(false);
    const displayLimit = ref(20);
    const filterState = ref({
        search: "",
        category: "all",
        subCategory: "all",
        brand: "all",
        sort: "default",
        hideEmptyStock: true,
    });

    // Comparison State
    const compareList = ref([]);

    // B. Member State
    const memberSearch = ref("");
    const memberSearchResults = ref([]);
    const selectedMember = ref(null);

    // C. Main Transaction Form
    const form = useForm({
        input_type: "realtime",
        // FIX: Gunakan Waktu Lokal (bukan UTC) untuk menghindari tanggal mundur jika jam < 7 pagi
        report_date: (() => {
            const d = new Date();
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        })(),
        items: [],
        customer_id: null,
        payment_amount: 0,
        change_amount: 0,
        payment_method: "cash",
        discount_type: "fixed",
        discount_value: 0,
        notes: "",
    });

    // =========================================================================
    // 2. COMPUTED: FINANCIAL CALCULATIONS
    // =========================================================================

    const subTotal = computed(() => {
        return form.items.reduce(
            (sum, item) => sum + (parseFloat(item.subtotal) || 0),
            0
        );
    });

    const discountAmount = computed(() => {
        const val = parseFloat(form.discount_value) || 0;
        if (val <= 0) return 0;

        if (form.discount_type === "percent") {
            const percent = val > 100 ? 100 : val;
            return Math.round((subTotal.value * percent) / 100);
        }
        return val > subTotal.value ? subTotal.value : val;
    });

    const grandTotal = computed(() => {
        const total = subTotal.value - discountAmount.value;
        const grand = total > 0 ? total : 0;

        // Reset payment jika total 0 (opsional logic)
        if (grand === 0 && form.payment_amount > 0) {
            // form.payment_amount = 0; // Uncomment jika ingin auto reset
        }
        return grand;
    });

    const changeAmount = computed(() => {
        const pay = parseFloat(form.payment_amount) || 0;
        return pay - grandTotal.value;
    });

    const isPaymentSufficient = computed(() => {
        if (form.payment_method !== "cash" && form.payment_method !== "")
            return true;
        return (parseFloat(form.payment_amount) || 0) >= grandTotal.value;
    });

    const hasInvalidQty = computed(() => {
        return form.items.some(
            (item) => item.quantity <= 0 || isNaN(item.quantity)
        );
    });

    const moneySuggestions = computed(() => {
        const total = grandTotal.value;
        if (total <= 0) return [];

        const suggestions = [{ label: "Uang Pas", value: total }];
        const fractions = [2000, 5000, 10000, 20000, 50000, 100000];

        fractions.forEach((frac) => {
            if (frac > total && !suggestions.find((s) => s.value === frac)) {
                suggestions.push({ label: rp(frac), value: frac });
            }
        });

        if (total > 50000) {
            const next50 = Math.ceil(total / 50000) * 50000;
            if (!suggestions.find((s) => s.value === next50)) {
                suggestions.push({ label: rp(next50), value: next50 });
            }
        }
        return suggestions.slice(0, 4);
    });

    // =========================================================================
    // 3. CART ACTIONS (ADD, REMOVE, RECALC)
    // =========================================================================

    // Recalculation Helpers
    const recalcFromQty = (item) => {
        const isDecimal = item.unit?.is_decimal === 1;
        if (!isDecimal) {
            item.quantity = Math.round(item.quantity);
            if (item.quantity < 1) item.quantity = 1;
        }
        if (item.quantity > item.stock_max) {
            item.quantity = item.stock_max;
            toast.warning(`Stok sisa ${item.stock_max}`);
        }
        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    const recalcFromSubtotal = (item) => {
        const targetSubtotal = parseFloat(item.subtotal) || 0;
        const currentPrice = parseFloat(item.selling_price) || 0;
        if (currentPrice <= 0) return;

        if (item.unit?.is_decimal === 1) {
            let newQty = targetSubtotal / currentPrice;
            if (newQty > item.stock_max) {
                newQty = item.stock_max;
                item.subtotal = Math.round(newQty * currentPrice);
                toast.warning("Melebihi sisa stok!");
            }
            item.quantity = parseFloat(newQty.toFixed(4));
        } else {
            if (
                confirm(
                    `⚠️ Ubah harga satuan agar sesuai total ${rp(
                        targetSubtotal
                    )}?`
                )
            ) {
                if (item.quantity > 0) {
                    item.selling_price = Math.round(
                        targetSubtotal / item.quantity
                    );
                }
            } else {
                item.subtotal = Math.round(item.quantity * item.selling_price);
            }
        }
    };

    const recalcFromPrice = (item) => {
        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    // Cart Management
    const addItem = (product) => {
        if (parseFloat(product.stock) <= 0) {
            toast.error("Stok habis!");
            return;
        }

        const existingItem = form.items.find(
            (i) => i.product_id === product.id
        );

        if (existingItem) {
            if (existingItem.quantity + 1 > product.stock) {
                toast.warning("Stok maksimal!");
                return;
            }
            existingItem.quantity++;
            recalcFromQty(existingItem);
        } else {
            const price = parseFloat(product.selling_price || product.price);
            form.items.push({
                product_id: product.id,
                code: product.code,
                name: product.name,
                unit: product.unit,
                size: product.size,
                stock_max: parseFloat(product.stock),
                selling_price: price,
                original_price: price,
                quantity: 1,
                subtotal: price,
            });
        }
    };

    const removeItem = (index) => form.items.splice(index, 1);

    const updateQty = (index, change) => {
        const item = form.items[index];
        const newQty = parseFloat(item.quantity) + change;

        if (newQty > item.stock_max) {
            toast.warning(`Stok sisa ${item.stock_max}`);
            return;
        }
        if (newQty <= 0) {
            if (confirm("Hapus item ini?")) removeItem(index);
            return;
        }
        item.quantity = newQty;
        recalcFromQty(item);
    };

    const handleMoneyClick = (suggestion) => {
        form.payment_amount = suggestion.value;
    };

    const resetPayment = () => {
        form.payment_amount = 0;
    };

    // =========================================================================
    // 4. PRODUCT & FILTER LOGIC
    // =========================================================================

    // =========================================================================
    // 4. PRODUCT & FILTER LOGIC (SERVER-SIDE)
    // =========================================================================

    // Cache Memory (In-Memory per session)
    // Key: JSON.stringify(queryParams)
    // Value: Array of products
    const productCache = ref(new Map());

    // Fungsi load product dari server dengan parameter search & limit
    const loadProduct = async (params = {}) => {
        // Merge params dengan default
        const queryParams = {
            query: params.search || filterState.value.search,
            limit: displayLimit.value,
            category_id: filterState.value.category,
            product_type_id: filterState.value.subCategory,
            brand_id: filterState.value.brand, // New addition
            sort: filterState.value.sort,
            hide_empty_stock: filterState.value.hideEmptyStock ? 1 : 0, // Changed to 1 or 0
            // page: page, // 'page' is not defined in the current scope
        };

        const cacheKey = JSON.stringify(queryParams);

        // 1. Cek Cache (Client-Side Feel)
        if (productCache.value.has(cacheKey)) {
            allProducts.value = productCache.value.get(cacheKey);
            // Opsional: Tetap fetch di background untuk update data (Stale-While-Revalidate)
            // Tapi untuk POS, "Feel Cepat" > "Data Detik Ini Update". 
            // Kita skip fetch background jika sudah ada cache, KECUALI user memaksa refresh / search.
            // Namun, stok mungkin berubah. Jadi kita bisa implement strategy:
            // "Pakai Cache Dulu, Fetch Background Nanti"
        } else {
            isFetchingData.value = true;
        }

        try {
            // Fetch Background (atau Fetch First jika belum ada cache)
            const response = await axios.get(route("sales.products.lite"), {
                params: queryParams
            });

            allProducts.value = response.data;
            // Simpan ke Cache
            productCache.value.set(cacheKey, response.data);
        } catch (e) {
            console.error("Gagal load produk:", e);
            if (!productCache.value.has(cacheKey)) {
                toast.error("Gagal memuat database produk");
            }
        } finally {
            isFetchingData.value = false;
        }
    };

    // Debounce search untuk mengurangi request server
    const debouncedSearch = debounce(() => {
        loadProduct();
    }, 400); // Tunggu 400ms setelah user stop ngetik

    // Watch perubahan filter
    watch(
        () => filterState.value,
        (newVal, oldVal) => {
            // Jika search berubah, pakai debounce
            if (newVal.search !== oldVal.search) {
                isFetchingData.value = true;
                debouncedSearch();
            } else {
                // Jika kategori/sort berubah, load langsung (reset limit idealnya, tapi user scrolling behavior varies)
                // displayLimit.value = 20; // Opsional: Reset scroll saat ganti kategori

                // Clear products for loading effect if category changed
                if (newVal.category !== oldVal.category) {
                    allProducts.value = [];
                }

                loadProduct();
            }
        },
        { deep: true }
    );

    // Filtered Products sekarang MURNI dari server
    // Sorting bisa dilakukan di client untuk data yang sudah tampak (karena sorting server side butuh reload)
    // Tapi jika kita load partial, sorting client side hanya mengurutkan 'what is visible'.
    // Idealnya sorting dikirim ke server juga. 
    // Tapi untuk responsiveness POS, sorting client side pada 'Top 20' hasil search biasanya cukup.
    const filteredProducts = computed(() => {
        let result = allProducts.value;
        const { sort } = filterState.value;

        // Sorting (Client Side pada hasil Server Side)
        if (sort === "cheapest") {
            result.sort((a, b) => a.selling_price - b.selling_price);
        } else if (sort === "bestseller") {
            result.sort((a, b) => (b.total_sold || 0) - (a.total_sold || 0));
        } else {
            result.sort((a, b) => a.name.localeCompare(b.name));
        }

        return result;
    });

    const loadMoreProducts = () => {
        // Simple Infinite Scroll: Tambah limit dan request ulang
        displayLimit.value += 12;
        loadProduct();
    };

    // =========================================================================
    // 5. MEMBER & SCANNER LOGIC
    // =========================================================================

    // Member
    const selectMember = (member) => {
        selectedMember.value = member;
        form.customer_id = member.id;
        memberSearchResults.value = [];
        memberSearch.value = "";
    };

    const removeMember = () => {
        selectedMember.value = null;
        form.customer_id = null;
    };

    // Watcher untuk pencarian member (pengganti computed sebelumnya)
    watch(memberSearch, (val) => {
        if (!val || !props.customers) {
            memberSearchResults.value = [];
            return;
        }
        const q = val.toLowerCase();
        const result = props.customers.filter(
            (c) =>
                c.name.toLowerCase().includes(q) ||
                c.code.toLowerCase().includes(q)
        );
        memberSearchResults.value = result.slice(0, 5);
    });

    // Scanner / Query
    const queryProduk = (code) => {
        const product = allProducts.value.find((p) => p.code == code);
        if (product) {
            if (product.stock <= 0) {
                toast.warning(`Stok produk "${product.name}" habis!`);
                return null;
            }
            return product;
        }
        return null;
    };

    const queryMember = (code) => {
        const member = props.customers.find(
            (c) => c.member_code == code || c.phone == code
        );
        if (member) {
            selectMember(member);
        } else {
            toast.warning("Member tidak ditemukan");
        }
    };

    // =========================================================================
    // 6. PERSISTENCE (LOCAL STORAGE)
    // =========================================================================

    const restoreSession = () => {
        const savedData = localStorage.getItem(STORAGE_KEY);
        if (savedData) {
            try {
                const parsed = JSON.parse(savedData);
                if (parsed.items) form.items = parsed.items;
                form.payment_amount = parsed.payment_amount || 0;
                form.payment_method = parsed.payment_method || "cash";
                form.notes = parsed.notes || "";
                form.discount_type = parsed.discount_type || "fixed";
                form.discount_value = parsed.discount_value || 0;

                if (parsed.selectedMember) {
                    selectedMember.value = parsed.selectedMember;
                    form.customer_id = parsed.selectedMember.id;
                }
            } catch (e) {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    };

    watch(
        [
            () => form.items,
            () => form.customer_id,
            () => form.payment_amount,
            () => form.discount_value,
            () => form.notes,
        ],
        () => {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    items: form.items,
                    customer_id: form.customer_id,
                    payment_amount: form.payment_amount,
                    payment_method: form.payment_method,
                    notes: form.notes,
                    discount_type: form.discount_type,
                    discount_value: form.discount_value,
                    selectedMember: selectedMember.value,
                })
            );
        },
        { deep: true }
    );

    // =========================================================================
    // 7. TRANSACTION SUBMIT & UTILS
    // =========================================================================

    const submitTransaction = (printInvoice = false, callbacks = {}) => {
        form.change_amount = changeAmount.value;
        form.payment_amount = parseFloat(form.payment_amount);

        form.transform((data) => ({
            ...data,
            print_invoice: printInvoice,
        })).post(route("sales.pos.store"), {
            onStart: callbacks.onStart,
            onFinish: callbacks.onFinish,
            onSuccess: (page) => {
                loadProduct(); // Reload stok
                localStorage.removeItem(STORAGE_KEY);
                form.reset();
                form.items = [];
                selectedMember.value = null;
                if (callbacks.onSuccess) callbacks.onSuccess(page);
            },
            onError: (err) => {
                console.error(err);
                toast.error("Gagal memproses transaksi");
            },
        });
    };

    const rp = (val) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(val);

    // =========================================================================
    // 8. COMPARISON LOGIC
    // =========================================================================

    const toggleCompare = (product) => {
        const index = compareList.value.findIndex((p) => p.id === product.id);
        if (index !== -1) {
            compareList.value.splice(index, 1);
        } else {
            if (compareList.value.length >= 5) {
                toast.warning("Maksimal 5 produk untuk perbandingan.");
                return;
            }
            compareList.value.push(product);
        }
    };

    const clearCompare = () => {
        compareList.value = [];
    };

    const isInCompare = (productId) => {
        return compareList.value.some(p => p.id === productId);
    };

    // =========================================================================
    // 8. LIFECYCLE
    // =========================================================================

    onMounted(() => {
        loadProduct();
        restoreSession();
    });

    return {
        // State
        form,
        filterState,
        allProducts,
        filteredProducts,
        isFetchingData,
        compareList,

        // Member State
        memberSearch,
        memberSearchResults,
        selectedMember,

        // Computed
        subTotal,
        discountAmount,
        grandTotal,
        changeAmount,
        isPaymentSufficient,
        hasInvalidQty,
        moneySuggestions,

        loadMoreProducts,
        queryMember,
        addItem,
        removeItem,
        updateQty,
        resetPayment,
        handleMoneyClick,
        recalcFromQty,
        recalcFromSubtotal,
        recalcFromPrice,

        // Actions: Compare
        toggleCompare,
        clearCompare,
        isInCompare,

        // Transaction
        submitTransaction,
        rp,
    };
}
