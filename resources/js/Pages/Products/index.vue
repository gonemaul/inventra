<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import Filter from "./partials/filter.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import CardProduct from "./partials/card-product.vue";
import Pagination from "./partials/pagination.vue";
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import { Link } from "@inertiajs/vue3";

const showFilter = ref(false);
</script>

<template>
    <Head title="Data Barang" />

    <AuthenticatedLayout headerTitle="Data Barang">
        <div class="w-full min-h-screen">
            <!-- Modal -->
            <Modal :show="showFilter" @close="showFilter = false" maxWidth="lg">
                <Filter />
            </Modal>
            <div
                class="flex justify-between px-5 py-3 bg-gray-100 rounded shadow-md dark:bg-customBg-tableDark"
            >
                <TextInput class="w-3/6" placeholder="Cari Produk..." />
                <div class="flex justify-end">
                    <PrimaryButton class="ms-4" @click="showFilter = true"
                        >Filter</PrimaryButton
                    >
                    <Link
                        :href="route('products.create')"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md cursor-pointer ms-4 dark:bg-gray-800 bg-lime-500 hover:bg-lime-400 dark:hover:bg-gray-900 focus:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-lime-500 focus:ring-offset-2 active:bg-lime-900"
                    >
                        Tambah Produk
                    </Link>
                </div>
            </div>
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
