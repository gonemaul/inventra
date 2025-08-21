<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import formCreate from "../form-create.vue";
import { ref } from "vue";

const showCreate = ref(false);

import DataTable from "@/Components/DataTable.vue";

const allData = [
    { id: 1, kode: "KTG001", nama: "Elektronik" },
    { id: 2, kode: "KTG002", nama: "Fashion" },
    { id: 3, kode: "KTG003", nama: "Makanan & Minuman" },
    { id: 4, kode: "KTG004", nama: "Otomotif" },
    { id: 5, kode: "KTG005", nama: "Kesehatan" },
    { id: 6, kode: "KTG006", nama: "Olahraga" },
    { id: 7, kode: "KTG007", nama: "Kosmetik" },
    { id: 8, kode: "KTG008", nama: "Peralatan Rumah" },
    { id: 9, kode: "KTG009", nama: "Alat Tulis" },
    { id: 10, kode: "KTG010", nama: "Mainan" },
];

async function fetchCategories({ search, page, perPage, sortKey, sortOrder }) {
    let filtered = [...allData];

    // Searching
    if (search) {
        const q = search.toLowerCase();
        filtered = filtered.filter(
            (item) =>
                item.kode.toLowerCase().includes(q) ||
                item.nama.toLowerCase().includes(q)
        );
    }

    // Sorting
    if (sortKey) {
        filtered.sort((a, b) => {
            if (a[sortKey] < b[sortKey]) return sortOrder === "asc" ? -1 : 1;
            if (a[sortKey] > b[sortKey]) return sortOrder === "asc" ? 1 : -1;
            return 0;
        });
    }

    // Pagination
    const total = filtered.length;
    const start = (page - 1) * perPage;
    const paginated = filtered.slice(start, start + perPage);

    return {
        data: paginated,
        total,
    };
}

function editItem(item) {
    alert("Edit: " + item.nama);
}
function deleteItem(item) {
    alert("Hapus: " + item.nama);
}
</script>
<template>
    <formCreate
        :show="showCreate"
        @close="showCreate = false"
        title="Satuan"
    ></formCreate>
    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-600">
        <div class="flex justify-between">
            <div class="">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Kelola Satuan
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Kelola semua satuan untuk produk.
                </p>
            </div>
            <PrimaryButton class="ms-4" @click="showCreate = true"
                >Tambah Satuan</PrimaryButton
            >
        </div>
        <div class="p-4 my-3 bg-gray-100 rounded-md dark:bg-gray-600">
            <DataTable
                :columns="[
                    { key: 'kode', label: 'Kode' },
                    { key: 'nama', label: 'Nama' },
                ]"
                :fetchData="fetchCategories"
                :perPage="5"
                @edit="editItem"
                @delete="deleteItem"
            />
        </div>
    </div>
</template>
