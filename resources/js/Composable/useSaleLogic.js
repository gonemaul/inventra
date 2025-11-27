import { ref, computed, watch, onMounted } from "vue"; // Tambah onMounted
import { useForm, usePage } from "@inertiajs/vue3"; // Tambah usePage untuk Toast manual jika perlu
import axios from "axios";
import debounce from "lodash/debounce";
import { useToast } from "vue-toastification";

export function useSaleLogic() {
    // --- STATE ---
    const toast = useToast();
    const STORAGE_KEY = "POS_RECAP_DRAFT_V1"; // Key unik
    const form = useForm({
        report_date: new Date().toISOString().substr(0, 10),
        notes: "",
        items: [],
    });

    const searchResults = ref([]);
    const isLoadingSearch = ref(false);

    // --- CONFIG ---
    const DECIMAL_UNITS = [
        // Berat
        "kg",
        "kilogram",
        "gram",
        "gr",
        "g",
        "ons",
        "ton",
        "kwintal",
        "mg",

        // Volume
        "liter",
        "ltr",
        "l",
        "ml",
        "cc",
        "m3",
        "kubik",
        "galon",

        // Panjang/Luas
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

    // --- 1. LOCAL STORAGE LOGIC (FIX BUG 1) ---
    onMounted(() => {
        const savedData = localStorage.getItem(STORAGE_KEY);
        if (savedData) {
            try {
                const parsed = JSON.parse(savedData);
                if (parsed.items && parsed.items.length > 0) {
                    // Restore data
                    form.items = parsed.items;
                    form.notes = parsed.notes || "";
                }
            } catch (e) {
                localStorage.removeItem(STORAGE_KEY);
            }
        }
    });

    // Simpan setiap kali item berubah
    watch(
        () => form.items,
        (newItems) => {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    items: newItems,
                    notes: form.notes,
                })
            );
        },
        { deep: true }
    );

    // --- HELPERS ---
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

    // Cek apakah ada item yang quantity-nya 0 atau kurang
    const hasInvalidQty = computed(() => {
        return form.items.some((item) => parseFloat(item.quantity) <= 0);
    });
    const hasStockError = computed(() => {
        return form.items.some(
            (item) => parseFloat(item.quantity) > parseFloat(item.stock_max)
        );
    });
    // --- CORE ACTIONS ---
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
                category: product.category?.name || "-", // Tambahan Detail (Fix Bug 5)
                brand: product.brand || "-", // Tambahan Detail (Fix Bug 5)
                stock_max: parseFloat(product.stock),
                image: product.image,
                selling_price: parseFloat(product.price),
                is_price_locked: true,
                is_total_locked: true,
                quantity: 1,
                subtotal: parseFloat(product.price),
            });
        }
    };

    const removeItem = (index) => form.items.splice(index, 1);

    const calculateSubtotal = (item) => {
        const qty = parseFloat(item.quantity) || 0;
        const price = parseFloat(item.selling_price) || 0;
        item.subtotal = Math.round(qty * price);
    };

    const calculateQty = (item) => {
        const total = parseFloat(item.subtotal) || 0;
        const price = parseFloat(item.selling_price) || 0;
        if (price > 0) {
            let result = total / price;
            item.quantity = isDecimalAllowed(item.unit)
                ? parseFloat(result.toFixed(4))
                : Math.round(result);
        }
    };

    const handleSearch = debounce(async (query) => {
        if (!query) {
            searchResults.value = [];
            return;
        }

        isLoadingSearch.value = true; // Set Loading True (Fix Bug 3)
        try {
            const response = await axios.get(route("sales.search-product"), {
                params: { query },
            });
            searchResults.value = response.data;
            console.log(response);
        } catch (e) {
            console.error(e);
        } finally {
            isLoadingSearch.value = false;
        } // Set Loading False
    }, 300);

    // --- SUBMIT (FIX BUG 2) ---
    const submitForm = () => {
        // Validasi Klien
        if (form.items.length === 0) {
            toast.error("Keranjang masih kosong!"); // Bisa diganti Toast jika punya library
            return;
        }
        if (hasInvalidQty.value) {
            toast.error(
                "Ada produk dengan Qty 0. Mohon isi jumlah barang atau hapus dari list."
            );
            return;
        }
        // Validasi 3: [BARU] Over Stock
        if (hasStockError.value) {
            // Cari nama barangnya biar user tau
            const badItem = form.items.find(
                (item) => parseFloat(item.quantity) > parseFloat(item.stock_max)
            );
            toast.error(
                `Stok tidak cukup untuk produk: ${badItem.name}. (Sisa: ${badItem.stock_max})`
            );
            return;
        }
        form.post(route("sales.store"), {
            onSuccess: () => {
                localStorage.removeItem(STORAGE_KEY); // Bersihkan draft
                form.reset();
                form.items = [];
            },
            onError: (errors) => {
                // Inertia otomatis handle flash error, tapi kita bisa tambah toast.error manual
                console.error(errors);
                toast.error("Gagal menyimpan. Periksa inputan.");
            },
        });
    };

    const totalQty = computed(() =>
        form.items.reduce(
            (sum, item) => sum + (parseFloat(item.quantity) || 0),
            0
        )
    );
    const grandTotal = computed(() =>
        form.items.reduce(
            (sum, item) => sum + (parseFloat(item.subtotal) || 0),
            0
        )
    );

    return {
        form,
        searchResults,
        isLoadingSearch,
        addItem,
        removeItem,
        handleSearch,
        calculateSubtotal,
        calculateQty,
        checkIntegerInput,
        isDecimalAllowed,
        formatCurrency,
        totalQty,
        grandTotal,
        hasInvalidQty,
        hasStockError,
        submitForm, // Return fungsi submit baru
    };
}
