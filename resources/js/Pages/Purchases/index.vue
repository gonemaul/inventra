<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import DataTable from "@/Components/DataTable.vue";
import OpsiTable from "@/Components/OpsiTable.vue";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import { router } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { throttle } from "lodash";

const props = defineProps({
    dropdowns: Object, // Paginator object
    filters: Object,
    purchases: Object,
});

const activeId = ref(null);
const position = ref({ top: 0, left: 0 });
function toggleDropdown(id, event) {
    if (activeId.value === id) {
        activeId.value = null;
    } else {
        const rect = event.target.getBoundingClientRect();
        position.value = {
            top: rect.bottom + window.scrollY,
            left: rect.left + window.scrollX,
        };
        activeId.value = id;
    }
}

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
        key: "items_sum_subtotal",
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
const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const showConfirmModal = ref(null);

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.purchases.current_page || 1, // Penting untuk pagination
});

watch(
    params,
    throttle((newParams) => {
        const query = Object.fromEntries(
            Object.entries(newParams).filter(([_, v]) => v != null && v !== "")
        );

        router.get(route("purchases.index"), query, {
            preserveState: true, // Jangan scroll ke atas
            preserveScroll: true,
            replace: true, // Jangan penuh-penuhin history browser
            only: ["purchases"], // Optimasi: Cuma update data tabel
        });
    }, 300),
    { deep: true }
);

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
watch(search, performSearch);

const activeFilterCount = computed(() => {
    const filterKeys = Object.keys(props.filters);
    console.log(filterKeys);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});

const openDeleteModal = (purchase, isPermanent = false) => {
    config = {
        title: "Hapus Permanen Belanja",
        message: "Belanja ini akan dihapus selamanya. Anda yakin menghapus",
        itemName: purchase.name,
        url: route("purchases.destroy", {
            id: purchase.id,
            permanen: true,
        }),
    };
    showConfirmModal.value.open(config);
};
const updateStatus = (row, newStatus) => {
    if (confirm(`Ubah status menjadi ${newStatus}?`)) {
        router.put(
            route("purchases.update-status", row.id),
            {
                status: newStatus,
            },
            {
                preserveScroll: true,
            }
        );
    }
};
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Pembelian">
        <DeleteConfirm ref="showConfirmModal" @success="" />
        <div class="w-full min-h-screen px-4 space-y-6">
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
                                'bg-green-100 text-green-600':
                                    row.status === 'selesai',
                                'bg-red-100 text-red-600':
                                    row.status === 'dibatalkan',
                            }"
                        >
                            {{ row.status }}
                        </span>
                    </template>
                    <template #aksi="{ row }"
                        ><OpsiTable
                            :row-id="row.id"
                            :active-id="activeId"
                            :position="position"
                            @toggle="toggleDropdown"
                            @close="activeId = null"
                        >
                            <Link
                                :href="route('purchases.show', row.id)"
                                class="block w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700"
                                >Detail
                            </Link>
                            <a
                                target="_blank"
                                class="block w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Cetak PO
                            </a>
                            <template v-if="row.status === 'dipesan'">
                                <hr
                                    class="my-1 border-gray-200 dark:border-gray-600"
                                />

                                <Link
                                    :href="route('purchases.edit', row.id)"
                                    class="block w-full px-4 py-2 text-sm text-left text-yellow-600 hover:bg-yellow-50 dark:hover:bg-gray-700 dark:text-yellow-500"
                                >
                                    Edit Pesanan
                                </Link>

                                <button
                                    @click="confirmDelete(row)"
                                    class="block w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-red-50 dark:hover:bg-gray-700 dark:text-red-500"
                                >
                                    Hapus
                                </button>

                                <button
                                    @click="updateStatus(row, 'dikirim')"
                                    class="block w-full px-4 py-2 text-sm text-left text-blue-600 hover:bg-blue-50 dark:hover:bg-gray-700 dark:text-blue-500"
                                >
                                    Tandai Dikirim
                                </button>
                            </template>

                            <template v-if="row.status === 'dikirim'">
                                <hr
                                    class="my-1 border-gray-200 dark:border-gray-600"
                                />
                                <button
                                    @click="updateStatus(row, 'diterima')"
                                    class="block w-full px-4 py-2 text-sm font-semibold text-left text-purple-600 hover:bg-purple-50 dark:hover:bg-gray-700 dark:text-purple-400"
                                >
                                    Barang Sampai (Terima)
                                </button>
                            </template>

                            <template v-if="row.status === 'diterima'">
                                <hr
                                    class="my-1 border-gray-200 dark:border-gray-600"
                                />

                                <Link
                                    :href="route('purchases.checking', row.id)"
                                    class="block w-full px-4 py-2 text-sm font-bold text-left text-lime-600 hover:bg-lime-50 dark:hover:bg-gray-700 dark:text-lime-500"
                                >
                                    Validasi (Checking)
                                </Link>
                            </template>
                        </OpsiTable>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
