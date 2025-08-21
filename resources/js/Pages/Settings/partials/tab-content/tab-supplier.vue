<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import formCreate from "../form-create.vue";
import { ref } from "vue";

const showCreate = ref(false);

import DataTable from "@/Components/DataTable.vue";

const allData = [
    {
        id: 1,
        nama: "Elektronik",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 2,
        nama: "Fashion",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 3,
        nama: "Makanan & Minuman",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 4,
        nama: "Otomotif",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 5,
        nama: "Kesehatan",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 6,
        nama: "Olahraga",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 7,
        nama: "Kosmetik",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 8,
        nama: "Peralatan Rumah",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 9,
        nama: "Alat Tulis",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
    {
        id: 10,
        nama: "Mainan",
        no_hp: "08208289918",
        alamat: "Indonesia",
        type: "Offline",
        status: "Active",
    },
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
        title="Supplier"
        isSupplier="true"
    ></formCreate>
    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-600">
        <div class="flex justify-between">
            <div class="">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Kelola Supplier
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Kelola semua supplier anda.
                </p>
            </div>
            <PrimaryButton class="ms-4" @click="showCreate = true"
                >Tambah Supplier</PrimaryButton
            >
        </div>
        <div class="p-4 my-3 bg-gray-100 rounded-md dark:bg-gray-600">
            <DataTable
                :columns="[
                    { key: 'nama', label: 'Nama' },
                    { key: 'no_hp', label: 'No Hp' },
                    { key: 'alamat', label: 'Alamat' },
                    { key: 'type', label: 'Type' },
                    { key: 'status', label: 'Status' },
                ]"
                :fetchData="fetchCategories"
                :perPage="5"
                @edit="editItem"
                @delete="deleteItem"
            />
        </div>
    </div>
</template>
