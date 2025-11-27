<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm, router } from "@inertiajs/vue3";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    filters: Object, // Filter yang sedang aktif
    dropdowns: Object, // Data { categories: [], sizes: [], productStatuses: [] }
});
const emit = defineEmits(["close"]);

const sortOptions = [
    { value: "created_at", label: "Terbaru" },
    { value: "name", label: "Nama" },
    { value: "selling_price", label: "Harga" },
    { value: "stock", label: "Stok" },
];
const form = useForm({
    sort: props.filters.sort || "created_at",
    order: props.filters.order || "desc",
    status: props.filters.status || "",
    category_id: props.filters.category_id || "",
    supplier_id: props.filters.supplier_id || "",

    // Untuk filter ukuran (multi-select), kita gunakan array
    sizes: props.filters.sizes || [],

    // Range filter (nama field harus cocok dengan backend)
    min_stock: props.filters.min_stock || "",
    max_stock: props.filters.max_stock || "",
    min_price: props.filters.min_price || "",
    max_price: props.filters.max_price || "",
    trashed: props.filters.trashed || false,
});

const { isActionLoading } = useActionLoading();

function clean(obj) {
    return Object.entries(obj).reduce((acc, [key, value]) => {
        // Cek umum: bukan null, undefined, atau string kosong
        if (typeof value === "boolean" && value === false) {
            return acc;
        }
        if (value !== null && value !== undefined && value !== "") {
            // Cek khusus: bukan array kosong (untuk 'sizes')
            if (!(Array.isArray(value) && value.length === 0)) {
                acc[key] = value;
            }
        }
        return acc;
    }, {});
}
// 4. Helper untuk tombol Ukuran (multi-select)
function toggleSize(sizeId) {
    const index = form.sizes.indexOf(sizeId);
    if (index > -1) {
        form.sizes.splice(index, 1); // Hapus jika sudah ada
    } else {
        form.sizes.push(sizeId); // Tambah jika belum ada
    }
}
function isSizeActive(sizeId) {
    return form.sizes.includes(sizeId);
}

// 5. Aksi Tombol "Terapkan"
function applyFilter() {
    const modalFilters = form.data();
    const searchFilter = { search: props.filters.search || "" };
    const allFilters = { ...searchFilter, ...modalFilters };
    const cleanModalFilters = clean(allFilters);
    isActionLoading.value = true;
    router.get(route("products.index"), cleanModalFilters, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false; // Matikan loader
            emit("close"); // Tutup modal
        },
    });
}

// 6. Aksi Tombol "Reset"
function resetFilter() {
    const cleanSearch = clean({ search: props.filters.search || "" });
    isActionLoading.value = true;
    router.get(route("products.index"), cleanSearch, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false; // Matikan loader
            emit("close"); // Tutup modal
            form.reset();
        },
    });
}
</script>
<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="lg"
        ><div class="w-full p-4 bg-white shadow rounded-xl dark:bg-gray-800">
            <h2
                class="mb-4 text-lg font-semibold text-gray-800 dark:text-white"
            >
                Filter Produk
            </h2>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Sortir</label
                >
                <div class="flex gap-2">
                    <select
                        v-model="form.sort"
                        class="w-1/2 px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                    >
                        <option
                            v-for="opt in sortOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option></select
                    ><select
                        v-model="form.order"
                        class="w-1/2 px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                    >
                        <option value="desc">Terbaru / Desc (Z-A)</option>
                        <option value="asc">Terlama / Asc (A-Z)</option>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Status</label
                ><select
                    v-model="form.status"
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option value="">Semua Status</option>
                    <option
                        v-for="status in dropdowns.productStatuses"
                        :key="status"
                        :value="status"
                    >
                        {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                    </option>
                </select>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Kategori</label
                ><select
                    v-model="form.category_id"
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option value="">Semua Kategori</option>
                    <option
                        v-for="cat in dropdowns.categories"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Supplier</label
                ><select
                    v-model="form.supplier_id"
                    class="w-full px-3 py-2 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                >
                    <option value="">Semua Supplier</option>
                    <option
                        v-for="cat in dropdowns.suppliers"
                        :key="cat.id"
                        :value="cat.id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Ukuran</label
                >
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="size in dropdowns.sizes"
                        :key="size.id"
                        class="px-3 py-1 text-sm text-gray-800 border border-gray-300 rounded-lg dark:border-gray-600 dark:text-gray-300"
                        :class="{
                            'bg-lime-500 text-white border-lime-500':
                                isSizeActive(size.id),
                            'hover:bg-lime-100 dark:hover:bg-gray-600':
                                !isSizeActive(size.id),
                        }"
                        @click="toggleSize(size.id)"
                    >
                        {{ size.name }}
                    </button>
                </div>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Stok</label
                >
                <div class="flex gap-2">
                    <input
                        type="number"
                        placeholder="Min"
                        class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.min_stock"
                    />
                    <input
                        type="number"
                        placeholder="Max"
                        class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.max_stock"
                    />
                </div>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Harga (Jual)</label
                >
                <div class="flex gap-2">
                    <div class="flex flex-col w-full gap-2">
                        <input
                            type="number"
                            placeholder="Min"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model="form.min_price"
                        />
                        <span class="text-xs text-gray-500">{{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.min_price) || 0
                        }}</span>
                    </div>
                    <div class="flex flex-col w-full gap-2">
                        <input
                            type="number"
                            placeholder="Max"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model="form.max_price"
                        />
                        <span class="text-xs text-gray-500">{{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.max_price) || 0
                        }}</span>
                    </div>
                </div>
            </div>
            <div class="flex justify-between gap-2">
                <div class="my-auto">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="form.trashed"
                            class="border-gray-300 rounded dark:bg-gray-900 dark:border-gray-700 text-lime-600 focus:ring-lime-500"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-400">
                            Hanya Tampilkan Sampah
                        </span>
                    </label>
                </div>
                <div class="flex gap-3">
                    <button
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600 disabled:opacity-50"
                        @click="applyFilter"
                        :disabled="form.processing"
                    >
                        {{
                            form.processing ? "Menerapkan..." : "Terapkan"
                        }}</button
                    ><button
                        class="px-4 py-2 text-sm font-medium bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200"
                        @click="resetFilter"
                        type="button"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div></Modal
    >
</template>
