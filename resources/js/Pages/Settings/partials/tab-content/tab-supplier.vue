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
    { key: "nama", label: "Nama", sortable: true, width: "120px" },
    { key: "no_hp", label: "No Hp", sortable: true, width: "200px" },
    { key: "alamat", label: "Alamat", sortable: true, width: "200px" },
    { key: "type", label: "Type", sortable: true, width: "200px" },
    {
        key: "status",
        label: "Status",
        sortable: true,
        width: "200px",
        slot: "status",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];

// Contoh data
const suppliers = [
    {
        id: 1,
        nama: "Toko A",
        no_hp: "081234567890",
        alamat: "Jl. Mawar No.1",
        type: "online",
        status: "active",
    },
    {
        id: 2,
        nama: "Toko B",
        no_hp: "082345678901",
        alamat: "Jl. Melati No.2",
        type: "offline",
        status: "inactive",
    },
    {
        id: 3,
        nama: "Toko C",
        no_hp: "083456789012",
        alamat: "Jl. Anggrek No.3",
        type: "online",
        status: "active",
    },
    {
        id: 4,
        nama: "Toko D",
        no_hp: "084567890123",
        alamat: "Jl. Kenanga No.4",
        type: "offline",
        status: "inactive",
    },
];
</script>
<template>
    <formCreate
        :show="showCreate"
        @close="showCreate = false"
        title="Supplier"
        isSupplier="true"
    ></formCreate>
    <div
        class="p-4 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
    >
        <div
            class="flex flex-col gap-3 mb-3 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="">
                <h2
                    class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                >
                    Kelola Supplier
                </h2>
                <p
                    class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                >
                    Kelola semua supplier anda.
                </p>
            </div>
            <PrimaryButton
                class="text-xs text-center sm:ms-4 sm:text-sm lg:text-base"
                @click="showCreate = true"
                >Tambah Supplier</PrimaryButton
            >
        </div>
        <DataTable
            :data="suppliers"
            :columns="columns"
            @row-click="(row) => console.log('Klik row:', row)"
        >
            <!-- Slot custom status -->
            <template #status="{ row }">
                <span
                    :class="
                        row.status === 'active'
                            ? 'px-2 py-1 rounded bg-green-100 text-green-700'
                            : 'px-2 py-1 rounded bg-red-100 text-red-700'
                    "
                >
                    {{ row.status }}
                </span>
            </template>

            <!-- Slot custom actions -->
            <template #aksi="{ row }">
                <button
                    class="px-2 py-1 text-xs text-white bg-blue-500 rounded hover:bg-blue-600"
                >
                    Edit
                </button>
                <button
                    class="px-2 py-1 ml-2 text-xs text-white bg-red-500 rounded hover:bg-red-600"
                >
                    Delete
                </button>
            </template>
        </DataTable>
    </div>
</template>
