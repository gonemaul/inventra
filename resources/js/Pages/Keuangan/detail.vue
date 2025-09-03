<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DataTable from "@/Components/DataTable.vue";
import formBayar from "./partials/formBayar.vue";
import { ref } from "vue";

const showBayar = ref(false);

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    { key: "tanggal", label: "Tanggal", sortable: true, width: "120px" },
    {
        key: "nominal",
        label: "Nominal",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "kekurangan",
        label: "Kekurangan",
        sortable: true,
        width: "120px",
        rupiah: true,
    },
    { key: "bukti", label: "Bukti", width: "120px", slot: "bukti" },
];

const allData = [
    {
        id: 1,
        tanggal: "2024-12-21",
        nominal: 2000000,
        kekurangan: 1350000,
    },
    {
        id: 2,
        tanggal: "2024-12-25",
        nominal: 5000000,
        kekurangan: 8500000,
    },
];
</script>

<template>
    <Head title="Detail Nota" />

    <AuthenticatedLayout headerTitle="Detail Nota">
        <formBayar :show="showBayar" @close="showBayar = false"></formBayar>
        <div class="w-full min-h-screen p-4 space-y-6">
            <!-- Informasi Utama Nota -->
            <div
                class="flex flex-col gap-5 p-4 bg-gray-100 border rounded-md shadow-md dark:bg-customBg-tableDark lg:flex-row lg:items-center"
            >
                <!-- Nota Gambar -->
                <div class="flex justify-center p-2 lg:w-1/6 lg:justify-start">
                    <img
                        alt="Nota"
                        class="object-cover w-full rounded-md"
                        src="no-image.png"
                    />
                </div>
                <!-- Container Tombol -->
                <div class="flex justify-center gap-4 lg:flex-col lg:w-1/12">
                    <PrimaryButton
                        @click="showBayar = true"
                        class="justify-center flex-1 gap-2 text-white !bg-green-600 !hover:bg-green-700"
                    >
                        Bayar
                    </PrimaryButton>
                    <PrimaryButton
                        class="justify-center flex-1 text-white !bg-blue-600 !hover:bg-blue-700"
                    >
                        Detail
                    </PrimaryButton>
                    <PrimaryButton
                        class="justify-center flex-1 text-white !bg-gray-500 !hover:bg-gray-600"
                    >
                        Kembali
                    </PrimaryButton>
                </div>

                <!-- Detail Nota -->
                <div
                    class="flex flex-col items-center flex-1 gap-4 p-5 lg:flex-row lg:justify-between lg:items-start"
                >
                    <!-- Detail Nota -->
                    <div class="grid w-full grid-rows-5 gap-2 text-sm lg:w-1/3">
                        <div class="flex justify-between">
                            <span>No Nota</span>
                            <span class="font-semibold">83749734873</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tanggal Terbit</span>
                            <span>20 Maret 2024</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Bayar</span>
                            <span class="font-semibold">Rp 20.000.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Terbayarkan</span>
                            <span class="font-semibold text-green-600"
                                >Rp 15.000.000</span
                            >
                        </div>
                        <div class="flex justify-between">
                            <span>Kekurangan</span>
                            <span class="font-semibold text-red-600"
                                >Rp 5.000.000</span
                            >
                        </div>
                    </div>

                    <!-- Progress -->
                    <div
                        class="flex flex-col items-center justify-center w-full gap-2 lg:w-40"
                    >
                        <span class="text-xs">Pembayaran Terakhir</span>
                        <span class="text-sm font-medium"
                            >11 Desember 2024</span
                        >
                        <div
                            class="w-full h-2 bg-gray-300 rounded-full dark:bg-gray-600"
                        >
                            <div
                                class="h-2 bg-green-500 rounded-full"
                                style="width: 30%"
                            ></div>
                        </div>
                        <span class="text-xs">Progress: 30%</span>
                    </div>

                    <!-- Supplier -->
                    <div
                        class="flex flex-col items-center justify-center p-3 text-sm rounded-md shadow bg-lime-200 dark:bg-lime-700 lg:w-48"
                    >
                        <span class="font-bold">PT. MAJU MUNDUR</span>
                        <span>0827 8782 78</span>
                        <span>Offline</span>
                        <span>Jawa Timur, Indonesia</span>
                    </div>
                </div>
            </div>

            <div
                class="p-4 space-y-3 border rounded-lg shadow-md bg-customBg-tableLight dark:bg-customBg-tableDark"
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
                <!-- Riwayat Pembayaran -->
                <DataTable :columns="columns" :data="allData" :params="params">
                    <template #bukti="{ row }">
                        <button>
                            <svg
                                class="w-5 h-5 text-blue-700 hover:text-blue-800 dark:text-blue-500 dark:hover:text-blue-600"
                                viewBox="0 0 24 24"
                                fill="currentColor"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M7 2H6C3 2 2 3.79 2 6V7V21C2 21.83 2.94 22.3 3.6 21.8L5.31 20.52C5.71 20.22 6.27 20.26 6.63 20.62L8.29 22.29C8.68 22.68 9.32 22.68 9.71 22.29L11.39 20.61C11.74 20.26 12.3 20.22 12.69 20.52L14.4 21.8C15.06 22.29 16 21.82 16 21V4C16 2.9 16.9 2 18 2H7ZM5.97 14.01C5.42 14.01 4.97 13.56 4.97 13.01C4.97 12.46 5.42 12.01 5.97 12.01C6.52 12.01 6.97 12.46 6.97 13.01C6.97 13.56 6.52 14.01 5.97 14.01ZM5.97 10.01C5.42 10.01 4.97 9.56 4.97 9.01C4.97 8.46 5.42 8.01 5.97 8.01C6.52 8.01 6.97 8.46 6.97 9.01C6.97 9.56 6.52 10.01 5.97 10.01ZM12 13.76H9C8.59 13.76 8.25 13.42 8.25 13.01C8.25 12.6 8.59 12.26 9 12.26H12C12.41 12.26 12.75 12.6 12.75 13.01C12.75 13.42 12.41 13.76 12 13.76ZM12 9.76H9C8.59 9.76 8.25 9.42 8.25 9.01C8.25 8.6 8.59 8.26 9 8.26H12C12.41 8.26 12.75 8.6 12.75 9.01C12.75 9.42 12.41 9.76 12 9.76Z"
                                    fill="currentColor"
                                />
                                <path
                                    d="M18.01 2V3.5C18.67 3.5 19.3 3.77 19.76 4.22C20.24 4.71 20.5 5.34 20.5 6V8.42C20.5 9.16 20.17 9.5 19.42 9.5H17.5V4.01C17.5 3.73 17.73 3.5 18.01 3.5V2ZM18.01 2C16.9 2 16 2.9 16 4.01V11H19.42C21 11 22 10 22 8.42V6C22 4.9 21.55 3.9 20.83 3.17C20.1 2.45 19.11 2.01 18.01 2C18.02 2 18.01 2 18.01 2Z"
                                    fill="currentColor"
                                />
                            </svg>
                        </button>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
