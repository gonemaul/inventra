<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import DataTable from "@/Components/DataTable.vue";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { throttle } from "lodash";

const props = defineProps({
    filters: Object,
    sales: Object,
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const columns = [
    {
        key: "transaction_date",
        label: "Tanggal",
        sortable: true,
        format: "tanggal",
    },
    {
        key: "reference_no",
        label: "No. Referensi",
        sortable: true,
        slot: "reference",
    },
    {
        key: "summary",
        label: "Summary",
        slot: "Summary",
    },
    {
        key: "total_revenue",
        label: "Total Omset",
        sortable: true,
        format: "rupiah",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.sales.current_page || 1, // Penting untuk pagination
});

const refreshTable = () => {
    router.reload({ only: ["sales", "filters"], preserveScroll: true });
};

const performSearch = throttle(() => {
    const currentFilters = { ...props.filters };
    currentFilters.search = search.value;
    if (!currentFilters.search) {
        delete currentFilters.search;
    }
    router.get(route("sales.index"), currentFilters, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch(
    params,
    throttle((newParams) => {
        const query = Object.fromEntries(
            Object.entries(newParams).filter(([_, v]) => v != null && v !== "")
        );
        isActionLoading.value = true;
        router.get(route("sales.index"), query, {
            preserveState: true, // Jangan scroll ke atas
            preserveScroll: true,
            replace: true, // Jangan penuh-penuhin history browser
            only: ["sales", "filters"], // Optimasi: Cuma update data tabel
            onFinish: () => {
                isActionLoading.value = false;
            },
        });
    }, 300),
    { deep: true }
);

watch(search, performSearch);

const activeFilterCount = computed(() => {
    const query = Object.fromEntries(
        Object.entries(params).filter(([_, v]) => v != null && v !== "")
    );
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});
</script>

<template>
    <Head title="Riwayat Penjualan" />

    <AuthenticatedLayout headerTitle="Riwayat Penjualan">
        <div class="w-full min-h-screen px-4 space-y-6">
            <Filter
                :filters="filters"
                v-model="search"
                @showFilter="showFilterModal = true"
                :filterCount="activeFilterCount"
                :actions="[
                    { route: route('sales.create'), buttonText: 'Rekap' },
                ]"
            />
            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
            />

            <div
                class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
            >
                <!-- Title & Description -->
                <div>
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                    >
                        Riwayat Penjualan
                    </h2>
                    <p
                        class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                    >
                        Kelola semua riwayat penjualan.
                    </p>
                </div>
                <!-- Data Table -->
                <DataTable
                    serverSide="true"
                    :data="sales.data"
                    :columns="columns"
                    :perPageOptions="[5, 10, 25, 50, 100]"
                    v-model:params="params"
                    :pagination="sales"
                >
                    <template #reference="{ row }">
                        <div
                            class="font-mono text-sm font-bold text-indigo-600 dark:text-indigo-400"
                        >
                            {{ row.reference_no }}
                        </div>
                        <div
                            v-if="row.notes"
                            class="text-xs text-gray-500 truncate max-w-[150px]"
                            :title="row.notes"
                        >
                            📝 {{ row.notes }}
                        </div>
                    </template>
                    <template #summary="{ row }">
                        <div
                            v-if="row.financial_summary"
                            class="mx-6 my-4 text-sm text-gray-600 whitespace-nowrap"
                        >
                            <div>
                                Item:
                                <strong>{{
                                    row.financial_summary.item_count
                                }}</strong>
                                Jenis
                            </div>
                            <div>
                                Qty:
                                <strong>{{
                                    row.financial_summary.total_qty
                                }}</strong>
                                Unit
                            </div>
                        </div>
                        <span v-else class="text-xs text-gray-400">-</span>
                    </template>
                    <template #aksi="{ row }">
                        <Link
                            :href="route('sales.show', row.id)"
                            class="px-3 py-1 text-sm font-medium text-indigo-600 transition border border-indigo-200 rounded hover:text-indigo-900 hover:bg-indigo-50"
                        >
                            Lihat Detail
                        </Link>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
