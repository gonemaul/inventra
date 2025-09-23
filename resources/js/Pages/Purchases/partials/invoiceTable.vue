<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref } from "vue";
import Filter from "@/Components/Filter.vue";
import { Link } from "@inertiajs/vue3";

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    { key: "code", label: "No Incoice", sortable: true, width: "120px" },
    {
        key: "tanggal",
        label: "Tanggal",
        sortable: true,
        width: "200px",
        format: "tanggal",
    },
    { key: "qty", label: "Qty", sortable: true, width: "200px" },
    {
        key: "nominal",
        label: "Nominal",
        sortable: true,
        width: "200px",
        format: "rupiah",
    },
    {
        key: "status",
        label: "Status",
        sortable: true,
        width: "120px",
        slot: "status",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

const allData = [
    {
        id: 1,
        code: "INV-001",
        tanggal: "2025-09-01",
        qty: 15,
        nominal: 1500000,
        status: "Valid",
    },
    {
        id: 2,
        code: "INV-002",
        tanggal: "2025-09-02",
        qty: 8,
        nominal: 850000,
        status: "Invalid",
    },
    {
        id: 3,
        code: "INV-003",
        tanggal: "2025-09-03",
        qty: 20,
        nominal: 3200000,
        status: "Invalid",
    },
    {
        id: 4,
        code: "INV-004",
        tanggal: "2025-09-04",
        qty: 5,
        nominal: 400000,
        status: "Valid",
    },
    {
        id: 5,
        code: "INV-005",
        tanggal: "2025-09-05",
        qty: 12,
        nominal: 1200000,
        status: "Invalid",
    },
];
</script>
<template>
    <div
        class="p-4 space-y-5 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
    >
        <Filter
            :actions="[{ buttonText: 'Tambah Invoice', route: 'invoice' }]"
        />
        <DataTable :columns="columns" :params="params" :data="allData">
            <!-- Slot aksi -->
            <template #status="{ row }">
                <span
                    :class="{
                        'px-2 py-1 text-white rounded': true,
                        'bg-green-500': row.status === 'Valid',
                        'bg-red-500': row.status === 'Invalid',
                    }"
                >
                    {{ row.status }}
                </span>
            </template>
            <template #aksi="{ row }">
                <button class="px-2 py-1 text-white bg-blue-500 rounded">
                    Edit
                </button>
                <button class="px-2 py-1 ml-2 text-white bg-red-500 rounded">
                    Hapus
                </button>
                <Link
                    :href="route('checking-detail', row)"
                    class="px-2 py-2 ml-2 text-sm font-medium text-white transition bg-blue-500 rounded hover:bg-blue-600 md:text-base"
                >
                    Check
                </Link>
            </template>
        </DataTable>
    </div>
</template>
