import { ref, computed, watch, onMounted } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";
import { useToast } from "vue-toastification";

export function usePosRealtime(props) {
    // --- STATE ---
    const toast = useToast();
    const STORAGE_KEY = "POS_REALTIME_DRAFT_V1"; // Key BEDA dengan Recap

    const form = useForm({
        input_type: "realtime", // Penanda khusus Realtime
        report_date: new Date().toISOString().substr(0, 10), // Tetap kirim tanggal hari ini
        items: [],

        // --- TAMBAHAN KHUSUS POS ---
        customer_id: null,
        payment_amount: 0,
        change_amount: 0,
        payment_method: "cash",
        notes: "",
    });

    // State Produk
    const searchResults = ref([]);
    const isLoadingSearch = ref(false);

    // State Member (Baru)
    const memberSearchResults = ref([]);
    const isLoadingMember = ref(false);
    const selectedMember = ref(null);

    // --- CONFIG (SAMA) ---
    const DECIMAL_UNITS = [
        "kg",
        "kilogram",
        "gram",
        "gr",
        "g",
        "ons",
        "ton",
        "kwintal",
        "mg",
        "liter",
        "ltr",
        "l",
        "ml",
        "cc",
        "m3",
        "kubik",
        "galon",
        "meter",
        "mtr",
        "m",
        "cm",
        "mm",
        "m2",
        "yard",
        "kaki",
        "inch",
        "inci",
    ];

    // --- 1. LOCAL STORAGE LOGIC ---
    onMounted(() => {
        const savedData = localStorage.getItem(STORAGE_KEY);
        if (savedData) {
            try {
                const parsed = JSON.parse(savedData);

                // Restore Items
                if (parsed.items && parsed.items.length > 0) {
                    form.items = parsed.items;
                }

                // Restore Payment & Notes
                form.payment_amount = parsed.payment_amount || 0;
                form.payment_method = parsed.payment_method || "cash";
                form.notes = parsed.notes || "";

                // Restore Member
                if (parsed.selectedMember) {
                    selectedMember.value = parsed.selectedMember;
                    form.customer_id = parsed.selectedMember.id;
                }
            } catch (e) {
                console.error("Error parsing local storage", e);
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    });

    // Simpan setiap kali ada perubahan data penting
    watch(
        [
            () => form.items,
            () => form.customer_id,
            () => form.payment_amount,
            () => selectedMember.value,
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
                    selectedMember: selectedMember.value, // Simpan object member biar nama gak ilang
                })
            );
        },
        { deep: true }
    );

    // --- HELPERS (SAMA) ---
    const isDecimalAllowed = (unit) =>
        unit && DECIMAL_UNITS.includes(unit.toLowerCase());

    const checkIntegerInput = (event, unit) => {
        if (isDecimalAllowed(unit)) return;
        if (event.key === "." || event.key === ",") event.preventDefault();
    };

    const formatCurrency = (val) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(val);

    // --- COMPUTED VALIDATIONS ---
    const hasInvalidQty = computed(() => {
        return form.items.some((item) => parseFloat(item.quantity) <= 0);
    });

    const hasStockError = computed(() => {
        return form.items.some(
            (item) => parseFloat(item.quantity) > parseFloat(item.stock_max)
        );
    });

    const grandTotal = computed(() =>
        form.items.reduce(
            (sum, item) => sum + (parseFloat(item.subtotal) || 0),
            0
        )
    );

    // Hitung Kembalian Otomatis
    const changeAmount = computed(() => {
        const pay = parseFloat(form.payment_amount) || 0;
        const total = grandTotal.value;
        return pay - total;
    });

    const isPaymentSufficient = computed(() => {
        return (parseFloat(form.payment_amount) || 0) >= grandTotal.value;
    });

    // --- ACTIONS ---

    // 1. Product Actions
    const addItem = (product) => {
        if (parseFloat(product.stock) <= 0) {
            toast.error("Stok habis. Tidak bisa ditambahkan.");
            return;
        }

        const existingItem = form.items.find(
            (i) => i.product_id === product.id
        );

        if (existingItem) {
            if (existingItem.quantity + 1 > product.stock) {
                toast.error("Melebihi sisa stok!");
                return;
            }
            existingItem.quantity++;
            calculateSubtotal(existingItem);
        } else {
            form.items.push({
                product_id: product.id,
                code: product.code,
                name: product.name,
                unit: product.unit,
                category: product.category?.name || "-",
                brand: product.brand || "-",
                stock_max: parseFloat(product.stock),
                image: product.image_path,
                selling_price: parseFloat(
                    product.selling_price || product.price
                ), // Handle nama kolom beda
                quantity: 1,
                subtotal: parseFloat(product.selling_price || product.price),
                // Flag untuk POS
                is_price_locked: true,
            });
        }
    };

    const removeItem = (index) => form.items.splice(index, 1);

    // Helper khusus POS: Update Qty via Tombol +/-
    const updateQty = (index, change) => {
        const item = form.items[index];
        const newQty = parseFloat(item.quantity) + change;

        if (newQty > item.stock_max) {
            toast.error("Stok Maksimal!");
            return;
        }
        if (newQty <= 0) {
            // Opsional: Hapus jika 0 atau biarkan 0 tapi validasi nanti
            removeItem(index);
            return;
        }

        item.quantity = newQty;
        calculateSubtotal(item);
    };

    const calculateSubtotal = (item) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.selling_price) || 0;
        item.subtotal = Math.round(qty * price);
    };

    // Fungsi handle input manual qty (biar sinkron dengan logic rekap)
    const calculateQty = (item) => {
        // Di POS biasanya kita edit Qty -> Subtotal berubah.
        // Tapi kalau mau edit Subtotal -> Qty berubah (Logic Rekap), fungsi ini tetap ada.
        const total = parseFloat(item.subtotal) || 0;
        const price = parseFloat(item.selling_price) || 0;
        if (price > 0) {
            let result = total / price;
            item.quantity = isDecimalAllowed(item.unit)
                ? parseFloat(result.toFixed(4))
                : Math.round(result);
        }
    };

    // 2. Search Product Action
    const handleSearch = debounce(async (query) => {
        if (!query) {
            searchResults.value = [];
            return;
        }
        isLoadingSearch.value = true;
        try {
            const response = await axios.get(route("sales.search-product"), {
                params: { query },
            });
            searchResults.value = response.data;
        } catch (e) {
            console.error(e);
        } finally {
            isLoadingSearch.value = false;
        }
    }, 300);

    // 3. Search Member Action (BARU)
    const handleSearchMember = debounce(async (query) => {
        if (!query) {
            memberSearchResults.value = [];
            return;
        }
        isLoadingMember.value = true;
        try {
            // Asumsi ada route sales.search-customer
            const response = await axios.get(route("sales.search-customer"), {
                params: { query },
            });
            memberSearchResults.value = response.data;
        } catch (e) {
            console.error(e);
        } finally {
            isLoadingMember.value = false;
        }
    }, 300);

    const selectMember = (member) => {
        selectedMember.value = member;
        form.customer_id = member.id;
        memberSearchResults.value = []; // Clear hasil search
    };

    const removeMember = () => {
        selectedMember.value = null;
        form.customer_id = null;
    };

    // --- SUBMIT ---
    const submitForm = () => {
        // 1. Validasi Keranjang Kosong
        if (form.items.length === 0) {
            toast.error("Keranjang belanja kosong!");
            return;
        }

        // 2. Validasi Qty Invalid
        if (hasInvalidQty.value) {
            toast.error("Ada produk dengan Qty 0.");
            return;
        }

        // 3. Validasi Stok
        if (hasStockError.value) {
            const badItem = form.items.find(
                (item) => parseFloat(item.quantity) > parseFloat(item.stock_max)
            );
            toast.error(`Stok tidak cukup: ${badItem.name}`);
            return;
        }

        // 4. Validasi Pembayaran (KHUSUS POS)
        if (!isPaymentSufficient.value) {
            toast.error("Uang pembayaran kurang!");
            return;
        }

        // Hitung Kembalian sebelum kirim (Opsional, backend bisa hitung ulang)
        form.change_amount = changeAmount.value;

        // Post ke Route Store
        form.post(route("sales.pos.store"), {
            onSuccess: () => {
                localStorage.removeItem(STORAGE_KEY);
                form.reset();
                form.items = [];
                selectedMember.value = null;
                // Toast success otomatis dari Inertia flash message atau manual:
                toast.success("Transaksi Berhasil!");
            },
            onError: (errors) => {
                console.error(errors);
                toast.error("Gagal memproses transaksi.");
            },
        });
    };
    const submitTransaction = (printInvoice = false, callbacks = {}) => {
        // 1. Validasi Ulang (Safety Layer)
        if (form.items.length === 0) return toast.error("Keranjang kosong!");
        if (hasInvalidQty.value) return toast.error("Qty tidak valid!");
        if (hasStockError.value) return toast.error("Stok tidak cukup!");
        if (!isPaymentSufficient.value) return toast.error("Uang kurang!");

        // Hitung kembalian final
        form.change_amount = changeAmount.value;

        // 2. Transform Data (Sisipkan flag print) & POST
        form.transform((data) => ({
            ...data,
            print_invoice: printInvoice, // Kirim status cetak ke backend
        })).post(route("sales.pos.store"), {
            onStart: () => {
                if (callbacks.onStart) callbacks.onStart();
            },
            onFinish: () => {
                if (callbacks.onFinish) callbacks.onFinish();
            },
            onSuccess: (page) => {
                // --- CLEANUP (RESET DATA) ---
                // Ini yang kemarin hilang di Index.vue
                localStorage.removeItem(STORAGE_KEY);

                form.reset();
                form.items = []; // Kosongkan cart visual

                selectedMember.value = null;
                memberSearchResults.value = [];

                toast.success("Transaksi Berhasil!");

                // Cek URL Print dari Backend
                const printUrl = page.props.flash?.print_url;
                if (printInvoice && printUrl) {
                    window.open(printUrl, "_blank", "width=400,height=600");
                }

                if (callbacks.onSuccess) callbacks.onSuccess();
            },
            onError: (errors) => {
                console.error(errors);
                toast.error("Gagal menyimpan transaksi.");
            },
        });
    };

    return {
        // State
        form,
        searchResults,
        isLoadingSearch,

        // State Member
        memberSearchResults,
        isLoadingMember,
        selectedMember,

        // Actions Product
        addItem,
        removeItem,
        updateQty, // New helper
        handleSearch,
        calculateSubtotal,
        calculateQty,

        // Actions Member
        handleSearchMember,
        selectMember,
        removeMember,

        // Helper & Validations
        checkIntegerInput,
        formatCurrency,
        grandTotal,
        hasInvalidQty,
        hasStockError,

        // Payment Validations
        changeAmount,
        isPaymentSufficient,

        // Core
        submitTransaction,
    };
}
