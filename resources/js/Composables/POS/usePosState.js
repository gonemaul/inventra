import { defineStore } from "pinia";
import { ref, computed, watch } from "vue";
import { usePremiumAlert } from "@/Composable/usePremiumAlert";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import debounce from "lodash/debounce";

export const usePosState = defineStore("posState", () => {
    const toast = usePremiumAlert();

    // 1. DRAFTS STATE (MULTI-TAB)
    const drafts = ref([]);
    const activeTabIndex = ref(0);
    const MAX_DRAFTS = 5;

    // Default template for a new Transaction/Tab
    const createEmptyTab = () => {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');

        return {
            id: Date.now(),
            mode: 'retail', // 'retail' | 'bengkel'
            current_cart_step: 1, // Step tracker for modular cart
            cart_items: [], // renamed from form.items -> cart_items
            selected_vehicle: null, // explicit selected_vehicle key
            subtotal: 0, // explicit subtotal key

            // --- EDIT MODE STATE ---
            // Digunakan untuk menandai tab yang sedang mengedit transaksi existing.
            // Jika is_edit_mode=true, mode toggle (Retail/Bengkel) HARUS dikunci.
            is_edit_mode: false,
            edit_transaction_id: null,
            original_items: [], // Snapshot item asli untuk keperluan revert stok di backend

            form: {
                id: null,
                input_type: "realtime",
                type: "retail", // 'retail' | 'bengkel' — Fase 1 kolom baru
                transaction_date: `${year}-${month}-${day}`,
                customer_id: null,
                payment_amount: 0,
                change_amount: 0,
                payment_method: "cash",
                discount_type: "fixed",
                discount_value: 0,
                notes: "",
            },
            filterState: {
                search: "",
                category: "all",
                subCategory: "all",
                brand: "all",
                sort: "default",
                hideEmptyStock: true,
                size: "all"
            },
            selectedMember: null,
            // For Service logic
            serviceData: {
                vehicle: null, // Full vehicle object
                plate_number: "", // input for search
                current_km: null,
                engine_oil_id: null,
                gear_oil_id: null,
                last_service: null, // last service summary
                is_fetching_vehicle: false,
            }
        };
    };

    // ACTIVE TRANSACTION GETTERS
    const activeDraft = computed(() => {
        if (!drafts.value[activeTabIndex.value]) {
            if (drafts.value.length === 0) {
                drafts.value.push(createEmptyTab());
                activeTabIndex.value = 0;
            } else {
                activeTabIndex.value = 0;
            }
        }
        return drafts.value[activeTabIndex.value];
    });

    const form = computed(() => activeDraft.value.form);
    const filterState = computed(() => activeDraft.value.filterState);
    const selectedMember = computed({
        get: () => activeDraft.value.selectedMember,
        set: (val) => activeDraft.value.selectedMember = val,
    });

    // GLOBAL SHARED STATE 
    const allProducts = ref([]);
    const isFetchingData = ref(false);
    const displayLimit = ref(20);
    const hasMore = ref(true);
    const compareList = ref([]);
    const dynamicBrands = ref([]);
    const dynamicSizes = ref([]);
    const productCache = ref(new Map());
    const serviceProducts = ref([]);

    // Member search shared context
    const memberSearch = ref("");
    const memberSearchResults = ref([]);

    // --- MODAL QTY STATE ---
    const showQtyModal = ref(false);
    const currentItem = ref({
        id: null, name: "", code: "", price: 0, quantity: 1, stock: 0, unit: "Pcs", image: null,
    });

    const isSubmitting = ref(false);
    const showDetailModal = ref(false);
    const showCompareModal = ref(false);

    // 2. FINANCIAL CALCULATIONS (Based on active draft)
    const subTotal = computed(() => {
        return activeDraft.value.cart_items.reduce((sum, item) => sum + (parseFloat(item.subtotal) || 0), 0);
    });

    // Sync activeDraft.subtotal with calculated subtotal
    watch(subTotal, (val) => {
        if (activeDraft.value) {
            activeDraft.value.subtotal = val;
        }
    });

    const discountAmount = computed(() => {
        const val = parseFloat(form.value.discount_value) || 0;
        if (val <= 0) return 0;
        if (form.value.discount_type === "percent") {
            const percent = val > 100 ? 100 : val;
            return Math.round((subTotal.value * percent) / 100);
        }
        return val > subTotal.value ? subTotal.value : val;
    });

    const grandTotal = computed(() => {
        const total = subTotal.value - discountAmount.value;
        return total > 0 ? total : 0;
    });

    const changeAmount = computed(() => {
        const pay = parseFloat(form.value.payment_amount) || 0;
        return pay - grandTotal.value;
    });

    const isPaymentSufficient = computed(() => {
        if (form.value.payment_method !== "cash" && form.value.payment_method !== "") return true;
        return (parseFloat(form.value.payment_amount) || 0) >= grandTotal.value;
    });

    const hasInvalidQty = computed(() => {
        return activeDraft.value.cart_items.some((item) => item.quantity <= 0 || isNaN(item.quantity));
    });

    const rp = (val) =>
        new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0,
        }).format(val);

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

    // 3. CART ACTIONS
    const recalcFromQty = (item) => {
        const isDecimal = item.unit?.is_decimal === 1 || item.unit?.is_decimal === true || item.is_decimal;
        if (!isDecimal) {
            item.quantity = Math.round(item.quantity);
            if (item.quantity < 1) item.quantity = 1;
        }
        if (item.quantity > item.stock_max) {
            item.quantity = item.stock_max;
            toast.warning(`Stok sisa ${item.stock_max}`);
        }

        // HUKUM SUBTOTAL: Jika item ini berasal dari input nominal, 
        // CEGAH penghitungan ulang subtotal agar tidak terjadi rounding errors (drift).
        if (item.is_nominal_override) {
            return; 
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
            if (confirm(`⚠️ Ubah harga satuan agar sesuai total ${rp(targetSubtotal)}?`)) {
                if (item.quantity > 0) {
                    item.selling_price = Math.round(targetSubtotal / item.quantity);
                }
            } else {
                item.subtotal = Math.round(item.quantity * item.selling_price);
            }
        }
    };

    const recalcFromPrice = (item) => {
        item.subtotal = Math.round(item.quantity * item.selling_price);
    };

    const openQtyModal = (product, isEdit = false) => {
        console.log(product)
        currentItem.value = {
            ...product,
            price: parseFloat(product.selling_price || product.price || 0),
            quantity: isEdit ? parseFloat(product.quantity) : 1,
            stock: parseFloat(product.stock || product.stock_max || 0),
            is_decimal: product.unit?.is_decimal == 1 || product.unit?.is_decimal === true || product.is_decimal,
            is_nominal_override: product.is_nominal_override || false,
            subtotal: parseFloat(product.subtotal || product.selling_price || product.price || 0),
            _is_edit: isEdit, // Internal flag to distinguish add vs edit
        };
        showQtyModal.value = true;
    };

    const addItem = (product) => {
        const isService = ['Jasa', 'Layanan'].includes(product.category?.name);

        if (!isService && parseFloat(product.stock || product.stock_max) <= 0) {
            toast.error("Stok habis!");
            return;
        }

        const existingItem = activeDraft.value.cart_items.find((i) => i.product_id === (product.id || product.product_id));

        if (existingItem) {
            if (isService) {
                toast.warning(`Jasa "${product.name}" sudah ada di keranjang.`);
                return;
            }

            // Jika ini adalah EDIT dari modal, maka KITA OVERWRITE, bukan ADD (tambah)
            if (product._is_edit) {
                existingItem.quantity = parseFloat(product.quantity);
                existingItem.subtotal = parseFloat(product.subtotal);
                existingItem.is_nominal_override = product.is_nominal_override;
                toast.success("Item diperbarui");
                return;
            }

            if (existingItem.quantity + (product.quantity || 1) > parseFloat(product.stock || product.stock_max)) {
                toast.warning("Stok maksimal!");
                return;
            }
            
            if (product.is_nominal_override) {
                // Tambahkan langsung subtotal dan qty tanpa memecah lock
                existingItem.quantity += parseFloat(product.quantity);
                existingItem.subtotal += parseFloat(product.subtotal);
                existingItem.is_nominal_override = true;
            } else {
                existingItem.quantity += parseFloat(product.quantity || 1);
                recalcFromQty(existingItem);
            }
        } else {
            const price = parseFloat(product.selling_price || product.price);
            activeDraft.value.cart_items.push({
                product_id: product.id || product.product_id,
                code: product.code,
                name: product.name,
                image_url: product.image_url,
                category: product.category,
                unit: product.unit,
                size: product.size,
                stock_max: parseFloat(product.stock || product.stock_max),
                selling_price: price,
                original_price: price,
                quantity: parseFloat(product.quantity || 1),
                original_quantity: 0,
                subtotal: parseFloat(product.subtotal || price),
                is_service: isService,
                is_nominal_override: product.is_nominal_override || false,
                is_decimal: product.is_decimal || false,
            });
        }
    };

    const removeItem = (index) => activeDraft.value.cart_items.splice(index, 1);

    const updateQty = (index, change) => {
        const item = activeDraft.value.cart_items[index];
        const newQty = parseFloat(item.quantity) + change;

        if (newQty > item.stock_max) {
            toast.warning(`Stok sisa ${item.stock_max}`);
            return;
        }
        if (newQty <= 0) {
            if (confirm("Hapus item ini?")) removeItem(index);
            return;
        }
        
        // PENTING: Jika diubah via tombol +/- di keranjang, lock nominal akan lepas
        item.is_nominal_override = false; 
        
        item.quantity = newQty;
        recalcFromQty(item);
    };

    const handleMoneyClick = (suggestion) => {
        form.value.payment_amount = suggestion.value;
    };

    const resetPayment = () => {
        form.value.payment_amount = 0;
    };

    const syncStockWithCatalog = () => {
        if (!allProducts.value.length) return;

        activeDraft.value.cart_items.forEach(item => {
            const product = allProducts.value.find(p => p.id === item.product_id);
            if (product) {
                const myHoldings = item.original_quantity || 0;
                const freshStock = parseFloat(product.stock);
                item.stock_max = freshStock + myHoldings;

                if (item.quantity > item.stock_max) {
                    toast.warning(`Stok ${item.name} berubah! Disesuaikan ke max: ${item.stock_max}`);
                    item.quantity = item.stock_max;
                    recalcFromQty(item);
                }
            }
        });
    };

    const filteredProducts = computed(() => {
        const isRetail = activeDraft.value.mode === 'retail';
        if (isRetail) {
            return allProducts.value.filter(p => !['Jasa', 'Layanan'].includes(p.category?.name));
        }
        return allProducts.value;
    });

    // STEP NAVIGATION
    const maxSteps = computed(() => activeDraft.value.mode === 'bengkel' ? 3 : 2);
    const isLastStep = computed(() => activeDraft.value.current_cart_step === maxSteps.value);

    const nextStep = () => {
        if (activeDraft.value.current_cart_step < maxSteps.value) {
            activeDraft.value.current_cart_step++;
        }
    };

    const prevStep = () => {
        if (activeDraft.value.current_cart_step > 1) {
            activeDraft.value.current_cart_step--;
        }
    };

    const goToStep = (n) => {
        if (n >= 1 && n <= maxSteps.value) {
            activeDraft.value.current_cart_step = n;
        }
    };

    const resetCartStep = () => {
        activeDraft.value.current_cart_step = 1;
    };

    const loadProduct = async (params = {}) => {
        const currentLimit = displayLimit.value;
        const activeSearch = params.search || filterState.value.search;

        const queryParams = {
            query: activeSearch,
            limit: currentLimit,
            category_id: activeSearch ? "all" : filterState.value.category,
            product_type_id: activeSearch ? "all" : filterState.value.subCategory,
            brand_id: activeSearch ? "all" : filterState.value.brand,
            sort: filterState.value.sort,
            hide_empty_stock: filterState.value.hideEmptyStock ? 1 : 0,
            size_id: activeSearch ? "all" : (filterState.value.size || "all"),
        };

        const cacheKey = JSON.stringify(queryParams);

        if (productCache.value.has(cacheKey)) {
            const cachedData = productCache.value.get(cacheKey);
            if (Array.isArray(cachedData)) {
                allProducts.value = cachedData;
            } else {
                allProducts.value = cachedData.products;
                dynamicBrands.value = cachedData.available_brands || [];
                dynamicSizes.value = cachedData.available_sizes || [];

                hasMore.value = cachedData.products.length >= currentLimit;
            }
            syncStockWithCatalog();
        } else {
            isFetchingData.value = true;
        }

        try {
            const response = await axios.get(route("sales.products.lite"), { params: queryParams });
            const data = response.data;
            let products = [];

            if (Array.isArray(data)) {
                products = data;
            } else {
                products = data.products;
                dynamicBrands.value = data.available_brands || [];
                dynamicSizes.value = data.available_sizes || [];
            }

            allProducts.value = products;
            hasMore.value = products.length >= currentLimit;
            productCache.value.set(cacheKey, data);
            syncStockWithCatalog();
        } catch (e) {
            console.error("Gagal load produk:", e);
            toast.error("Gagal memuat database produk");
        } finally {
            isFetchingData.value = false;
        }
    };

    const debouncedSearch = debounce(() => {
        displayLimit.value = 20;
        hasMore.value = true;
        loadProduct();
    }, 400);

    const handleFilterChange = (newVal, oldVal) => {
        if (!newVal || !oldVal) {
            loadProduct();
            return;
        }
        if (newVal.search !== oldVal.search) {
            isFetchingData.value = true;
            debouncedSearch();
        } else {
            if (newVal.category !== oldVal.category ||
                newVal.subCategory !== oldVal.subCategory ||
                newVal.brand !== oldVal.brand ||
                newVal.size !== oldVal.size ||
                newVal.sort !== oldVal.sort ||
                newVal.hideEmptyStock !== oldVal.hideEmptyStock) {
                displayLimit.value = 20;
                hasMore.value = true;
                allProducts.value = [];
            }
            loadProduct();
        }
    };

    // INTERNAL WATCH: Auto-trigger product loading when filter state changes
    watch(
        () => JSON.stringify(activeDraft.value?.filterState),
        (newStr, oldStr) => {
            if (newStr === oldStr) return;
            const newVal = newStr ? JSON.parse(newStr) : null;
            const oldVal = oldStr ? JSON.parse(oldStr) : null;
            handleFilterChange(newVal, oldVal);
        }
    );

    const loadMoreProducts = () => {
        if (!hasMore.value || isFetchingData.value) return;
        displayLimit.value += 12;
        loadProduct();
    };

    const fetchServices = async () => {
        try {
            const response = await axios.get(route("sales.products.lite"), {
                params: { only_services: true, limit: 100 }
            });
            if (response.data.products) {
                serviceProducts.value = response.data.products;
            } else if (Array.isArray(response.data)) {
                serviceProducts.value = response.data;
            } else {
                serviceProducts.value = [];
            }
        } catch (e) {
            console.error("Gagal load layanan:", e);
        }
    };

    const selectMember = (member) => {
        selectedMember.value = member;
        form.value.customer_id = member.id;
        memberSearchResults.value = [];
        memberSearch.value = "";
    };

    const removeMember = () => {
        selectedMember.value = null;
        form.value.customer_id = null;
    };

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

    const queryMember = (customers, code) => {
        const member = customers.find(
            (c) => c.member_code == code || c.phone == code
        );
        if (member) {
            selectMember(member);
        } else {
            toast.warning("Member tidak ditemukan");
        }
    };

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

    // --- TRANSACTION MANAGEMENT (MULTI-TAB) ---
    const addNewTab = () => {
        if (drafts.value.length < MAX_DRAFTS) {
            drafts.value.push(createEmptyTab());
            activeTabIndex.value = drafts.value.length - 1;
        } else {
            toast.warning(`Maksimal ${MAX_DRAFTS} tab transaksi.`);
        }
    };

    const removeTab = (index) => {
        if (drafts.value.length <= 1) {
            drafts.value.splice(0, 1, createEmptyTab());
            activeTabIndex.value = 0;
            return;
        }
        drafts.value.splice(index, 1);
        if (activeTabIndex.value >= drafts.value.length) {
            activeTabIndex.value = drafts.value.length - 1;
        }
    };

    const switchTab = (index) => {
        activeTabIndex.value = index;
    };

    // Guard: Mencegah perubahan mode di tab yang sedang edit.
    // Penting agar tipe transaksi bengkel tidak diubah ke retail saat edit
    // (akan merusak integritas riwayat servis kendaraan).
    const isEditModeLocked = computed(() => activeDraft.value?.is_edit_mode === true);

    const switchPosMode = (mode) => {
        if (isEditModeLocked.value) {
            toast.warning('Mode tidak bisa diubah saat mengedit transaksi.');
            return;
        }
        activeDraft.value.mode = mode;
        activeDraft.value.form.type = mode; // Sync type di form payload
        activeDraft.value.current_cart_step = 1; // Reset step on mode switch
    };

    /**
     * Load transaksi existing ke tab baru untuk diedit.
     *
     * Alur:
     * 1. Buat tab baru (atau gunakan tab kosong saat ini)
     * 2. Inject data transaksi lama: items, customer, payment, notes, discount
     * 3. Set mode (retail/bengkel) berdasarkan data dari DB — TIDAK BISA DIUBAH
     * 4. Simpan original_items untuk keperluan revert stok di backend (Fase 3)
     *
     * @param {Object} transactionData - Sale object dari backend (with items, customer, oilServiceLog)
     */
    const loadTransactionToTab = (transactionData) => {
        // Tentukan apakah tab aktif kosong (bisa di-reuse) atau perlu tab baru
        const currentTabEmpty = activeDraft.value.cart_items.length === 0
            && !activeDraft.value.is_edit_mode;

        if (!currentTabEmpty) {
            // Cek duplikasi: jangan buka edit untuk transaksi yang sudah ada di tab lain
            const existingTabIndex = drafts.value.findIndex(
                tab => tab.is_edit_mode && tab.edit_transaction_id === transactionData.id
            );
            if (existingTabIndex !== -1) {
                activeTabIndex.value = existingTabIndex;
                toast.info('Transaksi ini sudah dibuka di tab lain.');
                return;
            }

            if (drafts.value.length >= MAX_DRAFTS) {
                toast.warning(`Maksimal ${MAX_DRAFTS} tab. Tutup tab lain terlebih dahulu.`);
                return;
            }
            drafts.value.push(createEmptyTab());
            activeTabIndex.value = drafts.value.length - 1;
        }

        const tab = activeDraft.value;
        const sale = transactionData;

        // --- SET EDIT MODE FLAGS ---
        tab.is_edit_mode = true;
        tab.edit_transaction_id = sale.id;

        // --- DETERMINE MODE (Retail/Bengkel) ---
        // Prioritas: kolom `type` dari DB (Fase 1). Fallback: deteksi item Jasa/Layanan.
        const saleType = sale.type || 'retail';
        tab.mode = saleType;

        // --- POPULATE FORM ---
        tab.form.id = sale.id;
        tab.form.input_type = sale.input_type || 'realtime';
        tab.form.type = saleType;
        tab.form.transaction_date = sale.transaction_date;
        tab.form.customer_id = sale.customer_id;
        tab.form.payment_method = sale.payment_method || 'cash';
        tab.form.discount_type = sale.discount_type || 'fixed';
        tab.form.discount_value = parseFloat(sale.discount_value) || 0;
        tab.form.notes = sale.notes || '';

        // --- POPULATE CUSTOMER ---
        if (sale.customer) {
            tab.selectedMember = sale.customer;
        }

        // --- MAP SALE ITEMS KE FORMAT CART ---
        // Backend items → cart_items format yang kompatibel dengan UI
        const mappedItems = (sale.items || []).map(item => {
            const product = item.product || {};
            const snapshot = item.product_snapshot || {};
            const isService = ['Jasa', 'Layanan'].includes(
                product.category?.name || snapshot.category || ''
            );

            return {
                product_id: item.product_id,
                code: product.code || snapshot.code || '-',
                name: product.name || snapshot.name || 'Produk Dihapus',
                image_url: product.image_url || null,
                category: product.category || { name: snapshot.category || '-' },
                unit: product.unit || { name: snapshot.unit || 'Pcs', is_decimal: 0 },
                size: product.size || null,
                // Stok: stok saat ini + qty yang sedang ditahan oleh transaksi ini
                // (karena stok sudah terpotong saat transaksi pertama dibuat)
                stock_max: parseFloat(product.stock || 0) + parseFloat(item.quantity),
                selling_price: parseFloat(item.selling_price),
                original_price: parseFloat(snapshot.original_price || item.selling_price),
                quantity: parseFloat(item.quantity),
                original_quantity: parseFloat(item.quantity), // Untuk revert stok
                subtotal: parseFloat(item.subtotal),
                is_service: isService,
            };
        });

        tab.cart_items = mappedItems;

        // --- SIMPAN SNAPSHOT ORIGINAL ITEMS ---
        // Deep clone agar tidak ter-mutate saat user edit di keranjang
        tab.original_items = JSON.parse(JSON.stringify(mappedItems));

        // --- POPULATE SERVICE DATA (Bengkel Mode) ---
        if (saleType === 'bengkel' && sale.oil_service_log) {
            const log = sale.oil_service_log;
            tab.serviceData.vehicle = log.vehicle || null;
            tab.serviceData.plate_number = log.vehicle?.plate_number || '';
            tab.serviceData.current_km = log.current_km;
            tab.serviceData.engine_oil_id = log.engine_oil_id;
            tab.serviceData.gear_oil_id = log.gear_oil_id;
            tab.selected_vehicle = log.vehicle || null;
        }

        // Reset ke step 1
        tab.current_cart_step = 1;

        toast.success(`Transaksi ${sale.reference_no} dimuat untuk diedit.`);
    };

    const fetchVehicleInfo = async (plateNumber) => {
        if (!plateNumber) return;
        activeDraft.value.serviceData.is_fetching_vehicle = true;
        try {
            const response = await axios.get(route('api.vehicles.info'), {
                params: { plate_number: plateNumber }
            });

            if (response.data.status === 'success') {
                activeDraft.value.serviceData.vehicle = response.data.data.vehicle;
                activeDraft.value.serviceData.last_service = response.data.data.last_service;
                activeDraft.value.selected_vehicle = response.data.data.vehicle;

                // Peringatan jika KM terakhir lebih tinggi (UI Only help)
                if (activeDraft.value.serviceData.last_service?.current_km) {
                    toast.info(`Info: KM Terakhir adalah ${activeDraft.value.serviceData.last_service.current_km.toLocaleString()}`);
                }
            } else if (response.data.status === 'not_found') {
                toast.warning("Kendaraan tidak ditemukan. Silakan daftar di Customer Hub.");
                activeDraft.value.serviceData.vehicle = null;
                activeDraft.value.serviceData.last_service = null;
                activeDraft.value.selected_vehicle = null;
            }
        } catch (error) {
            console.error("Gagal ambil info kendaraan", error);
            toast.error("Gagal mengambil data kendaraan");
        } finally {
            activeDraft.value.serviceData.is_fetching_vehicle = false;
        }
    };

    const debouncedVehicleSearch = debounce((query) => {
        fetchVehicleInfo(query);
    }, 500);

    const submitTransaction = (printInvoice = false) => {
        return new Promise((resolve, reject) => {
            const currentChange = changeAmount.value;
            const currentTotal = grandTotal.value;

            form.value.change_amount = currentChange;
            form.value.payment_amount = parseFloat(form.value.payment_amount);

            const isEditMode = form.value.id != null;
            const path = isEditMode ? route("sales.update", form.value.id) : route("sales.pos.store");
            const method = isEditMode ? 'put' : 'post';

            const payload = {
                ...form.value,
                type: activeDraft.value.mode, // 'retail' | 'bengkel' — Fase 1
                items: activeDraft.value.cart_items,
                print_invoice: printInvoice,
                service_data: activeDraft.value.mode === 'bengkel' ? activeDraft.value.serviceData : null,
                // Kirim original_items jika edit mode (untuk revert stok di Fase 3)
                original_items: activeDraft.value.is_edit_mode ? activeDraft.value.original_items : null,
            };

            router[method](path, payload, {
                onSuccess: (page) => {
                    const flash = page.props.flash || {};
                    const saleId = flash.sale_id;

                    if (!isEditMode) {
                        loadProduct();
                        const idx = activeTabIndex.value;
                        drafts.value.splice(idx, 1, createEmptyTab());
                    }

                    resolve({
                        success: true,
                        saleId: saleId,
                        change: currentChange,
                        total: currentTotal
                    });
                },
                onError: (err) => {
                    console.error(err);
                    toast.error("Gagal memproses transaksi");
                    reject(err);
                }
            });
        });
    };

    return {
        // Multi Draft API
        drafts,
        activeTabIndex,
        activeDraft,
        addNewTab,
        removeTab,
        switchTab,

        // Active Transaction Getters
        form,
        filterState,
        selectedMember,

        // Globals
        allProducts,
        filteredProducts,
        isFetchingData,
        compareList,
        serviceProducts,
        dynamicBrands,
        dynamicSizes,
        memberSearch,
        memberSearchResults,

        // Computed
        currentItem,
        showQtyModal,
        subTotal,
        discountAmount,
        grandTotal,
        changeAmount,
        isPaymentSufficient,
        hasInvalidQty,
        moneySuggestions,
        maxSteps,
        isLastStep,
        showDetailModal,
        showCompareModal,
        isSubmitting,
        isEditModeLocked, // Fase 2: Guard untuk lock mode saat edit

        // Cart / Global actions
        openQtyModal,
        addItem,
        removeItem,
        updateQty,
        resetPayment,
        handleMoneyClick,
        recalcFromQty,
        recalcFromSubtotal,
        recalcFromPrice,
        loadProduct,
        loadMoreProducts,
        fetchServices,
        debouncedSearch,
        handleFilterChange,
        selectMember,
        removeMember,
        queryProduk,
        queryMember,
        toggleCompare,
        clearCompare,
        isInCompare,
        submitTransaction,
        loadTransactionToTab, // Fase 2: Load transaksi existing ke tab POS
        // Mode & Step API
        switchPosMode,
        nextStep,
        prevStep,
        goToStep,
        resetCartStep,
        fetchVehicleInfo,
        debouncedVehicleSearch,
        rp
    };
});
