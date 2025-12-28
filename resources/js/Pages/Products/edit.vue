<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import FormCreateEdit from "./partials/form-create-edit.vue";
import FormProduct from "./Components/FormProduct.vue";

const props = defineProps({
    dropdowns: Object,
    product: Object, // Bisa null (create) atau berisi data (edit)
});
</script>
<template>
    <Head title="Edit Barang" />

    <AuthenticatedLayout headerTitle="Edit Barang">
        <div class="w-full min-h-screen">
            <!-- <FormCreateEdit
                :dropdowns="props.dropdowns"
                mode="edit"
                :product="product"
            /> -->
            <FormProduct
                :dropdowns="dropdowns"
                :product="product"
                mode="edit"
            />
        </div>
    </AuthenticatedLayout>
</template>
