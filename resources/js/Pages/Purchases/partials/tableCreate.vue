<script setup>
import DataTable from "@/Components/DataTable.vue";
const props = defineProps({
    items: Array, // Ini adalah form.items dari Induk
});
const emit = defineEmits(["remove", "edit"]);
const rupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);
const columns = [
    {
        key: "product_details",
        label: "Produk",
        sortable: true,
        slot: "product_details",
    },
    {
        key: "current_stock",
        label: "Stok",
        sortable: true,
    },
    {
        key: "quantity",
        label: "Qty Beli",
        sortable: true,
        width: "10%",
    },
    {
        key: "purchase_price",
        label: "Harga Satuan",
        sortable: true,
        format: "rupiah",
    },
    {
        key: "subtotal",
        label: "Total",
        sortable: true,
        format: "rupiah",
    },
    { key: "actions", label: "Aksi", width: "120px", slot: "actions" },
];
</script>
<template>
    <!-- Data Table -->
    <DataTable :columns="columns" :data="items" :params="params">
        <template #product_details="{ row }">
            <div class="flex flex-col">
                <span class="font-bold text-gray-800 dark:text-white">
                    {{ row.name }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ row.code }} | {{ row.category }}
                </span>
                <span class="text-xs text-gray-400">
                    {{ row.size }} - {{ row.unit }}
                </span>
            </div>
        </template>
        <template #actions="{ row }">
            <div class="flex gap-2">
                <button
                    @click="$emit('edit', row)"
                    type="button"
                    class="px-2 py-1 text-xs text-white bg-yellow-500 rounded hover:bg-yellow-600"
                >
                    Edit
                </button>

                <button
                    @click="$emit('remove', row.product_id)"
                    type="button"
                    class="px-2 py-1 text-xs text-white bg-red-500 rounded hover:bg-red-600"
                >
                    Hapus
                </button>
            </div>
        </template>
    </DataTable>
</template>
