<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm, router } from "@inertiajs/vue3";
import { useActionLoading } from "@/Composable/useActionLoading";
import BottomSheetFilter from "@/Components/BottomSheetFilter.vue";

const props = defineProps({
    show: { type: Boolean, default: false },
    filters: Object, // State filter dari URL
    dropdowns: Object, // Data master dari Controller (categories, brands, suppliers, types, units, sizes)
});

const emit = defineEmits(["close"]);

// --- OPSI SORTING ---
const sortOptions = [
    { value: "created_at", label: "Tanggal Dibuat" },
    { value: "name", label: "Nama Produk" },
    { value: "stock", label: "Jumlah Stok" },
    { value: "selling_price", label: "Harga Jual" },
    { value: "purchase_price", label: "Harga Beli" },
];

// --- INIT FORM ---
const form = useForm({
    // 1. Relasi (Foreign Keys)
    category_id: props.filters.category_id || "",
    brand_id: props.filters.brand_id || "",
    supplier_id: props.filters.supplier_id || "",
    product_type_id: props.filters.product_type_id || "",
    unit_id: props.filters.unit_id || "",
    size_id: props.filters.size_id || "",

    // 2. Range (Min-Max)
    // Stok
    min_stock: props.filters.stock_min || "",
    max_stock: props.filters.stock_max || "",
    // Harga Jual
    min_price: props.filters.price_min || "",
    max_price: props.filters.price_max || "",
    // Harga Beli (Cost)
    min_cost: props.filters.cost_min || "",
    max_cost: props.filters.cost_max || "",

    // 3. Status & System
    status: props.filters.status || "",
    trashed: props.filters.trashed || "", // '', 'with', 'only'

    // 4. Sorting
    sort: props.filters.sort || "created_at",
    order: props.filters.order || "desc",
});

const { isActionLoading } = useActionLoading();

// Helper: Bersihkan nilai kosong sebelum dikirim ke URL
function clean(obj) {
    return Object.entries(obj).reduce((acc, [key, value]) => {
        if (value !== null && value !== undefined && value !== "") {
            acc[key] = value;
        }
        return acc;
    }, {});
}

// Action: Terapkan Filter
function applyFilter() {
    // Pertahankan search query jika ada
    const currentSearch = props.filters.search
        ? { search: props.filters.search }
        : {};

    // Mapping form ke nama parameter yang diharapkan Service
    // Perhatikan mapping nama field form -> param url
    const payload = {
        ...currentSearch,
        category_id: form.category_id,
        brand_id: form.brand_id,
        supplier_id: form.supplier_id,
        product_type_id: form.product_type_id,
        unit_id: form.unit_id,
        size_id: form.size_id,
        status: form.status,
        trashed: form.trashed,
        sort: form.sort,
        order: form.order,

        // Mapping Range (Form name -> URL param name)
        stock_min: form.min_stock,
        stock_max: form.max_stock,
        price_min: form.min_price,
        price_max: form.max_price,
        cost_min: form.min_cost,
        cost_max: form.max_cost,
    };

    const finalFilters = clean(payload);

    isActionLoading.value = true;
    router.get(route("products.index"), finalFilters, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false;
            emit("close");
        },
    });
}

// Action: Reset Filter
function resetFilter() {
    const currentSearch = props.filters.search
        ? { search: props.filters.search }
        : {};

    isActionLoading.value = true;
    router.get(route("products.index"), currentSearch, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false;
            form.reset();
            form.defaults();
            emit("close");
        },
    });
}
const formatRupiah = (value) => {
    if (!value) return "0";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
};
</script>

