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
    dropdowns: Object, // Paginator object
    filters: Object,
    purchases: Object,
});

const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const showTrashed = ref(false);
const columns = [
    {
        key: "transaction_date",
        label: "Tanggal Order",
        sortable: true,
        format: "tanggal",
    },
    {
        key: "reference_no",
        label: "No. Referensi",
        sortable: true,
    },
    {
        key: "supplier",
        label: "Supplier",
        slot: "supplier",
    },
    {
        key: "grand_total",
        label: "Total Belanja",
        sortable: true,
        format: "rupiah",
    },
    {
        key: "status",
        label: "Status",
        sortable: true,
        slot: "status",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.purchases.current_page || 1, // Penting untuk pagination
});

const refreshTable = () => {
    // Memanggil fungsi fetchData di DataTable (jika mode Server Side Mandiri)
    // Jika mode Semi Side (Inertia), ini memicu reload prop Inertia
    router.reload({ only: ["purchases", "filters"], preserveScroll: true });
};

const performSearch = throttle(() => {
    const currentFilters = { ...props.filters };
    currentFilters.search = search.value;
    if (!currentFilters.search) {
        delete currentFilters.search;
    }
    router.get(route("purchases.index"), currentFilters, {
        preserveState: true,
        replace: true,
    });
}, 300);

// WATHCH
watch(showTrashed, (newValue) => {
    if (newValue === true) {
        params.value.trashed = true;
    } else {
        delete params.value.trashed;
    }
});
watch(
    params,
    throttle((newParams) => {
        const query = Object.fromEntries(
            Object.entries(newParams).filter(([_, v]) => v != null && v !== ""),
        );
        isActionLoading.value = true;
        router.get(route("purchases.index"), query, {
            preserveState: true, // Jangan scroll ke atas
            preserveScroll: true,
            replace: true, // Jangan penuh-penuhin history browser
            only: ["purchases", "filters"], // Optimasi: Cuma update data tabel
            onFinish: () => {
                isActionLoading.value = false;
            },
        });
    }, 300),
    { deep: true },
);

watch(search, performSearch);

const activeFilterCount = computed(() => {
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Pembelian">
        <div class="w-full min-h-screen space-y-6">
            <Filter
                :filters="filters"
                v-model="search"
                @showFilter="showFilterModal = true"
                :filterCount="activeFilterCount"
                :actions="[
                    { route: route('purchases.create'), buttonText: 'RAB' },
                ]"
            />
            <FilterModal
                :show="showFilterModal"
                @close="showFilterModal = false"
                :filters="filters"
                :dropdowns="dropdowns"
            />

            <div
                class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
            >
                <!-- Title & Description -->
                <div>
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                    >
                        Daftar Pembelian
                    </h2>
                    <p
                        class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                    >
                        Kelola semua pembelian.
                    </p>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input
                            type="checkbox"
                            v-model="showTrashed"
                            class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 ..."
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            Tampilkan Sampah
                        </span>
                    </label>
                </div>
                <!-- Data Table -->
                <DataTable
                    serverSide="true"
                    :data="purchases.data"
                    :columns="columns"
                    :perPageOptions="[5, 10, 25, 50, 100]"
                    v-model:params="params"
                    :pagination="purchases"
                >
                    <template #supplier="{ row }">
                        <div class="flex flex-col">
                            <span class="font-medium">{{
                                row.supplier ? row.supplier.name : "Umum/Cash"
                            }}</span>
                            <span class="text-xs text-gray-500">{{
                                row.supplier ? row.supplier.phone : "-"
                            }}</span>
                        </div>
                    </template>
                    <template #status="{ row }">
                        <span
                            class="px-2 py-1 text-xs font-bold uppercase rounded"
                            :class="{
                                'bg-gray-200 text-gray-600':
                                    row.status === 'draft',
                                'bg-blue-100 text-blue-600':
                                    row.status === 'dipesan',
                                'bg-yellow-100 text-yellow-600':
                                    row.status === 'dikirim',
                                'bg-purple-100 text-purple-600':
                                    row.status === 'diterima',
                                'bg-teal-100 text-teal-600':
                                    row.status === 'checking',
                                'bg-green-100 text-green-600':
                                    row.status === 'selesai',
                                'bg-red-100 text-red-600':
                                    row.status === 'dibatalkan',
                            }"
                        >
                            {{ row.status }}
                        </span>
                    </template>
                    <template #aksi="{ row }">
                        <div class="inline-flex w-full gap-2">
                            <Link
                                v-if="row.status == 'checking'"
                                :href="route('purchases.checking', row.id)"
                                class="px-3 py-1 text-sm font-medium text-teal-600 transition border border-teal-200 rounded hover:text-teal-900 hover:bg-teal-300"
                            >
                                Validasi Barang
                            </Link>
                            <Link
                                v-else
                                :href="route('purchases.show', row.id)"
                                class="px-3 py-1 text-sm font-medium text-indigo-600 transition border border-indigo-200 rounded hover:text-indigo-900 hover:bg-indigo-300"
                            >
                                Lihat Detail
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
