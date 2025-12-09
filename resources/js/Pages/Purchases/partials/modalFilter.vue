<script setup>
import Modal from "@/Components/Modal.vue";
import { useForm, router } from "@inertiajs/vue3";
import { useActionLoading } from "@/Composable/useActionLoading";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    filters: Object,
    // Pastikan backend mengirim 'users' di dalam objek dropdowns
    dropdowns: Object, // { suppliers: [], purchaseStatuses: [], users: [] }
});

const emit = defineEmits(["close"]);

// Setup Form berdasarkan kolom database yang relevan untuk difilter
const form = useForm({
    status: props.filters.status || "",
    supplier_id: props.filters.supplier_id || "",
    user_id: props.filters.user_id || "", // Tambahan: Filter berdasarkan pembuat PO

    // Range Tanggal Transaksi (transaction_date)
    min_date: props.filters.min_date || "",
    max_date: props.filters.max_date || "",

    // Range Grand Total (grand_total)
    min_total: props.filters.min_total || "",
    max_total: props.filters.max_total || "",
});

const { isActionLoading } = useActionLoading();

function clean(obj) {
    return Object.entries(obj).reduce((acc, [key, value]) => {
        if (typeof value === "boolean" && value === false) return acc;
        if (value !== null && value !== undefined && value !== "") {
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
        only: ["purchases", "filters"],
        onFinish: () => {
            isActionLoading.value = false;
            emit("close");
        },
    });
}

function resetFilter() {
    const cleanSearch = clean({ search: props.filters.search || "" });

    isActionLoading.value = true;

    router.get(route("purchases.index"), cleanSearch, {
        preserveState: true,
        replace: true,
        onFinish: () => {
            isActionLoading.value = false;
            emit("close");
            // Reset semua field form ke default
            form.reset(
                "status",
                "supplier_id",
                "user_id",
                "min_date",
                "max_date",
                "min_total",
                "max_total"
            );
        },
    });
}

// Formatter Rupiah untuk preview
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
    <Modal :show="show" @close="$emit('close')" maxWidth="lg">
        <div class="w-full p-6 bg-white shadow-xl rounded-2xl dark:bg-gray-800">
            <div
                class="flex items-center justify-between pb-4 mb-6 border-b border-gray-100 dark:border-gray-700"
            >
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">
                    Filter Data Pembelian
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-500 uppercase dark:text-gray-400"
                            >Status</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                        >
                            <option value="">Semua Status</option>
                            <option
                                v-for="status in dropdowns.purchaseStatuses"
                                :key="status"
                                :value="status"
                            >
                                {{
                                    status.charAt(0).toUpperCase() +
                                    status.slice(1)
                                }}
                            </option>
                        </select>
                    </div>

                    <div v-if="dropdowns.users">
                        <label
                            class="block mb-1.5 text-xs font-bold text-gray-500 uppercase dark:text-gray-400"
                            >Dibuat Oleh</label
                        >
                        <select
                            v-model="form.user_id"
                            class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                        >
                            <option value="">Semua Staff</option>
                            <option
                                v-for="user in dropdowns.users"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label
                        class="block mb-1.5 text-xs font-bold text-gray-500 uppercase dark:text-gray-400"
                        >Supplier</label
                    >
                    <select
                        v-model="form.supplier_id"
                        class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                    >
                        <option value="">Semua Supplier</option>
                        <option
                            v-for="supplier in dropdowns.suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        class="block mb-1.5 text-xs font-bold text-gray-500 uppercase dark:text-gray-400"
                        >Tanggal Transaksi</label
                    >
                    <div class="flex items-center gap-2">
                        <div class="relative w-full">
                            <input
                                type="date"
                                v-model="form.min_date"
                                class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                                placeholder="Dari"
                            />
                        </div>
                        <span class="text-gray-400">-</span>
                        <div class="relative w-full">
                            <input
                                type="date"
                                v-model="form.max_date"
                                class="w-full px-3 py-2 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                                placeholder="Sampai"
                            />
                        </div>
                    </div>
                </div>

                <div>
                    <label
                        class="block mb-1.5 text-xs font-bold text-gray-500 uppercase dark:text-gray-400"
                        >Total Nominal (Grand Total)</label
                    >
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <div class="relative">
                                <span
                                    class="absolute text-xs text-gray-500 left-3 top-2"
                                    >Rp</span
                                >
                                <input
                                    type="number"
                                    v-model.number="form.min_total"
                                    class="w-full py-2 pl-8 pr-3 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                                    placeholder="Min"
                                />
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 text-left">
                                {{ formatRupiah(form.min_total) }}
                            </p>
                        </div>
                        <span class="self-start mt-2 text-gray-400">-</span>
                        <div class="flex-1">
                            <div class="relative">
                                <span
                                    class="absolute text-xs text-gray-500 left-3 top-2"
                                    >Rp</span
                                >
                                <input
                                    type="number"
                                    v-model.number="form.max_total"
                                    class="w-full py-2 pl-8 pr-3 text-sm border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-lime-500 focus:border-lime-500"
                                    placeholder="Max"
                                />
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1 text-left">
                                {{ formatRupiah(form.max_total) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="flex items-center justify-between pt-4 mt-8 border-t border-gray-100 dark:border-gray-700"
            >
                <button
                    type="button"
                    @click="resetFilter"
                    class="text-sm font-medium text-gray-500 transition hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    Reset Filter
                </button>

                <div class="flex gap-2">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600"
                    >
                        Batal
                    </button>
                    <button
                        @click="applyFilter"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white rounded-lg shadow-sm bg-lime-600 hover:bg-lime-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-lime-200 dark:shadow-none"
                    >
                        {{ form.processing ? "Memuat..." : "Terapkan Filter" }}
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>
