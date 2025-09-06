<template>
    <div class="relative w-64">
        <!-- Input -->
        <label class="block mb-1 text-sm font-medium">Nama</label>
        <input
            type="text"
            v-model="query"
            @input="searchProducts"
            placeholder="Ketik nama produk..."
            autocomplete="off"
            class="w-full px-2 py-1.5 border border-gray-400 rounded-md focus:border-lime-500 focus:ring-lime-300 dark:bg-gray-600 dark:border-gray-600 dark:text-white"
        />

        <!-- Suggestion List -->
        <ul
            v-if="suggestions.length > 0"
            class="absolute left-0 right-0 z-10 mt-1 overflow-y-auto bg-white border rounded-lg shadow-lg dark:bg-gray-700 dark:border-gray-600 max-h-48"
        >
            <li
                v-for="(product, index) in suggestions"
                :key="index"
                @click="selectProduct(product)"
                class="px-4 py-2 cursor-pointer hover:bg-blue-100 dark:hover:bg-gray-600 dark:text-white"
            >
                {{ product.name }}
            </li>
        </ul>

        <!-- Jika tidak ada hasil -->
        <p
            v-else-if="query.length >= 3 && !isLoading && !hasSelected"
            class="absolute left-0 right-0 p-2 mt-1 text-sm text-center text-gray-500 bg-white border rounded-md shadow-lg border-lime-400 dark:bg-gray-700 dark:text-gray-400"
        >
            Produk tidak ditemukan
        </p>
    </div>
</template>

<script setup>
import { ref } from "vue";

const query = ref("");
const suggestions = ref([]);
const isLoading = ref(false);
const hasSelected = ref(false);

// contoh data produk
const allProducts = [
    { id: 1, name: "Beras 5kg" },
    { id: 2, name: "Gula Pasir 1kg" },
    { id: 3, name: "Minyak Goreng 2L" },
    { id: 4, name: "Tepung Terigu 1kg" },
    { id: 5, name: "Susu UHT 1L" },
    { id: 6, name: "Minyak Zaitun 500ml" },
    { id: 7, name: "Kopi Bubuk 200g" },
    { id: 8, name: "Teh Celup 50pcs" },
    { id: 9, name: "Mie Instan 5pcs" },
    { id: 10, name: "Saus Tomat 300g" },
];

function searchProducts() {
    hasSelected.value = false; // reset saat user ketik
    if (query.value.length < 3) {
        suggestions.value = [];
        return;
    }

    isLoading.value = true;
    setTimeout(() => {
        suggestions.value = allProducts.filter((p) =>
            p.name.toLowerCase().includes(query.value.toLowerCase())
        );
        isLoading.value = false;
    }, 200);
}

function selectProduct(product) {
    query.value = product.name;
    suggestions.value = []; // tutup list setelah dipilih
    hasSelected.value = true;
}
</script>
