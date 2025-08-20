<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import CardDetail from "./partials/card-detail.vue";
</script>
<template>
    <Head title="Detail Barang" />

    <AuthenticatedLayout headerTitle="Detail Barang">
        <div class="w-full min-h-screen">
            <CardDetail />
        </div>
    </AuthenticatedLayout>
</template>
