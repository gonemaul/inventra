<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
    <Head title="User" />

    <AuthenticatedLayout headerTitle="User">
        <div
            class="justify-center w-full p-4 bg-white shadow rounded-xl dark:bg-gray-800"
        >
            <h2
                class="py-2 mb-4 text-lg font-semibold text-center text-gray-800 border-b border-gray-300 dark:text-white"
            >
                Tambah Kategori
            </h2>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                    >Kode</label
                >
                <div class="flex gap-2">
                    <input
                        type="text"
                        placeholder="Kode"
                        class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                </div>
            </div>
            <div class="mb-4">
                <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                    >Nama</label
                >
                <div class="flex gap-2">
                    <input
                        type="text"
                        placeholder="Nama"
                        class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                    />
                </div>
            </div>
            <div class="flex justify-center gap-2">
                <button
                    class="px-4 py-2 text-sm text-white rounded-lg bg-lime-500 hover:bg-lime-600"
                >
                    Tambah
                </button>
                <button
                    class="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white"
                >
                    Batal
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
