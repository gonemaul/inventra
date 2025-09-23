<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import FilterModal from "./partials/modalfilter.vue";
import CardProduct from "./partials/card-product.vue";
import Pagination from "./partials/pagination.vue";
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import Filter from "@/Components/Filter.vue";

const showFilter = ref(false);
</script>

<template>
    <Head title="Data Barang" />

    <AuthenticatedLayout headerTitle="Data Barang">
        <div class="w-full min-h-screen">
            <!-- Modal -->
            <Modal :show="showFilter" @close="showFilter = false" maxWidth="lg">
                <FilterModal />
            </Modal>
            <Filter
                @showFilter="showFilter = true"
                :actions="[
                    {
                        route: route('products.create'),
                        buttonText: 'Tambah Produk',
                    },
                ]"
            />
            <Pagination />
            <div
                class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-1 lg:grid-cols-2"
            >
                <CardProduct />
                <CardProduct />
                <CardProduct />
                <CardProduct />
                <CardProduct />
                <CardProduct />
                <CardProduct />
                <CardProduct />
            </div>
            <Pagination />
        </div>
    </AuthenticatedLayout>
</template>
