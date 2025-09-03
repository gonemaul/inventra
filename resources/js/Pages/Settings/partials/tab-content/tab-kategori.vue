<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import formCreate from "../form-create.vue";
import DataTable from "@/Components/DataTable.vue";
import { ref } from "vue";

const showCreate = ref(false);

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    { key: "code", label: "Kode", sortable: true, width: "120px" },
    { key: "name", label: "Nama", sortable: true, width: "200px" },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];
</script>
<template>
    <formCreate :show="showCreate" @close="showCreate = false"></formCreate>
    <div
        class="p-4 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
    >
        <div
            class="flex flex-col gap-3 mb-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <!-- Title & Description -->
            <div>
                <h2
                    class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                >
                    Kelola Kategori
                </h2>
                <p
                    class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                >
                    Kelola semua kategori untuk kategori produk.
                </p>
            </div>

            <!-- Button -->
            <PrimaryButton
                class="text-xs text-center sm:ms-4 sm:text-sm lg:text-base"
                @click="showCreate = true"
            >
                Tambah Kategori
            </PrimaryButton>
        </div>
        <DataTable
            :columns="columns"
            :serverSide="true"
            :perPageOptions="[2, 3, 4, 5, 10]"
            endpoint="/settings/category"
            :params="params"
        >
            <!-- Slot aksi -->
            <template #aksi="{ row }">
                <button class="px-2 py-1 text-white bg-blue-500 rounded">
                    Edit
                </button>
                <button class="px-2 py-1 ml-2 text-white bg-red-500 rounded">
                    Hapus
                </button>
            </template>
        </DataTable>
    </div>
</template>
