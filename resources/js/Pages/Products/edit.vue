<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import FormCreateEdit from "./partials/form-create-edit.vue";
</script>
<template>
    <Head title="Edit Barang" />

    <AuthenticatedLayout headerTitle="Edit Barang">
        <div class="w-full min-h-screen">
            <FormCreateEdit />
        </div>
    </AuthenticatedLayout>
</template>
