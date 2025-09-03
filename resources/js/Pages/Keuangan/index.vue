<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import { computed } from "vue";
import DataTable from "@/Components/DataTable.vue";

// props data pembayaran
const props = defineProps({
    total: {
        type: Number,
        required: true,
    },
    terbayarkan: {
        type: Number,
        required: true,
    },
    kekurangan: {
        type: Number,
        required: true,
    },
});

// format ke rupiah
const formatRupiah = (value) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const params = ref({
    search: "",
    kategori: "",
});

const columns = [
    { key: "no_nota", label: "Kode", sortable: true, width: "120px" },
    { key: "tanggal", label: "Tanggal", sortable: true, width: "200px" },
    { key: "supplier", label: "Supplier", sortable: true, width: "200px" },
    {
        key: "total",
        label: "Total",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "terbayarkan",
        label: "Terbayarkan",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "kekurangan",
        label: "Kekurangan",
        sortable: true,
        width: "200px",
        rupiah: true,
    },
    {
        key: "progress",
        label: "Progress",
        sortable: true,
        width: "200px",
        slot: "progress",
    },
    { key: "aksi", label: "Aksi", width: "120px", slot: "aksi" },
];
const allData = [
    {
        id: 1,
        no_nota: "INV-001",
        tanggal: "2025-08-01",
        supplier: "PT Sumber Makmur",
        total: 2000000,
        terbayarkan: 1500000,
        kekurangan: 500000,
    },
    {
        id: 2,
        no_nota: "INV-002",
        tanggal: "2025-08-03",
        supplier: "UD Berkah Jaya",
        total: 3000000,
        terbayarkan: 3000000,
        kekurangan: 0,
    },
    {
        id: 3,
        no_nota: "INV-003",
        tanggal: "2025-08-05",
        supplier: "CV Sinar Abadi",
        total: 1500000,
        terbayarkan: 500000,
        kekurangan: 1000000,
    },
];
</script>

