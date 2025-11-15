<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref } from "vue";
import tableCreate from "./partials/tableCreate.vue";
import Recom from "./partials/recom.vue";
import Search from "./partials/search.vue";

const showRecom = ref(false);

// Data rekomendasi produk (biasanya dari API backend)
const rekomendasi = ref([
    {
        id: 1,
        nama: "Oli Motor A",
        satuan: "Botol",
        stok: 6,
        rekom: 24,
        inRAB: false,
    },
    {
        id: 2,
        nama: "Kampas Rem",
        satuan: "Set",
        stok: 3,
        rekom: 10,
        inRAB: true,
    }, // contoh sudah ada di RAB
    {
        id: 3,
        nama: "Filter Oli",
        satuan: "Pcs",
        stok: 8,
        rekom: 15,
        inRAB: false,
    },
]);

// Produk yang dipilih user
const selected = ref([]);

// Event ketika klik "Tambah"
const tambahKeRAB = () => {
    console.log("Produk yang dipilih:", selected.value);
    // bisa emit ke parent atau langsung kirim API
};
</script>
<template>
    <Head title="Rancangan Anggaran Belanja" />

    <AuthenticatedLayout
        headerTitle="Rancangan Anggaran Belanja"
        :showSidebar="false"
    >
        <Recom
            :show="showRecom"
            :items="rekomendasi"
            @close="showRecom = false"
        />
        <div class="w-full min-h-screen space-y-6">
            <!-- Atas -->
            <div class="space-y-6">
                <!-- Grid Utama -->
                <div class="flex flex-col w-full gap-4 md:flex-row">
                    <!-- ========== KIRI ========== -->
                    <div
                        class="flex flex-col gap-4 p-4 bg-gray-200 rounded-lg lg:w-1/2 dark:bg-customBg-tableDark"
                    >
                        <!-- Bagian Atas -->
                        <div class="flex flex-row gap-4">
                            <div class="flex flex-col w-full gap-3">
                                <!-- Atas -->
                                <div
                                    class="flex flex-row justify-between gap-3"
                                >
                                    <div class="w-full lg:w-2/3">
                                        <label
                                            class="block mb-1 text-sm font-medium"
                                            >Supplier</label
                                        >
                                        <select
                                            class="w-full px-2 focus:border-lime-500 focus:ring-lime-500 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 bg-white dark:bg-gray-600 dark:text-white"
                                        >
                                            <option>Supplier</option>
                                            <option>PT. Maju Kanan</option>
                                            <option>CV. Enggal Cepet</option>
                                        </select>
                                    </div>
                                    <div class="hidden mt-2 lg:block">
                                        <label class="block text-sm font-medium"
                                            >Last Update</label
                                        >
                                        <p
                                            class="text-xs text-gray-600 dark:text-gray-400"
                                        >
                                            12 Agustus 2025 | 10:22:30 WIB
                                        </p>
                                    </div>
                                </div>

                                <!-- Bawah -->
                                <div class="flex flex-row gap-4">
                                    <Search />
                                    <div class="w-1/5 lg:w-1/6">
                                        <label
                                            class="block mb-1 text-sm font-medium"
                                            >Qty</label
                                        >
                                        <input
                                            type="number"
                                            placeholder="Qty"
                                            class="w-full px-2 focus:border-lime-500 focus:ring-lime-500 py-1.5 border border-gray-400 rounded-md dark:border-gray-700 dark:bg-gray-600 dark:text-white"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Kanan Foto (Full Height) -->
                            <div
                                class="flex-col items-center justify-center hidden w-1/5 rounded lg:flex"
                            >
                                <img
                                    alt="Produk"
                                    class="object-cover rounded-md"
                                    src="/no-image.png"
                                />
                            </div>
                        </div>

                        <!-- Bagian Bawah (Tombol) -->
                        <div
                            class="flex flex-wrap justify-center gap-2 lg:justify-start"
                        >
                            <Link
                                :href="route('purchases.index')"
                                class="px-3 py-2 bg-gray-400 rounded dark:bg-white dark:text-gray-800 hover:bg-gray-500"
                            >
                                Kembali
                            </Link>
                            <button
                                class="px-3 py-2 text-white bg-blue-500 rounded hover:bg-blue-600"
                            >
                                Tambah
                            </button>
                            <button
                                class="px-3 py-2 bg-red-300 rounded dark:bg-red-600 dark:text-white hover:bg-red-400"
                            >
                                Reset
                            </button>
                            <button
                                class="px-3 py-2 text-white bg-green-500 rounded hover:bg-green-600"
                            >
                                Simpan
                            </button>
                            <button
                                @click="showRecom = true"
                                class="px-3 py-2 text-white bg-yellow-500 rounded hover:bg-yellow-600"
                            >
                                Rekomendasi
                            </button>
                            <button
                                class="px-3 py-2 text-white bg-purple-500 rounded hover:bg-purple-600"
                            >
                                Order
                            </button>
                            <button
                                class="px-3 py-2 text-white bg-pink-400 rounded lg:hidden hover:bg-pink-600"
                            >
                                Foto
                            </button>
                        </div>
                    </div>

                    <!-- ========== TENGAH ========== -->
                    <div
                        class="flex flex-col justify-between p-4 text-sm bg-gray-200 rounded-lg dark:bg-customBg-tableDark"
                    >
                        <!-- Bagian Atas -->
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <strong>Kategori</strong>
                                <strong>Satuan</strong>
                                <strong>Ukuran</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Oil Motor</span>
                                <span>Botol</span>
                                <span>800 ML</span>
                            </div>
                        </div>

                        <!-- Bagian Bawah -->
                        <div
                            class="grid grid-cols-3 gap-4 pt-2 border-t-2 border-gray-400"
                        >
                            <div>
                                <p class="font-semibold">Sisa Stok</p>
                                <p>6</p>
                            </div>
                            <div>
                                <p class="font-semibold">Rekom Restok</p>
                                <p>24</p>
                            </div>
                            <div>
                                <p class="font-semibold">Stok Masuk</p>
                                <p>24</p>
                            </div>
                            <div>
                                <p class="font-semibold">Total Stok</p>
                                <p>30</p>
                            </div>
                            <div>
                                <p class="font-semibold">Harga Terakhir</p>
                                <p>Rp 55.500</p>
                            </div>
                            <div>
                                <p class="font-semibold">Total</p>
                                <p>Rp 1.188.500</p>
                            </div>
                        </div>
                    </div>

                    <!-- ========== KANAN ========== -->
                    <div
                        class="flex flex-col justify-between flex-1 w-full p-4 bg-gray-200 rounded-lg dark:bg-customBg-tableDark"
                    >
                        <div class="flex justify-between">
                            <p class="text-lg font-bold">362 Unit</p>
                            <p class="text-lg font-bold">54 Macam</p>
                        </div>
                        <div class="mt-8 text-left">
                            <p
                                class="text-gray-500 uppercase dark:text-gray-400"
                            >
                                Subtotal
                            </p>
                            <p class="text-3xl font-extrabold">Rp 17.547.000</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Table -->
            <div
                class="p-4 space-y-6 bg-gray-100 border shadow-md rounded-xl dark:bg-customBg-tableDark"
            >
                <tableCreate />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
