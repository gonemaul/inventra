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

const allData = [
    { id: 1, code: "KTG001", name: "Elektronik" },
    { id: 2, code: "KTG002", name: "Fashion" },
    { id: 3, code: "KTG003", name: "Makanan & Minuman" },
    { id: 4, code: "KTG004", name: "Otomotif" },
    { id: 5, code: "KTG005", name: "Kesehatan" },
    { id: 6, code: "KTG006", name: "Olahraga" },
    { id: 7, code: "KTG007", name: "Kosmetik" },
    { id: 8, code: "KTG008", name: "Peralatan Rumah" },
    { id: 9, code: "KTG009", name: "Alat Tulis" },
    { id: 10, code: "KTG010", name: "Mainan" },
];
</script>
<template>
    <formCreate
        :show="showCreate"
        @close="showCreate = false"
        title="Ukuran"
    ></formCreate>
    <div
        class="p-4 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
    >
        <div
            class="flex flex-col gap-3 mb-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2
                    class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                >
                    Kelola Ukuran
                </h2>
                <p
                    class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                >
                    Kelola semua ukuran produk.
                </p>
            </div>
            <PrimaryButton
                class="text-xs text-center sm:ms-4 sm:text-sm lg:text-base"
                @click="showCreate = true"
                >Tambah Ukuran</PrimaryButton
            >
        </div>
        <DataTable :columns="columns" :params="params" :data="allData">
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
