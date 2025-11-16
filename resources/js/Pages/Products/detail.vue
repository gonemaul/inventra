<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import CardDetail from "./partials/card-detail.vue";
import tabsDetail from "./partials/tabs-detail.vue";

const props = defineProps({
    product: Object, // Bisa null (create) atau berisi data (edit)
});
const tabs = [
    { key: "ringkasan", label: "Ringkasan Penjualan" },
    { key: "analisis", label: "Analisis Pergerakan" },
    { key: "forecast", label: "Forecasting Restock" },
    { key: "riwayat", label: "Riwayat Stock" },
];
</script>
<template>
    <Head title="Detail Barang" />

    <AuthenticatedLayout headerTitle="Detail Barang">
        <div class="w-full min-h-screen space-y-5">
            <CardDetail :data="product" />
            <tabsDetail :tabs="tabs" defaultTab="ringkasan">
                <!-- Ringkasan Penjualan -->
                <template #ringkasan>
                    <div
                        class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Ringkasan Penjualan
                        </h2>
                        <p
                            class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            Menampilkan total penjualan harian, mingguan, dan
                            bulanan.
                        </p>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div
                                class="p-3 bg-gray-100 rounded dark:bg-gray-700"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Hari Ini
                                </p>
                                <p
                                    class="text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    Rp 2.300.000
                                </p>
                            </div>
                            <div
                                class="p-3 bg-gray-100 rounded dark:bg-gray-700"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Minggu Ini
                                </p>
                                <p
                                    class="text-lg font-bold text-gray-900 dark:text-white"
                                >
                                    Rp 12.500.000
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Analisis Pergerakan -->
                <template #analisis>
                    <div
                        class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Analisis Pergerakan
                        </h2>
                        <p
                            class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            Grafik dan pola pergerakan barang berdasarkan waktu.
                        </p>
                        <div
                            class="flex items-center justify-center h-40 mt-3 bg-gray-100 rounded dark:bg-gray-700"
                        >
                            📊 Grafik placeholder
                        </div>
                    </div>
                </template>

                <!-- Forecasting Restock -->
                <template #forecast>
                    <div
                        class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Forecasting Restock
                        </h2>
                        <p
                            class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            Prediksi kebutuhan restock barang untuk periode
                            mendatang.
                        </p>
                        <div class="grid grid-cols-2 gap-4 mt-3">
                            <div
                                class="p-3 bg-gray-100 rounded dark:bg-gray-700"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Produk A
                                </p>
                                <p
                                    class="text-lg font-bold text-red-600 dark:text-red-400"
                                >
                                    Restock 20 Unit
                                </p>
                            </div>
                            <div
                                class="p-3 bg-gray-100 rounded dark:bg-gray-700"
                            >
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-400"
                                >
                                    Produk B
                                </p>
                                <p
                                    class="text-lg font-bold text-green-600 dark:text-green-400"
                                >
                                    Aman
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Riwayat Stock -->
                <template #riwayat>
                    <div
                        class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"
                    >
                        <h2
                            class="text-lg font-semibold text-gray-900 dark:text-white"
                        >
                            Riwayat Stock
                        </h2>
                        <p
                            class="mt-2 text-sm text-gray-600 dark:text-gray-300"
                        >
                            Daftar perubahan stok masuk/keluar terbaru.
                        </p>
                        <ul
                            class="mt-3 divide-y divide-gray-200 dark:divide-gray-700"
                        >
                            <li
                                class="flex justify-between py-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <span>+ 50 Unit Produk A</span>
                                <span class="text-gray-500 dark:text-gray-400"
                                    >20 Agustus</span
                                >
                            </li>
                            <li
                                class="flex justify-between py-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <span>- 10 Unit Produk B</span>
                                <span class="text-gray-500 dark:text-gray-400"
                                    >19 Agustus</span
                                >
                            </li>
                        </ul>
                    </div>
                </template>
            </tabsDetail>
        </div>
    </AuthenticatedLayout>
</template>
