<template>
    <Modal :show="show" @close="$emit('close')" max-width="md">
        <div
            class="w-full max-w-md p-6 bg-white shadow-lg rounded-xl dark:bg-gray-800"
        >
            <!-- Header -->
            <div
                class="relative flex items-center justify-center pb-3 border-b"
            >
                <!-- Judul selalu center -->
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Tambah Produk Dari Data Master
                </h2>

                <!-- Tombol close di kanan atas -->
                <button
                    @click="$emit('close')"
                    class="absolute right-0 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>
            <div class="mb-4">
                <label
                    for="search"
                    class="block text-sm font-medium text-gray-700"
                    >Cari Produk</label
                >
                <input
                    type="text"
                    v-model="searchQuery"
                    @input="searchProduct"
                    placeholder="Cari nama produk..."
                    class="block w-full px-3 py-2 mt-1 border rounded-md focus:ring focus:ring-blue-200"
                />
            </div>

            <div
                v-if="manualSearchResults.length > 0"
                class="p-2 mb-4 overflow-y-auto border rounded-lg max-h-64"
            >
                <div
                    v-for="product in manualSearchResults"
                    :key="product.id"
                    class="flex items-center justify-between py-3 border-b last:border-0"
                >
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">
                            {{ product.name }}
                        </div>
                        <div class="text-sm text-gray-600">
                            <span v-if="product.size">{{ product.size }}</span>
                            <span v-if="product.unit">
                                / {{ product.unit }}</span
                            >
                        </div>
                    </div>
                    <button
                        @click="addManualProduct(product)"
                        class="flex-shrink-0 px-3 py-1 text-sm text-white transition-colors bg-green-500 rounded hover:bg-green-600"
                    >
                        Tambah
                    </button>
                </div>
            </div>

            <div
                v-if="
                    searchQuery.length >= 3 && manualSearchResults.length === 0
                "
                class="py-4 text-sm text-center text-red-500"
            >
                Produk tidak ditemukan. Coba kata kunci lain.
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(["close", "add-products"]);

const searchQuery = ref("");
const manualSearchResults = ref([]);

// Data dummy master produk (ini seharusnya diambil dari API)
const masterProducts = [
    {
        id: 101,
        name: "Kabel HDMI",
        category: "Aksesoris",
        unit: "Unit",
        size: "2 Meter",
    },
    {
        id: 102,
        name: "Keyboard Mekanik",
        category: "Aksesoris",
        unit: "Pcs",
        size: "Full Size",
    },
];

const searchProduct = () => {
    if (searchQuery.value.length >= 3) {
        const results = masterProducts.filter((p) =>
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
        manualSearchResults.value = results;
    } else {
        manualSearchResults.value = [];
    }
};

const addManualProduct = (product) => {
    const newProduct = {
        ...product,
        quantity: 1, // Anda bisa tambahkan input jumlah jika perlu
        source: "Manual",
    };
    emit("add-products", [newProduct]);
    searchQuery.value = "";
    manualSearchResults.value = [];
    emit("close");
};
</script>
