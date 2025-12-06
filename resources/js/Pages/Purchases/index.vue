<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import DataTable from "@/Components/DataTable.vue";
import OpsiTable from "@/Components/OpsiTable.vue";
import Filter from "@/Components/Filter.vue";
import FilterModal from "./partials/modalFilter.vue";
import { useActionLoading } from "@/Composable/useActionLoading";
import DeleteConfirm from "@/Components/DeleteConfirm.vue";
import ConfirmModal from "@/Components/ConfirmModal.vue";
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
const search = ref(props.filters.search || "");
const showFilterModal = ref(false);
const { isActionLoading } = useActionLoading();
const showConfirmDelete = ref(null);
const showTrashed = ref(false);
const showConfirmModal = ref(false); // State untuk menampilkan modal
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

const params = ref({
    search: props.filters.search || "",
    sort: props.filters.sort || "transaction_date",
    order: props.filters.order || "desc",
    page: props.purchases.current_page || 1, // Penting untuk pagination
});

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

const updateStatus = (row, newStatus) => {
    const config = {
        title: `Konfirmasi Perubahan Status`,
        message: `Anda yakin ingin mengubah status transaksi ${
            row.reference_no
        } menjadi ${newStatus.toUpperCase()}?`,
        itemName: row.reference_no,
        url: route("purchases.update-status", { purchase: row.id }),
        method: "put",
        // Tambahkan data status yang akan dikirim
        data: { status: newStatus },
    };
    showConfirmModal.value.open(config);
};

const confirmDelete = (row) => {
    const config = {
        title: `Hapus transaksi ${row.reference_no}?`,
        message: `Aksi ini akan menghapus semua item terkait dan tidak dapat dibatalkan. Status saat ini: ${row.status}`,
        itemName: row.name,
        url: route("purchases.destroy", {
            purchase: row.id,
            permanen: false,
        }),
    };
    showConfirmDelete.value.open(config);
};

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
            Object.entries(newParams).filter(([_, v]) => v != null && v !== "")
        );
        isActionLoading.value = true;
        router.get(route("purchases.index"), query, {
            preserveState: true, // Jangan scroll ke atas
            preserveScroll: true,
            replace: true, // Jangan penuh-penuhin history browser
            only: ["purchases"], // Optimasi: Cuma update data tabel
            onFinish: () => {
                isActionLoading.value = false;
            },
        });
    }, 300),
    { deep: true }
);

watch(search, performSearch);

const activeFilterCount = computed(() => {
    const filterKeys = Object.keys(props.filters);
    const ignoredKeys = ["search", "page", "per_page"];
    return filterKeys.filter((key) => !ignoredKeys.includes(key)).length;
});

const getActions = (row) => {
    const actions = [];

    // --- A. AKSI STANDAR (Selalu Ada) ---
    actions.push({
        label: "📄 Detail",
        icon: "eye",
        type: "link",
        route: route("purchases.show", row.id),
    });
    actions.push({
        label: "Cetak PO",
        icon: "print",
        type: "print",
        route: route("purchases.print", row.id),
        target: "_blank",
    });

    // --- B. AKSI OPERASIONAL (Conditional berdasarkan Status) ---
    switch (row.status) {
        case "draft":
            actions.push({
                label: "✏️ Edit",
                icon: "edit",
                type: "link",
                route: route("purchases.edit", row.id),
            });
            actions.push({
                label: "▶️ Tandai Dipesan",
                icon: "play",
                type: "status",
                newStatus: "dipesan",
            });
            actions.push({ label: "🗑️ Hapus", icon: "delete", type: "delete" });
            break;

        case "dipesan":
            actions.push({
                label: "🚚 Tandai Dikirim",
                icon: "truck",
                type: "status",
                newStatus: "dikirim",
            });
            actions.push({
                label: "✏️ Edit",
                icon: "edit",
                type: "link",
                route: route("purchases.edit", row.id),
            });
            actions.push({ label: "🗑️ Hapus", icon: "delete", type: "delete" });
            break;

        case "dikirim":
            actions.push({
                label: "📦 Tandai Diterima",
                icon: "receive",
                type: "status",
                newStatus: "diterima",
            });
            break;

        case "diterima":
            actions.push({
                label: "✅ Validasi Barang",
                icon: "check",
                type: "link",
                route: route("purchases.checking", row.id),
                isPrimary: true,
            });
            actions.push({
                label: "🔄 Batalkan Terima",
                icon: "undo",
                type: "status",
                newStatus: "dikirim",
            }); // Kembali ke shipped
            break;

        case "checking":
            actions.push({
                label: "✏️ Lanjut Checking",
                icon: "edit",
                type: "link",
                route: route("purchases.checking", row.id),
            });
            break;

        case "completed":
        case "cancelled":
            // Status final, hanya View dan Print yang tersedia (sudah di Aksi Standar)
            break;
    }

    return actions;
};
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Pembelian">
        <DeleteConfirm ref="showConfirmDelete" @success="refreshTable" />
        <ConfirmModal ref="showConfirmModal" @success="refreshTable" />
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
                    <template #aksi="{ row }"
                        ><OpsiTable
                            v-if="row.deleted_at == null"
                            :row-id="row.id"
                            :active-id="activeId"
                            :position="position"
                            @toggle="toggleDropdown"
                            @close="activeId = null"
                        >
                            <template
                                v-for="(action, index) in getActions(row)"
                                :key="index"
                            >
                                <Link
                                    v-if="action.type === 'link'"
                                    :href="action.route"
                                    :target="action.target || '_self'"
                                    :class="{
                                        'text-green-600 font-semibold':
                                            action.isPrimary,
                                        'border-b-2 border-gray-100':
                                            action.label.includes('Detail'), // Garis pemisah
                                    }"
                                    class="block w-full px-4 py-2 text-sm text-left text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-100"
                                >
                                    {{ action.label }}
                                </Link>
                                <a
                                    v-else-if="action.type === 'print'"
                                    :href="action.route"
                                    target="action.target || '_self'"
                                    class="block w-full px-4 py-2 text-sm text-left text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-100"
                                >
                                    <span class="inline-flex gap-1">
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
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                            ></path>
                                        </svg>
                                        {{ action.label }}
                                    </span>
                                </a>
                                <button
                                    v-else-if="action.type === 'status'"
                                    @click="updateStatus(row, action.newStatus)"
                                    :class="{
                                        'text-lime-600 font-semibold':
                                            action.isPrimary,
                                        'border-b-2':
                                            action.newStatus === 'ordered', // Garis pemisah
                                    }"
                                    class="block w-full px-4 py-2 text-sm text-left text-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-100"
                                >
                                    {{ action.label }}
                                </button>

                                <button
                                    v-else-if="action.type === 'delete'"
                                    @click="confirmDelete(row)"
                                    class="block w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30"
                                >
                                    {{ action.label }}
                                </button>

                                <hr
                                    v-if="action.newStatus === 'shipped'"
                                    class="my-1 border-gray-200 dark:border-gray-600"
                                />
                            </template>
                        </OpsiTable>
                        <span v-else>Sudah dihapus</span>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
