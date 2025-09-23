<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import DataTable from "@/Components/DataTable.vue";
import OpsiTable from "@/Components/OpsiTable.vue";
import Filter from "@/Components/Filter.vue";

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

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    {
        key: "tanggal_order",
        label: "Tanggal Order",
        sortable: true,
        width: "200px",
        format: "tanggal",
    },
    {
        key: "tanggal_pengiriman",
        label: "Tanggal Pengiriman",
        sortable: true,
        width: "250px",
        format: "tanggal",
    },
    {
        key: "item",
        label: "Total Item",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "total",
        label: "Total Belanja",
        sortable: true,
        width: "200px",
        format: "rupiah",
    },
    {
        key: "status",
        label: "Status",
        sortable: true,
        width: "200px",
        rupiah: true,
        slot: "status",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];
const allData = [
    {
        id: 1,
        tanggal_order: "2025-08-01",
        tanggal_pengiriman: "2025-08-01",
        item: 2,
        total: 2000000,
        status: "active",
    },
    {
        id: 2,
        tanggal_order: "2025-08-03",
        tanggal_pengiriman: "2025-08-03",
        item: 4,
        total: 3000000,
        status: "inactive",
    },
    {
        id: 3,
        tanggal_order: "2025-08-05",
        tanggal_pengiriman: "2025-08-05",
        item: 7,
        total: 1500000,
        status: "active",
    },
];
</script>

<template>
    <Head title="Pembelian" />

    <AuthenticatedLayout headerTitle="Pembelian">
        <div class="w-full min-h-screen px-4 space-y-6">
            <Filter
                :actions="[
                    { route: route('purchases.create'), buttonText: 'RAB' },
                ]"
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
                </div>
                <!-- Data Table -->
                <DataTable :columns="columns" :data="allData" :params="params">
                    <template #status="{ row }">
                        <span
                            :class="
                                row.status === 'active'
                                    ? 'px-2 py-1 rounded bg-green-100 text-green-700'
                                    : 'px-2 py-1 rounded bg-red-100 text-red-700'
                            "
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
                                :href="route('checking')"
                                class="block w-full px-4 py-2 text-sm text-left border-b-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                👁 View
                            </Link>
                            <button
                                @click="alert('Edit ' + row.tanggal_order)"
                                class="block w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                ✏️ Edit
                            </button>
                            <button
                                @click="alert('Delete ' + row.tanggal_order)"
                                class="block w-full px-4 py-2 text-sm text-left text-red-600 hover:bg-red-100 dark:hover:bg-red-700"
                            >
                                🗑 Delete
                            </button>
                        </OpsiTable>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
