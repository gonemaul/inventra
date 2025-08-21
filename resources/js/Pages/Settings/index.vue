<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Tabs from "./partials/tabs.vue";
import tabKategori from "./partials/tab-content/tab-kategori.vue";
import tabSatuan from "./partials/tab-content/tab-satuan.vue";
import tabUkuran from "./partials/tab-content/tab-ukuran.vue";
import tabSupplier from "./partials/tab-content/tab-supplier.vue";
import tabBackup from "./partials/tab-content/tab-backup.vue";

const tabs = [
    { key: "kategori", label: "Kategori", count: 3 },
    { key: "satuan", label: "Satuan", count: 2 },
    { key: "ukuran", label: "Ukuran", count: 5 },
    { key: "supplier", label: "Supplier", count: 7 },
    { key: "backup", label: "Backup & Import", count: 0 },
];
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout headerTitle="Pengaturan">
        <div class="w-full min-h-screen space-y-5">
            <Tabs :tabs="tabs" defaultTab="kategori">
                <template #kategori>
                    <tabKategori />
                </template>
                <template #satuan>
                    <tabSatuan />
                </template>
                <template #ukuran>
                    <tabUkuran />
                </template>
                <template #supplier>
                    <tabSupplier />
                </template>
                <template #backup>
                    <tabBackup />
                </template>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
