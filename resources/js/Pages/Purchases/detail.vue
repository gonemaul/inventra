<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Tabs from "@/Components/Tabs.vue";
import invoiceTable from "./partials/invoiceTable.vue";
import productTable from "./partials/productTable.vue";
import HeaderDetail from "./partials/HeaderDetail.vue";
import { usePage } from "@inertiajs/vue3";

const { props } = usePage();
const type = props.type || "detail";
const tabs = [
    { key: "invoices", label: "Invoice" },
    { key: "products", label: "Produk" },
];

const invoice = {
    supplier: "PT Sumber Rejeki",
    order_code: "ORD-2025-001",
    tanggal_order: "2025-09-01",
    tanggal_datang: "2025-09-03",
    jumlah_invoice: 3,
    total_nominal: 6500000,
    status: "Checking",

    total_produk: 12,
    total_qty_order: 150,
    total_qty_diterima: 145,
    qty_sesuai: 140,
    qty_kurang: 5,

    catatan: "Ada selisih 5 pcs minyak goreng dari nota fisik.",
};
</script>
<template>
    <Head title="Checking"></Head>
    <AuthenticatedLayout
        :headerTitle="type == 'checking' ? 'Checking' : 'Detail'"
        :showSidebar="false"
    >
        <div class="w-full min-h-screen space-y-6">
            <HeaderDetail :data="invoice" :mode="type" />
            <Tabs :tabs="tabs" defaultTab="invoices">
                <template #invoices>
                    <invoiceTable />
                </template>
                <template #products>
                    <productTable />
                </template>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
