<script setup>
import DataTable from "@/Components/DataTable.vue";
const columns = [
    {
        key: "name",
        label: "Nama Barang",
        sortable: true,
        width: "200px",
        slot: "name",
    },
    {
        key: "stock",
        label: "Stok",
        sortable: true,
        width: "200px",
    },
    {
        key: "price",
        label: "Harga",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "restock",
        label: "Restock",
        sortable: true,
        width: "200px",
    },
    {
        key: "total",
        label: "Total",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];
const allData = [
    {
        id: 1,
        name: "Sepatu Nike",
        category: "Sepatu",
        size: "42",
        satuan: "Pasang",
        stock: 10,
        price: 500000,
        restock: 5,
        total: 2500000,
    },
    {
        id: 2,
        name: "Baju Adidas",
        category: "Baju",
        size: "L",
        satuan: "Pcs",
        stock: 20,
        price: 300000,
        restock: 10,
        total: 3000000,
    },
    {
        id: 3,
        name: "Celana Puma",
        category: "Celana",
        size: "M",
        satuan: "Pcs",
        stock: 15,
        price: 400000,
        restock: 7,
        total: 2800000,
    },
    {
        id: 4,
        name: "Topi Reebok",
        category: "Aksesoris",
        size: "One Size",
        satuan: "Pcs",
        stock: 30,
        price: 150000,
        restock: 15,
        total: 2250000,
    },
    {
        id: 5,
        name: "Tas Converse",
        category: "Aksesoris",
        size: "Medium",
        satuan: "Pcs",
        stock: 12,
        price: 600000,
        restock: 6,
        total: 3600000,
    },
];
</script>
<template>
    <!-- Data Table -->
    <DataTable :columns="columns" :data="allData" :params="params">
        <template #name="{ row }">
            <div class="flex flex-col">
                <span class="font-medium">{{ row.name }}</span>
                <span class="text-sm text-gray-500">
                    {{ row.category }} - {{ row.size }} - {{ row.satuan }}
                </span>
            </div>
        </template>
        <template #aksi="{ row }">
            <button class="px-2 py-1 text-white bg-blue-500 rounded">
                Edit
            </button>
            <button class="px-2 py-1 ml-2 text-white bg-red-500 rounded">
                Hapus
            </button>
        </template>
    </DataTable>
</template>
