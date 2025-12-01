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
const form = useForm({
    status: props.filters.status || "",
    supplier_id: props.filters.supplier_id || "",

    // Range filter (nama field harus cocok dengan backend)
    min_date: props.filters.min_date || "",
    max_date: props.filters.max_date || "",
    min_total: props.filters.min_total || "",
    max_total: props.filters.max_total || "",
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

function applyFilter() {
    const modalFilters = form.data();
    const searchFilter = { search: props.filters.search || "" };
    const allFilters = { ...searchFilter, ...modalFilters };
    const cleanModalFilters = clean(allFilters);
    isActionLoading.value = true;
    router.get(route("purchases.index"), cleanModalFilters, {
        preserveState: true,
        replace: true,
        only: ["purchases", "filters "],
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
    router.get(route("purchases.index"), cleanSearch, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false; // Matikan loader
            emit("close"); // Tutup modal
            form.reset(
                "status",
                "supplier_id",
                "max_date",
                "min_date",
                "min_total",
                "max_total"
            );
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
                Filter Pembelian
            </h2>
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
                        v-for="status in dropdowns.purchaseStatuses"
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
                    >Tanggal</label
                >
                <div class="flex gap-2">
                    <input
                        type="date"
                        placeholder="Min"
                        class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.min_date"
                    />
                    <input
                        type="date"
                        placeholder="Max"
                        class="w-1/2 px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                        v-model="form.max_date"
                    />
                </div>
            </div>
            <div class="mb-4">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Total</label
                >
                <div class="flex gap-2">
                    <div class="flex flex-col w-1/2 gap-2">
                        <input
                            type="number"
                            placeholder="Min"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model.number="form.min_total"
                        />
                        <span class="text-xs text-gray-500">{{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.min_total) || 0
                        }}</span>
                    </div>
                    <div class="flex flex-col w-1/2 gap-2">
                        <input
                            type="number"
                            placeholder="Max"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model.number="form.max_total"
                        />
                        <span class="text-xs text-gray-500">
                            {{
                                new Intl.NumberFormat("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                    minimumFractionDigits: 0,
                                }).format(form.max_total) || 0
                            }}</span
                        >
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button
                    class="px-4 py-2 text-sm font-medium text-white rounded-lg bg-lime-500 hover:bg-lime-600 disabled:opacity-50"
                    @click="applyFilter"
                    :disabled="form.processing"
                >
                    {{ form.processing ? "Menerapkan..." : "Terapkan" }}</button
                ><button
                    class="px-4 py-2 text-sm font-medium bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200"
                    @click="resetFilter"
                    type="button"
                >
                    Reset
                </button>
            </div>
        </div></Modal
    >
</template>
