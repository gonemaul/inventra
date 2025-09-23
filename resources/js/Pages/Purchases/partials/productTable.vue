<script setup>
import DataTable from "@/Components/DataTable.vue";
import { ref } from "vue";
import Filter from "@/Components/Filter.vue";
import { Link } from "@inertiajs/vue3";

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    { key: "name", label: "Nama", sortable: true, width: "120px" },
    { key: "qty_pesan", label: "Qty Order", sortable: true, width: "120px" },
    {
        key: "harga_pesan",
        label: "Harga Lama",
        sortable: true,
        width: "120px",
        format: "rupiah",
    },
    {
        key: "qty_diterima",
        label: "Qty Diterima",
        sortable: true,
        width: "120px",
    },
    {
        key: "harga_diterima",
        label: "Harga Diterima",
        sortable: true,
        width: "120px",
        format: "rupiah",
    },

    {
        key: "invoice_code",
        sortable: true,
        label: "Invoice",
        width: "120px",
        slot: "invoice",
    },
    {
        key: "status",
        label: "Status",
        sortable: true,
        width: "120px",
        slot: "status",
    },
    // { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

const allData = [
    {
        id: 1,
        invoice_code: "INV-001",
        name: "Beras Premium 5kg",
        qty_pesan: 10,
        harga_pesan: 120000,
        qty_diterima: 10,
        harga_diterima: 120000,
        status: "Sesuai",
    },
    {
        id: 2,
        invoice_code: "INV-001",
        name: "Minyak Goreng 1L",
        qty_pesan: 20,
        harga_pesan: 15000,
        qty_diterima: 18,
        harga_diterima: 15000,
        status: "Kurang",
    },
    {
        id: 3,
        invoice_code: "INV-002",
        name: "Gula Pasir 1kg",
        qty_pesan: 15,
        harga_pesan: 14000,
        qty_diterima: 15,
        harga_diterima: 14500, // harga diterima lebih tinggi
        status: "Harga Berbeda",
    },
    {
        id: 4,
        invoice_code: "",
        name: "Tepung Terigu 1kg",
        qty_pesan: 0, // tidak dipesan
        harga_pesan: 0,
        qty_diterima: 5,
        harga_diterima: 12000,
        status: "Tidak Ada di Order",
    },
    {
        id: 5,
        invoice_code: "INV-003",
        name: "Kopi Bubuk 250gr",
        qty_pesan: 8,
        harga_pesan: 30000,
        qty_diterima: 6,
        harga_diterima: 30000,
        status: "Kurang",
    },
];
</script>
<template>
    <div
        class="p-4 space-y-5 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
    >
        <Filter />
        <DataTable :columns="columns" :params="params" :data="allData">
            <!-- Slot aksi -->
            <template #invoice="{ row }">
                <Link
                    v-if="row.invoice_code"
                    class="font-medium text-blue-600 hover:underline"
                    >{{ row.invoice_code }}</Link
                >
                <span v-else class="text-gray-500">Invalid</span>
            </template>
            <template #status="{ row }">
                <span
                    :class="{
                        'px-2 py-2 text-white rounded-md text-sm font-medium': true,
                        'bg-green-500': row.status === 'Sesuai',
                        'bg-yellow-500': row.status === 'Harga Berbeda',
                        'bg-red-500': row.status === 'Kurang',
                        'bg-gray-500': row.status === 'Tidak Ada di Order',
                    }"
                >
                    {{ row.status }}
                </span>
            </template>
        </DataTable>
    </div>
</template>
