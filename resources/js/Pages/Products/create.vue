<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import FormProduct from "./Components/FormProduct.vue";

const props = defineProps({
    dropdowns: Object,
    product: Object, // Bisa null (create) atau berisi data (edit)
});
</script>
<template>
    <Head title="Tambah Barang" />

    <AuthenticatedLayout headerTitle="Tambah Barang">
        <div class="w-full min-h-screen">
            <FormProduct :dropdowns="props.dropdowns" mode="create" />
            <!-- <FormCreateEdit :dropdowns="props.dropdowns" mode="create" /> -->
        </div>
    </AuthenticatedLayout>
</template>