<template>
    <BottomSheetFilter :show="show" title="produk" @close="$emit('close')">
        <!-- <div
            class="flex flex-col overflow-hidden shadow-xl dark:bg-gray-800 rounded-xl"
        > -->
        <!-- <div
                class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
            >
                <div>
                    <h2
                        class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white"
                    >
                        <svg
                            class="w-5 h-5 text-lime-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                            />
                        </svg>
                        Filter Lanjutan
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Saring data produk secara mendetail.
                    </p>
                </div>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-red-500 transition p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div> -->

        <div class="flex-1 space-y-8 o custom-scrollbar">
            <section>
                <h3
                    class="pb-1 mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700"
                >
                    Klasifikasi Produk
                </h3>
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-x-6 gap-y-4"
                >
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Kategori</label
                        >
                        <select v-model="form.category_id" class="form-select">
                            <option value="">Semua Kategori</option>
                            <option
                                v-for="opt in dropdowns.categories"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Tipe Produk</label
                        >
                        <select
                            v-model="form.product_type_id"
                            class="form-select"
                        >
                            <option value="">Semua Tipe</option>
                            <option
                                v-for="opt in dropdowns.types"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Merk / Brand</label
                        >
                        <select v-model="form.brand_id" class="form-select">
                            <option value="">Semua Merk</option>
                            <option
                                v-for="opt in dropdowns.brands"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Supplier</label
                        >
                        <select v-model="form.supplier_id" class="form-select">
                            <option value="">Semua Supplier</option>
                            <option
                                v-for="opt in dropdowns.suppliers"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h3
                    class="pb-1 mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700"
                >
                    Spesifikasi Fisik
                </h3>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Satuan (Unit)</label
                        >
                        <select v-model="form.unit_id" class="form-select">
                            <option value="">Semua Satuan</option>
                            <option
                                v-for="opt in dropdowns.units"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Ukuran (Size)</label
                        >
                        <select v-model="form.size_id" class="form-select">
                            <option value="">Semua Ukuran</option>
                            <option
                                v-for="opt in dropdowns.sizes"
                                :key="opt.id"
                                :value="opt.id"
                            >
                                {{ opt.name }}
                            </option>
                        </select>
                    </div>
                </div>
            </section>

            <section>
                <h3
                    class="pb-1 mb-3 text-xs font-bold tracking-wider text-gray-400 uppercase border-b border-gray-100 dark:border-gray-700"
                >
                    Rentang Nilai
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Jumlah Stok</label
                        >
                        <div class="flex gap-2">
                            <input
                                type="number"
                                v-model="form.min_stock"
                                placeholder="Min"
                                class="px-1 text-center form-input"
                            />
                            <span class="self-center text-gray-400">-</span>
                            <input
                                type="number"
                                v-model="form.max_stock"
                                placeholder="Max"
                                class="px-1 text-center form-input"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Harga Jual</label
                        >
                        <div class="flex gap-2">
                            <div class="">
                                <input
                                    type="number"
                                    v-model="form.min_price"
                                    placeholder="Min"
                                    class="px-1 text-center form-input"
                                />
                                <p
                                    class="text-[10px] text-gray-400 mt-1 text-left"
                                >
                                    {{ formatRupiah(form.min_price) }}
                                </p>
                            </div>
                            <span class="self-start mt-2 text-gray-400">-</span>
                            <div>
                                <input
                                    type="number"
                                    v-model="form.max_price"
                                    placeholder="Max"
                                    class="px-1 text-center form-input"
                                />
                                <p
                                    class="text-[10px] text-gray-400 mt-1 text-left"
                                >
                                    {{ formatRupiah(form.max_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Harga Beli</label
                        >
                        <div class="flex gap-2">
                            <div class="">
                                <input
                                    type="number"
                                    v-model="form.min_cost"
                                    placeholder="Min"
                                    class="px-1 text-center form-input"
                                />
                                <p
                                    class="text-[10px] text-gray-400 mt-1 text-left"
                                >
                                    {{ formatRupiah(form.min_cost) }}
                                </p>
                            </div>
                            <span class="self-start mt-2 text-gray-400">-</span>
                            <div class="">
                                <input
                                    type="number"
                                    v-model="form.max_cost"
                                    placeholder="Max"
                                    class="px-1 text-center form-input"
                                />
                                <p
                                    class="text-[10px] text-gray-400 mt-1 text-left"
                                >
                                    {{ formatRupiah(form.max_cost) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                class="p-4 border border-gray-100 bg-gray-50 dark:bg-gray-700/30 rounded-xl dark:border-gray-600"
            >
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Status Produk</label
                        >
                        <select v-model="form.status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="active">Aktif (Tampil)</option>
                            <option value="inactive">
                                Non-Aktif (Disembunyikan)
                            </option>
                        </select>
                    </div>

                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Data Terhapus</label
                        >
                        <select
                            v-model="form.trashed"
                            class="font-medium text-red-600 form-select"
                        >
                            <option value="">Sembunyikan Sampah</option>
                            <option value="with">Tampilkan Arsip Sampah</option>
                            <option value="only">
                                Hanya Sampah (Recovery)
                            </option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-700 dark:text-gray-300"
                            >Urutkan Data</label
                        >
                        <div class="flex gap-2">
                            <select
                                v-model="form.sort"
                                class="flex-1 form-select"
                            >
                                <option
                                    v-for="opt in sortOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </option>
                            </select>
                            <div
                                class="flex overflow-hidden bg-white border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600"
                            >
                                <button
                                    type="button"
                                    @click="form.order = 'asc'"
                                    class="px-3 py-2 text-xs font-bold transition hover:bg-lime-50 dark:hover:bg-gray-600"
                                    :class="
                                        form.order === 'asc'
                                            ? 'bg-lime-500 text-white hover:bg-lime-600'
                                            : 'text-gray-600 dark:text-gray-300'
                                    "
                                >
                                    A-Z
                                </button>
                                <div
                                    class="w-px bg-gray-300 dark:bg-gray-600"
                                ></div>
                                <button
                                    type="button"
                                    @click="form.order = 'desc'"
                                    class="px-3 py-2 text-xs font-bold transition hover:bg-lime-50 dark:hover:bg-gray-600"
                                    :class="
                                        form.order === 'desc'
                                            ? 'bg-lime-500 text-white hover:bg-lime-600'
                                            : 'text-gray-600 dark:text-gray-300'
                                    "
                                >
                                    Z-A
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div
            class="flex flex-col-reverse items-center justify-between gap-4 px-5 py-3 mt-3 border-t border-gray-100 rounded-xl sm:flex-row dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 sm:gap-0"
        >
            <button
                @click="resetFilter"
                class="text-xs font-bold text-gray-500 transition border-b border-gray-400 border-dashed dark:text-gray-400 hover:text-red-500 hover:border-red-500"
            >
                Reset ke Default
            </button>
            <div class="flex w-full gap-3 sm:w-auto">
                <button
                    @click="$emit('close')"
                    class="flex-1 sm:flex-none px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-bold text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    Batal
                </button>
                <button
                    @click="applyFilter"
                    class="flex-1 sm:flex-none px-8 py-2.5 rounded-xl bg-lime-500 hover:bg-lime-600 text-white font-bold text-sm shadow-md shadow-lime-200 dark:shadow-none transition flex items-center justify-center gap-2"
                    :disabled="isActionLoading"
                >
                    <svg
                        v-if="isActionLoading"
                        class="w-4 h-4 text-white animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </div>
        <!-- </div> -->
    </BottomSheetFilter>
</template>

<style scoped>
/* REUSABLE INPUT CLASS */
.form-select,
.form-input {
    @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all;
}

/* CUSTOM SCROLLBAR */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
