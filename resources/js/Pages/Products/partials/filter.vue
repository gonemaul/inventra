<script setup></script>

<template>
    <div class="w-full p-4 bg-white shadow rounded-xl dark:bg-gray-800">
        <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
            Filter Produk
        </h2>

        <!-- Sort By -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Sortir</label
            >
            <div class="flex gap-2">
                <select
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Pilih...</option>
                    <option value="nama">Nama</option>
                    <option value="ukuran">Ukuran</option>
                    <option value="stok">Stok</option>
                    <option value="harga">Harga</option>
                </select>
                <select
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Asc</option>
                    <option value="tersedia">Desc</option>
                </select>
            </div>
        </div>

        <!-- Status -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Status</label
            >
            <select
                class="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
            >
                <option value="">Semua</option>
                <option value="tersedia">Tersedia</option>
                <option value="habis">Habis</option>
            </select>
        </div>

        <!-- Kategori -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Kategori</label
            >
            <select
                class="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
            >
                <option value="">Semua</option>
                <option value="fashion">Fashion</option>
                <option value="elektronik">Elektronik</option>
                <option value="lainnya">Lainnya</option>
            </select>
        </div>

        <!-- Ukuran -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Ukuran</label
            >
            <div class="flex flex-wrap gap-2">
                <button
                    class="px-3 py-1 text-sm border rounded-lg hover:bg-lime-100 dark:hover:bg-gray-600 dark:text-white"
                >
                    S
                </button>
                <button
                    class="px-3 py-1 text-sm border rounded-lg hover:bg-lime-100 dark:hover:bg-gray-600 dark:text-white"
                >
                    M
                </button>
                <button
                    class="px-3 py-1 text-sm border rounded-lg hover:bg-lime-100 dark:hover:bg-gray-600 dark:text-white"
                >
                    L
                </button>
                <button
                    class="px-3 py-1 text-sm border rounded-lg hover:bg-lime-100 dark:hover:bg-gray-600 dark:text-white"
                >
                    XL
                </button>
            </div>
        </div>

        <!-- Stok -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Stok</label
            >
            <div class="flex gap-2">
                <input
                    type="number"
                    placeholder="Min"
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                />
                <input
                    type="number"
                    placeholder="Max"
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                />
            </div>
        </div>

        <!-- Harga -->
        <div class="mb-4">
            <label class="block mb-1 text-sm font-medium dark:text-gray-300"
                >Harga</label
            >
            <div class="flex gap-2">
                <input
                    type="number"
                    placeholder="Min"
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                />
                <input
                    type="number"
                    placeholder="Max"
                    class="w-1/2 px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:text-white"
                />
            </div>
        </div>

        <!-- Tombol -->
        <div class="flex justify-end gap-2">
            <button
                class="px-4 py-2 text-sm text-white rounded-lg bg-lime-500 hover:bg-lime-600"
            >
                Terapkan
            </button>
            <button
                class="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-white"
            >
                Reset
            </button>
        </div>
    </div>
</template>
