<script setup>
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

// contoh error (nanti bisa diganti dengan useForm dari inertia)
const errors = ref({
    kode: "Kode barang wajib diisi",
    price_buy: "",
    price_sell: "",
    stock: "",
    min_stock: "",
});
</script>

<template>
    <form class="p-6 bg-white rounded-lg shadow dark:bg-gray-200">
        <!-- Upload + Kode Barang -->
        <div class="flex flex-col gap-4 md:flex-row">
            <!-- Upload -->
            <div class="flex flex-col lg:w-2/5">
                <label
                    for="image-upload"
                    class="block text-sm font-medium text-gray-700 dark:text-gray-600"
                >
                    Upload Gambar
                </label>
                <label
                    for="image-upload"
                    class="flex flex-col items-center justify-center w-full h-40 mt-2 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-300 hover:bg-gray-100 dark:hover:bg-gray-400"
                >
                    <div
                        class="flex flex-col items-center justify-center pt-5 pb-6"
                    >
                        <p
                            class="mb-2 text-sm text-gray-500 dark:text-gray-600"
                        >
                            <span class="font-semibold">Klik untuk Upload</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">
                            WEBP, PNG (max. 1MB)
                        </p>
                    </div>
                    <input
                        id="image-upload"
                        type="file"
                        name="image"
                        class="hidden"
                    />
                </label>
            </div>

            <!-- Input kode -->
            <div class="flex flex-col justify-center flex-1 gap-3">
                <div>
                    <TextInput
                        class="w-full max-h-10"
                        id="kode"
                        type="text"
                        name="kode"
                        placeholder="Kode Barang"
                    />
                    <p v-if="errors.kode" class="mt-1 text-sm text-red-500">
                        {{ errors.kode }}
                    </p>
                </div>
                <div>
                    <TextInput
                        class="w-full max-h-10"
                        id="nama"
                        type="text"
                        name="nama"
                        placeholder="Nama Barang"
                    />
                    <p v-if="errors.nama" class="mt-1 text-sm text-red-500">
                        {{ errors.nama }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Selects -->
        <div class="grid grid-cols-1 gap-3 mt-5 md:grid-cols-3">
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400"
                    >Kategori</label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg"
                >
                    <option value="">Pilih Kategori</option>
                    <option value="tersedia">Kategori A</option>
                    <option value="habis">Kategori B</option>
                </select>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-400"
                    >Satuan</label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-600 rounded-lg"
                >
                    <option value="">Pilih Satuan</option>
                    <option value="pcs">Pcs</option>
                    <option value="box">Box</option>
                </select>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-600"
                    >Ukuran</label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg"
                >
                    <option value="">Pilih Ukuran</option>
                    <option value="s">Small</option>
                    <option value="l">Large</option>
                </select>
            </div>
        </div>

        <!-- Deskripsi -->
        <div class="mt-4">
            <textarea
                name="description"
                id="description"
                rows="3"
                placeholder="Deskripsi Barang"
                class="w-full px-3 py-2 border border-gray-400 rounded-lg"
            ></textarea>
            <p v-if="errors.description" class="mt-1 text-sm text-red-500">
                {{ errors.description }}
            </p>
        </div>

        <!-- Harga Beli & Jual -->
        <div class="flex flex-col gap-3 mt-4 md:flex-row">
            <div class="flex-1">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-600"
                    >Harga Beli</label
                >
                <TextInput
                    class="w-full max-h-10"
                    id="price_buy"
                    type="number"
                    name="price_buy"
                    placeholder="Harga Beli"
                />
                <p v-if="errors.price_buy" class="mt-1 text-sm text-red-500">
                    {{ errors.price_buy }}
                </p>
            </div>
            <div class="flex-1">
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-600"
                    >Harga Jual</label
                >
                <TextInput
                    class="w-full max-h-10"
                    id="price_sell"
                    type="number"
                    name="price_sell"
                    placeholder="Harga Jual"
                />
                <p v-if="errors.price_sell" class="mt-1 text-sm text-red-500">
                    {{ errors.price_sell }}
                </p>
            </div>
        </div>

        <!-- Stock -->
        <div class="grid grid-cols-1 gap-3 mt-4 md:grid-cols-3">
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-600"
                    >Stock</label
                >
                <TextInput
                    class="w-full max-h-10"
                    id="stock"
                    type="number"
                    name="stock"
                    placeholder="Stock"
                />
                <p v-if="errors.stock" class="mt-1 text-sm text-red-500">
                    {{ errors.stock }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 border-gray-400 dark:text-gray-600"
                    >Min Stock</label
                >
                <TextInput
                    class="w-full max-h-10"
                    id="min_stock"
                    type="number"
                    name="min_stock"
                    placeholder="Min Stock"
                />
                <p v-if="errors.min_stock" class="mt-1 text-sm text-red-500">
                    {{ errors.min_stock }}
                </p>
            </div>
            <div>
                <label
                    class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-600"
                    >Status</label
                >
                <select
                    class="w-full px-3 py-2 text-sm border border-gray-400 rounded-lg"
                >
                    <option value="">Pilih Status</option>
                    <option value="tersedia">Tersedia</option>
                    <option value="habis">Habis</option>
                </select>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 mt-6">
            <Link :href="route('products.index')">
                <SecondaryButton>Batal</SecondaryButton>
            </Link>
            <PrimaryButton>Simpan</PrimaryButton>
        </div>
    </form>
</template>
