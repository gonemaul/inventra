import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";
import { useToast } from "vue-toastification";

export function usePosRealtime(props) {
    const toast = useToast();
    const STORAGE_KEY = "POS_REALTIME_DRAFT_V3"; // Naikkan versi key biar fresh

    // --- STATE UI ---
    const searchResults = ref([]);
    const memberSearchResults = ref([]);
    const isLoadingSearch = ref(false);
    const isLoadingMember = ref(false);
    const selectedMember = ref(null);

    // --- FORM UTAMA ---
    const form = useForm({
        input_type: "realtime",
        report_date: new Date().toISOString().slice(0, 10),
        items: [],

        // Data Transaksi
        customer_id: null,
        payment_amount: 0,
        change_amount: 0,
        payment_method: "cash",

        // Fitur Baru
        discount_type: "fixed", // 'fixed' or 'percent'
        discount_value: 0, // Nominal atau Angka Persen
        notes: "",
    });

    // --- 1. LOCAL STORAGE LOGIC ---
    onMounted(() => {
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

    // Auto Save
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

    // --- 2. CALCULATIONS (CORE) ---

    // Total Harga Barang (Sebelum Diskon Global)
    const subTotal = computed(() => {
        return form.items.reduce(
            (sum, item) => sum + (parseFloat(item.subtotal) || 0),
            0
        );
    });

    // Hitung Diskon Global
    const discountAmount = computed(() => {
        const val = parseFloat(form.discount_value) || 0;
        if (val <= 0) return 0;

        if (form.discount_type === "percent") {
            // Max 100%
            const percent = val > 100 ? 100 : val;
            return Math.round((subTotal.value * percent) / 100);
        } else {
            // Fixed Rupiah
            return val > subTotal.value ? subTotal.value : val;
        }
    });

    // Grand Total (Harus Dibayar)
    const grandTotal = computed(() => {
        const total = subTotal.value - discountAmount.value;
        return total > 0 ? total : 0;
    });

    // Kembalian
    const changeAmount = computed(() => {
        const pay = parseFloat(form.payment_amount) || 0;
        return pay - grandTotal.value;
    });

    // Validasi Pembayaran
    const isPaymentSufficient = computed(() => {
        // Jika metode bukan cash (transfer/qris), biasanya dianggap pas
        if (form.payment_method !== "cash") return true;
        return (parseFloat(form.payment_amount) || 0) >= grandTotal.value;
    });

    // Validasi Item Invalid (Untuk disable tombol checkout)
    const hasInvalidQty = computed(() => {
        return form.items.some(
            (item) => item.quantity <= 0 || isNaN(item.quantity)
        );
    });

    // --- 3. MONEY SUGGESTIONS (SARAN UANG) ---
    const moneySuggestions = computed(() => {
        const total = grandTotal.value;
        if (total <= 0) return [];

        const suggestions = [
            {
                label: "Uang Pas",
                value: total,
                class: "bg-lime-50 text-lime-600 border-lime-200",
            },
        ];

        // Logic Pecahan Indonesia
        const fractions = [2000, 5000, 10000, 20000, 50000, 100000];

        // Cari pecahan terdekat di atas total
        fractions.forEach((frac) => {
            if (frac > total) {
                // Jangan duplikat jika sudah ada
                if (!suggestions.find((s) => s.value === frac)) {
                    suggestions.push({ label: rp(frac), value: frac });
                }
            }
        });

        // Tambahkan kelipatan 50rb/100rb terdekat jika nominal besar
        if (total > 50000) {
            const next50 = Math.ceil(total / 50000) * 50000;
            if (!suggestions.find((s) => s.value === next50)) {
                suggestions.push({ label: rp(next50), value: next50 });
            }
        }

        // Batasi max 4 saran biar layout rapi
        return suggestions.slice(0, 4);
    });

    const handleMoneyClick = (suggestion) => {
        form.payment_amount = suggestion.value;
    };

    const resetPayment = () => {
        form.payment_amount = 0;
    };

    // --- 4. ITEM LOGIC & UPDATE QTY ---

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

    // FUNGSI UPDATE QTY (Dipakai tombol +/-)
    const updateQty = (index, change) => {
        const item = form.items[index];
        const newQty = parseFloat(item.quantity) + change;

        // Validasi
        if (newQty > item.stock_max) {
            toast.warning(`Stok sisa ${item.stock_max}`);
            return;
        }
        if (newQty <= 0) {
            // Opsional: Hapus item atau set ke 1
            if (confirm("Hapus item ini?")) {
                removeItem(index);
            }
            return;
        }

        item.quantity = newQty;
        recalcFromQty(item); // Hitung ulang subtotal
    };

    // --- RECALCULATION LOGIC (Yang sudah kita bahas) ---

    // A. User Ubah Qty -> Hitung Subtotal
    const recalcFromQty = (item) => {
        const isDecimal = item.unit?.is_decimal === 1;
        if (!isDecimal) {
            item.quantity = Math.round(item.quantity);
            if (item.quantity < 1) item.quantity = 1;
        }

        if (item.quantity > item.stock_max) {
            toast.warning(`Stok sisa ${item.stock_max}`);
            item.quantity = item.stock_max;
        }

        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    // B. User Ubah Subtotal (Beli Nominal) -> Hitung Qty atau Harga
    const recalcFromSubtotal = (item) => {
        const targetSubtotal = parseFloat(item.subtotal) || 0;
        const currentPrice = parseFloat(item.selling_price) || 0;
        if (currentPrice <= 0) return;

        const isDecimal = item.unit?.is_decimal === 1;

        if (isDecimal) {
            // Kasus Desimal: Ubah Qty
            let newQty = targetSubtotal / currentPrice;
            if (newQty > item.stock_max) {
                toast.warning("Melebihi sisa stok!");
                newQty = item.stock_max;
                item.subtotal = Math.round(newQty * currentPrice);
            }
            item.quantity = parseFloat(newQty.toFixed(4));
        } else {
            // Kasus Integer: Ubah Harga Satuan (Dangerous)
            if (
                confirm(
                    `⚠️ Ubah harga satuan agar sesuai total Rp ${rp(
                        targetSubtotal
                    )}?`
                )
            ) {
                if (item.quantity > 0) {
                    const newPrice = targetSubtotal / item.quantity;
                    item.selling_price = Math.round(newPrice);
                }
            } else {
                // Cancel: Reset subtotal
                item.subtotal = Math.round(item.quantity * item.selling_price);
            }
        }
    };

    // C. User Ubah Harga Satuan -> Hitung Subtotal
    const recalcFromPrice = (item) => {
        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    // --- 5. SEARCH ACTIONS ---

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

    const handleSearchMember = debounce(async (query) => {
        if (!query) {
            memberSearchResults.value = [];
            return;
        }
        isLoadingMember.value = true;
        try {
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
        memberSearchResults.value = [];
    };

    // --- 6. SUBMIT & CHECKOUT ---

    // Wrapper untuk tombol "Proses Sekarang"
    const handleCheckoutClick = () => {
        // 1. Cek Keranjang
        if (form.items.length === 0)
            return toast.error("Keranjang masih kosong!");

        // 2. Cek Qty Invalid
        if (hasInvalidQty.value)
            return toast.error("Ada item dengan jumlah 0 atau minus!");

        // 3. Cek Pembayaran (Khusus metode Cash)
        if (form.payment_method === "cash" && !isPaymentSufficient.value) {
            return toast.error("Uang pembayaran kurang!");
        }

        // Lanjut Submit
        submitTransaction();
    };

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
                toast.success("Transaksi Berhasil!");
                if (callbacks.onSuccess) callbacks.onSuccess(page);
            },
            onError: (err) => {
                console.error(err);
                toast.error("Gagal memproses transaksi");
            },
        });
    };

    // Helper Formatter
    const rp = (val) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(val);

    return {
        // State
        form,
        searchResults,
        isLoadingSearch,
        memberSearchResults,
        isLoadingMember,
        selectedMember,

        // Computed
        subTotal,
        discountAmount,
        grandTotal,
        changeAmount,
        isPaymentSufficient,
        hasInvalidQty,
        moneySuggestions,

        // Core Actions
        addItem,
        removeItem,
        updateQty,

        // Calculation Actions
        recalcFromQty,
        recalcFromSubtotal,
        recalcFromPrice,

        // Payment Actions
        handleMoneyClick,
        resetPayment,
        handleCheckoutClick,

        // Search Actions
        handleSearch,
        handleSearchMember,
        selectMember,
        removeMember: () => {
            selectedMember.value = null;
            form.customer_id = null;
        },

        submitTransaction,
        rp,
    };
}
