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
});
const emit = defineEmits(["close"]);
const form = useForm({
    min_date: props.filters.min_date || "",
    max_date: props.filters.max_date || "",
    min_revenue: props.filters.min_revenue || "",
    max_revenue: props.filters.max_revenue || "",
    min_profit: props.filters.min_profit || "",
    max_profit: props.filters.max_profit || "",
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
    router.get(route("sales.index"), cleanModalFilters, {
        preserveState: true,
        replace: true,
        only: ["sales", "filters"],
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
    router.get(route("sales.index"), cleanSearch, {
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
                Filter Penjualan
            </h2>
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
                    >Total Omset</label
                >
                <div class="flex gap-2">
                    <div class="flex flex-col w-1/2 gap-2">
                        <input
                            type="number"
                            placeholder="Min"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model.number="form.min_revenue"
                        />
                        <span class="text-xs text-gray-500">{{
                            new Intl.NumberFormat("id-ID", {
                                style: "currency",
                                currency: "IDR",
                                minimumFractionDigits: 0,
                            }).format(form.min_revenue) || 0
                        }}</span>
                    </div>
                    <div class="flex flex-col w-1/2 gap-2">
                        <input
                            type="number"
                            placeholder="Max"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-lime-500 focus:ring-lime-500"
                            v-model.number="form.max_revenue"
                        />
                        <span class="text-xs text-gray-500">
                            {{
                                new Intl.NumberFormat("id-ID", {
                                    style: "currency",
                                    currency: "IDR",
                                    minimumFractionDigits: 0,
                                }).format(form.max_revenue) || 0
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
