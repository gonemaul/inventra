import {
    ref,
    computed,
    watch,
    onMounted,
    onBeforeUnmount,
    nextTick,
} from "vue";
import { Html5Qrcode, Html5QrcodeSupportedFormats } from "html5-qrcode";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";
import { useToast } from "vue-toastification";

export function usePosRealtime(props) {
    const toast = useToast();
    const STORAGE_KEY = "POS_REALTIME_DRAFT_V3";

    // --- 1. STATE & DATA ---

    // State Pencarian Produk (Lokal)
    const searchQuery = ref("");
    const selectedCategory = ref("all");

    // State Database Produk (Ribuan Data)
    const allProducts = ref([]);
    const isFetchingData = ref(false);
    const displayLimit = ref(8); // Lazy render limit

    // State Member
    const memberSearch = ref("");
    const memberSearchResults = ref([]);
    const isLoadingMember = ref(false);
    const selectedMember = ref(null);

    // State scanner
    const activeScannerType = ref(null); // nil, 'product', atau 'member'
    const showScanner = ref(false);
    const scannerType = ref("single"); //single, multi
    const html5QrCode = ref(null);

    // --- 2. FORM TRANSAKSI ---
    const form = useForm({
        input_type: "realtime",
        report_date: new Date().toISOString().slice(0, 10),
        items: [],

        // Pembayaran
        customer_id: null,
        payment_amount: 0,
        change_amount: 0,
        payment_method: "cash",

        // Diskon & Notes
        discount_type: "fixed",
        discount_value: 0,
        notes: "",
    });

    // --- 3. LIFECYCLE & INITIAL LOAD ---

    onMounted(async () => {
        // A. Load Produk Background
        isFetchingData.value = true;
        try {
            const response = await axios.get(route("sales.products.lite"));
            allProducts.value = response.data;
        } catch (e) {
            console.error("Gagal load produk:", e);
            toast.error("Gagal memuat database produk");
        } finally {
            isFetchingData.value = false;
        }

        // B. Restore Local Storage (Draft)
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
    });

    // Auto Save ke LocalStorage
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

    // --- [BARU] LOGIC CAMERA SCANNER ---
    const startScanner = async (type, metode = "single") => {
        activeScannerType.value = type;
        scannerType.value = metode;
        showScanner.value = true;

        await nextTick(); // Tunggu DOM render div id="reader"

        const formatsToSupport = [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.QR_CODE,
        ];
        const qrCode = new Html5Qrcode("reader");
        html5QrCode.value = qrCode;

        qrCode
            .start(
                { facingMode: "environment" }, // Pakai kamera belakang
                {
                    fps: 30,
                    qrbox: { width: 250, height: 150 },
                    formatsToSupport: formatsToSupport,
                },
                (decodedText) => {
                    // SAAT SCAN BERHASIL
                    handleScanSuccess(decodedText);
                },
                (errorMessage) => {
                    // ignore errors while scanning
                }
            )
            .catch((err) => {
                console.log(err);
                activeScannerType.value = null;
                showScanner.value = false;
                alert("Gagal membuka kamera. Pastikan izin diberikan.");
            });
    };

    const stopScanner = () => {
        if (html5QrCode.value) {
            html5QrCode.value
                .stop()
                .then(() => {
                    html5QrCode.value.clear();
                    activeScannerType.value = null;
                    showScanner.value = false;
                })
                .catch((err) => console.log(err));
        } else {
            activeScannerType.value = null;
            showScanner.value = false;
        }
    };

    const handleScanSuccess = (code) => {
        // 1. JIKA SCAN PRODUK
        if (activeScannerType.value === "product") {
            const product = props.products.find((p) => p.code == code);
            if (product) {
                addItem(product);
                if (scannerType.value === "single") {
                    stopScanner();
                }
            } else {
                alert(`Produk ${code} tidak ditemukan!`);
            }
        }
        // 2. JIKA SCAN MEMBER
        else if (activeScannerType.value === "member") {
            // Cari member berdasarkan Member Code ATAU No HP
            const member = props.customers.find(
                (c) => c.member_code == code || c.phone == code
            );

            if (member) {
                selectMember(member); // Pilih member
                stopScanner(); // Langsung tutup kamera kalau member ketemu
                alert(`Member: ${member.name}`);
            } else {
                alert(`Member ${code} tidak terdaftar!`);
            }
        }
    };

    onBeforeUnmount(() => {
        if (html5QrCode.value && html5QrCode.value.isScanning) {
            html5QrCode.value.stop();
        }
    });

    // --- 4. FILTERING LOGIC (OPTIMIZED) ---

    // Filter Produk (Lokal)
    const filteredProducts = computed(() => {
        let result = allProducts.value;

        // A. Filter Kategori
        if (selectedCategory.value !== "all") {
            result = result.filter(
                (p) => p.category_id === selectedCategory.value
            );
        }

        // B. Filter Search
        if (searchQuery.value) {
            const q = searchQuery.value.toLowerCase();
            result = result.filter(
                (p) =>
                    p.name.toLowerCase().includes(q) ||
                    p.code.toLowerCase().includes(q)
            );
        }

        // C. Limit Render (Lazy Load)
        return result.slice(0, displayLimit.value);
    });

    // Infinite Scroll Trigger
    const loadMoreProducts = () => {
        if (displayLimit.value < allProducts.value.length) {
            displayLimit.value += 20;
        }
    };

    // Filter Member (Server Side Search)
    const handleSearchMember = computed(() => {
        if (!memberSearch) {
            memberSearchResults.value = [];
            return;
        }
        if (memberSearch.value) {
            const q = memberSearch.value.toLowerCase();
            result = props.customers.filter(
                (c) =>
                    c.name.toLowerCase().includes(q) ||
                    c.code.toLowerCase().includes(q)
            );
        }
        memberSearchResults.value = result.slice(0, 5);
    });

    // --- 5. CALCULATIONS ---

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
        if (grand === 0) {
            form.payment_amount = 0;
            form.discount_value = 0;
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

    // --- 6. MONEY SUGGESTIONS ---
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

    const handleMoneyClick = (suggestion) => {
        form.payment_amount = suggestion.value;
    };
    const resetPayment = () => {
        form.payment_amount = 0;
    };

    // --- 7. CART ACTIONS & LOGIC ---

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
                stock_max: parseFloat(product.stock),
                image: product.image_path,
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

    // Recalculation Logics
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
                    `⚠️ Ubah harga satuan agar sesuai total Rp ${rp(
                        targetSubtotal
                    )}?`
                )
            ) {
                if (item.quantity > 0)
                    item.selling_price = Math.round(
                        targetSubtotal / item.quantity
                    );
            } else {
                item.subtotal = Math.round(item.quantity * item.selling_price);
            }
        }
    };

    const recalcFromPrice = (item) => {
        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    // Member Selection
    const selectMember = (member) => {
        selectedMember.value = member;
        form.customer_id = member.id;
        memberSearchResults.value = [];
        memberSearch.value = ""; // Clear input
    };

    // --- 8. CHECKOUT ---
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
                localStorage.removeItem(STORAGE_KEY);
                form.reset();
                form.items = [];
                selectedMember.value = null;
                // toast.success("Transaksi Berhasil!");
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

    // --- RETURN ---
    return {
        // State Form & Data
        form,
        allProducts,
        filteredProducts,
        isFetchingData,

        // Scanner
        startScanner,
        stopScanner,
        activeScannerType,
        scannerType,
        showScanner,

        // Search & Filters
        searchQuery,
        selectedCategory,
        loadMoreProducts,

        // Member
        memberSearch,
        memberSearchResults,
        isLoadingMember,
        selectedMember,
        selectMember,
        removeMember: () => {
            selectedMember.value = null;
            form.customer_id = null;
        },

        // Computed Values
        subTotal,
        discountAmount,
        grandTotal,
        changeAmount,
        isPaymentSufficient,
        hasInvalidQty,
        moneySuggestions,

        // Actions
        addItem,
        removeItem,
        updateQty,
        recalcFromQty,
        recalcFromSubtotal,
        recalcFromPrice,
        handleMoneyClick,
        resetPayment,
        submitTransaction,

        // Utils
        rp,
    };
}
