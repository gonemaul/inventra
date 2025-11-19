<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";
import Tabs from "@/Components/Tabs.vue";
import tabKategori from "./partials/tab-content/tab-kategori.vue";
import tabSatuan from "./partials/tab-content/tab-satuan.vue";
import tabUkuran from "./partials/tab-content/tab-ukuran.vue";
import tabSupplier from "./partials/tab-content/tab-supplier.vue";
import tabBackup from "./partials/tab-content/tab-backup.vue";
import tabMerk from "./partials/tab-content/tab-merk.vue";
import tabType from "./partials/tab-content/tab-type.vue";
import TabMerk from "./partials/tab-content/tab-merk.vue";

const props = defineProps({
    categoryCount: Number,
    unitCount: Number,
    sizeCount: Number,
    supplierCount: Number,
    brandCount: Number,
    productTypeCount: Number,
});
const tabs = computed(() => [
    { key: "kategori", label: "Kategori", count: props.categoryCount },
    {
        key: "product_type",
        label: "Tipe Produk",
        count: props.productTypeCount,
    },
    { key: "satuan", label: "Satuan", count: props.unitCount },
    { key: "ukuran", label: "Ukuran", count: props.sizeCount },
    { key: "brand", label: "Merk", count: props.brandCount },
    { key: "supplier", label: "Supplier", count: props.supplierCount },
    { key: "backup", label: "Backup & Import", count: 0 },
]);
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout headerTitle="Pengaturan">
        <div class="w-full space-y-5 max-w-screen">
            <Tabs :tabs="tabs" defaultTab="kategori">
                <template #kategori><tabKategori /></template>
                <template #product_type><tabType /></template>
                <template #satuan><tabSatuan /></template>
                <template #ukuran><tabUkuran /></template>
                <template #brand><tabMerk /></template>
                <template #supplier><tabSupplier /></template>
                <template #backup><tabBackup /></template>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