<template>
    <Head title="Keuangan" />

    <AuthenticatedLayout headerTitle="Keuangan">
        <div class="w-full min-h-screen px-4 space-y-6">
            <!-- Atas -->
            <div
                class="p-4 space-y-6 bg-gray-100 shadow-md dark:bg-customBg-tableDark rounded-xl"
            >
                <!-- Ringkasan Atas -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div
                        class="grid items-center h-32 grid-rows-2 p-3 text-center bg-white border shadow-md dark:border-gray-600 lg:h-40 dark:bg-gray-800 rounded-xl"
                    >
                        <span
                            class="text-lg font-medium text-gray-700 lg:text-2xl dark:text-gray-200"
                            >Total Tagihan</span
                        >
                        <span
                            class="text-2xl font-bold text-red-500 lg:text-3xl"
                            >Rp 6.500.000</span
                        >
                    </div>
                    <div
                        class="grid items-center h-32 grid-rows-2 p-3 text-center bg-white border shadow-md dark:border-gray-600 lg:h-40 dark:bg-gray-800 rounded-xl"
                    >
                        <span
                            class="text-lg font-medium text-gray-700 lg:text-2xl dark:text-gray-200"
                            >Total Terbayar</span
                        >
                        <span
                            class="text-2xl font-bold text-green-500 lg:text-3xl"
                            >Rp 4.500.000</span
                        >
                    </div>
                </div>

                <!-- Detail Ringkasan -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div
                        class="grid grid-cols-1 gap-3 p-4 text-sm bg-white rounded-md shadow dark:bg-gray-800 lg:text-base lg:grid-cols-2"
                    >
                        <div class="flex justify-between">
                            <span>Nota Lunas</span>
                            <span class="font-semibold">5 Nota</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Nota Belum Lunas</span>
                            <span class="font-semibold">3 Nota</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Nota Jatuh Tempo</span>
                            <span class="font-semibold">2 Nota</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Total Transaksi</span>
                            <span class="font-semibold">8 Nota</span>
                        </div>
                    </div>
                    <div
                        class="flex flex-col justify-center p-4 space-y-2 bg-white rounded-md shadow dark:bg-gray-800"
                    >
                        <div
                            class="flex flex-col text-sm text-gray-500 dark:text-gray-300 lg:flex-row lg:items-center lg:gap-2"
                        >
                            <span
                                class="text-sm text-gray-500 dark:text-gray-300"
                                >Pembayaran terakhir:</span
                            >
                            <span><b>20 Agustus 2025</b> | 10:30:22 WIB</span>
                        </div>
                        <span class="text-sm text-red-500"
                            >⚠ 5 Nota dari 2 Transaksi mendekati jatuh
                            tempo.</span
                        >
                    </div>
                </div>
            </div>

            <div
                class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
            >
                <!-- Title & Description -->
                <div>
                    <h2
                        class="text-base font-semibold text-gray-900 dark:text-white sm:text-lg lg:text-xl"
                    >
                        Tagihan Pembelian
                    </h2>
                    <p
                        class="mt-1 text-xs text-gray-600 dark:text-gray-300 sm:text-sm lg:text-base"
                    >
                        Kelola semua invoice setiap transaksi.
                    </p>
                </div>
                <!-- Data Table -->
                <DataTable :columns="columns" :data="allData" :params="params">
                    <template #progress="{ row }">
                        <div class="" v-if="row.terbayarkan < row.total">
                            <span class="text-xs"
                                >Progress:
                                {{
                                    Math.min(
                                        100,
                                        Math.round(
                                            (row.terbayarkan / row.total) * 100
                                        )
                                    )
                                }}%</span
                            >
                            <div class="w-full h-2 bg-gray-300 rounded-full">
                                <div
                                    class="h-2 bg-green-500 rounded-full"
                                    :style="{
                                        width:
                                            Math.min(
                                                100,
                                                Math.round(
                                                    (row.terbayarkan /
                                                        row.total) *
                                                        100
                                                )
                                            ) + '%',
                                    }"
                                ></div>
                            </div>
                        </div>
                        <!-- Kalau sudah lunas -->
                        <div v-else class="flex items-center gap-2">
                            <span
                                class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-md dark:bg-green-700 dark:text-white"
                            >
                                Lunas
                            </span>
                        </div>
                    </template>
                    <template #aksi="{ row }">
                        <div
                            class="inline-flex items-center justify-end w-full gap-2"
                        >
                            <!-- Button Bayar (muncul kalau belum lunas) -->
                            <Link
                                href="/payments-detail"
                                v-if="row.terbayarkan < row.total"
                                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                <svg
                                    class="w-5 h-5 text-gray-700 dark:text-gray-300"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M12.875 3.29134V6.45801H11.625V3.29134C11.625 3.06634 11.425 2.95801 11.2917 2.95801C11.25 2.95801 11.2084 2.96634 11.1667 2.98301L4.55837 5.47467C4.11671 5.64134 3.83337 6.05801 3.83337 6.53301V7.09134C3.07504 7.65801 2.58337 8.56634 2.58337 9.59134V6.53301C2.58337 5.54134 3.19171 4.65801 4.11671 4.30801L10.7334 1.80801C10.9167 1.74134 11.1084 1.70801 11.2917 1.70801C12.125 1.70801 12.875 2.38301 12.875 3.29134Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        d="M18.4167 12.0837V12.917C18.4167 13.142 18.2417 13.3253 18.0083 13.3337H16.7917C16.35 13.3337 15.95 13.0087 15.9167 12.5753C15.8917 12.317 15.9917 12.0753 16.1583 11.9087C16.3083 11.7503 16.5167 11.667 16.7417 11.667H18C18.2417 11.6753 18.4167 11.8587 18.4167 12.0837Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        d="M16.7334 10.7913H17.5834C18.0417 10.7913 18.4167 10.4163 18.4167 9.95801V9.59134C18.4167 7.86634 17.0084 6.45801 15.2834 6.45801H5.71671C5.00837 6.45801 4.35837 6.69134 3.83337 7.09134C3.07504 7.65801 2.58337 8.56634 2.58337 9.59134V15.1997C2.58337 16.9247 3.99171 18.333 5.71671 18.333H15.2834C17.0084 18.333 18.4167 16.9247 18.4167 15.1997V15.0413C18.4167 14.583 18.0417 14.208 17.5834 14.208H16.8584C16.0584 14.208 15.2917 13.7163 15.0834 12.9413C14.9084 12.308 15.1167 11.6997 15.5334 11.2913C15.8417 10.9747 16.2667 10.7913 16.7334 10.7913ZM12.1667 10.6247H6.33337C5.99171 10.6247 5.70837 10.3413 5.70837 9.99967C5.70837 9.65801 5.99171 9.37467 6.33337 9.37467H12.1667C12.5084 9.37467 12.7917 9.65801 12.7917 9.99967C12.7917 10.3413 12.5084 10.6247 12.1667 10.6247Z"
                                        fill="currentColor"
                                    />
                                </svg>
                            </Link>

                            <!-- Button Lihat -->
                            <Link
                                class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-600"
                            >
                                <svg
                                    class="w-5 h-5 text-lime-600 dark:text-lime-400"
                                    viewBox="0 0 21 20"
                                    fill="currentColor"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        d="M10 8.12467C8.96451 8.12467 8.12504 8.96414 8.12504 9.99967C8.12504 11.0352 8.96451 11.8747 10 11.8747C11.0356 11.8747 11.875 11.0352 11.875 9.99967C11.875 8.96414 11.0356 8.12467 10 8.12467Z"
                                        fill="currentColor"
                                    />
                                    <path
                                        fill-rule="evenodd"
                                        clip-rule="evenodd"
                                        d="M10 4.58301C7.81856 4.58301 5.85671 5.45916 4.44305 6.54414C3.7353 7.08733 3.15457 7.69062 2.74712 8.27774C2.34742 8.85368 2.08337 9.45929 2.08337 9.99967C2.08337 10.5401 2.34742 11.1457 2.74712 11.7216C3.15457 12.3087 3.7353 12.912 4.44305 13.4552C5.85671 14.5402 7.81856 15.4163 10 15.4163C12.1815 15.4163 14.1434 14.5402 15.557 13.4552C16.2648 12.912 16.8455 12.3087 17.253 11.7216C17.6527 11.1457 17.9167 10.5401 17.9167 9.99967C17.9167 9.45929 17.6527 8.85368 17.253 8.27774C16.8455 7.69062 16.2648 7.08733 15.557 6.54414C14.1434 5.45916 12.1815 4.58301 10 4.58301ZM6.87504 9.99967C6.87504 8.27378 8.27415 6.87467 10 6.87467C11.7259 6.87467 13.125 8.27378 13.125 9.99967C13.125 11.7256 11.7259 13.1247 10 13.1247C8.27415 13.1247 6.87504 11.7256 6.87504 9.99967Z"
                                        fill="currentColor"
                                    />
                                </svg>
                            </Link>
                        </div>
                    </template>
                </DataTable>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
