<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm, router } from "@inertiajs/vue3";
import { useActionLoading } from "@/Composable/useActionLoading";
import BottomSheetFilter from "@/Components/BottomSheetFilter.vue";

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
    <!-- <Modal :show="show" @close="$emit('close')" maxWidth="lg"> -->
    <BottomSheetFilter :show="show" @close="$emit('close')" title="penjualan">
        <div class="w-full p-4 bg-white shadow rounded-xl dark:bg-gray-800">
            <!-- <h2
                class="mb-4 text-lg font-semibold text-gray-800 dark:text-white"
            >
                Filter Penjualan
            </h2> -->
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
                    <div class="flex-1">
                        <input
                            type="number"
                            placeholder="Min"
                            class="px-1 text-center form-input"
                            v-model.number="form.min_revenue"
                        />
                        <p class="text-[10px] text-gray-400 mt-1 text-left">
                            {{ formatRupiah(form.min_revenue) }}
                        </p>
                    </div>
                    <span class="self-start mt-2 text-gray-400">-</span>
                    <div class="flex-1">
                        <input
                            type="number"
                            placeholder="Max"
                            class="px-1 text-center form-input"
                            v-model.number="form.max_revenue"
                        />
                        <p class="text-[10px] text-gray-400 mt-1 text-left">
                            {{ formatRupiah(form.max_revenue) }}
                        </p>
                    </div>
                </div>
            </div>
            <div
                class="flex flex-col-reverse items-center justify-between gap-4 px-3 py-3 mt-3 border-t border-gray-100 sm:flex-row dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800 sm:gap-0"
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
                <!-- <button
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
                </button> -->
            </div>
        </div>
    </BottomSheetFilter>
    <!-- </Modal -->
</template>
<style scoped>
.form-input {
    @apply w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-lime-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-all;
}
</style>
