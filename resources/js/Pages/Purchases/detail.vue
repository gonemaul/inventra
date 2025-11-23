<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Tabs from "@/Components/Tabs.vue";
import invoiceTable from "./partials/invoiceTable.vue";
import productTable from "./partials/productTable.vue";
import HeaderDetail from "./partials/HeaderDetail.vue";
import { usePage } from "@inertiajs/vue3";
import PurchaseDetail from "./PurchaseDetail.vue";

defineProps({
    type: String || "detail",
    purchase: Object || {},
});
</script>
<template>
    <Head title="Detail"></Head>
    <AuthenticatedLayout :headerTitle="type.toUpperCase()" :showSidebar="false">
        <div class="w-full min-h-screen space-y-6">
            <PurchaseDetail />
        </div>
    </AuthenticatedLayout>
</template>
