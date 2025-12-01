<template>
    <div class="relative md:w-full">
        <!-- Input -->
        <label class="block mb-1 text-sm font-medium"
            >Cari Produk (Nama atau Kode)</label
        >
        <TextInput
            id="search_product"
            :model-value="modelValue"
            @update:model-value="(value) => $emit('update:modelValue', value)"
            type="text"
            autocomplete="off"
            class="w-full px-2 py-1.5 border border-gray-400 rounded-md focus:border-lime-500 focus:ring-lime-300 dark:bg-gray-600 dark:border-gray-600 dark:text-white"
            placeholder="Ketik min 2 huruf..."
        ></TextInput>
        <div
            v-if="modelValue.length > 1"
            class="absolute z-10 w-full mt-1 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg dark:bg-gray-900 dark:border-gray-700 max-h-60"
        >
            <div v-if="isSearching" class="px-4 py-2 text-gray-500">
                Mencari...
            </div>
            <div
                v-else-if="results.length === 0"
                class="px-4 py-2 text-gray-500"
            >
                Produk tidak ditemukan.
            </div>
            <a
                v-else
                v-for="product in results"
                :key="product.id"
                @click="$emit('select', product)"
                class="block px-4 py-2 text-sm text-gray-700 cursor-pointer dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
                {{ product.name }} ({{ product.code }})
            </a>
        </div>
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
defineProps({
    modelValue: String, // Untuk v-model
    results: Array, // Hasil pencarian
    isSearching: Boolean,
});
defineEmits(["update:modelValue", "select"]);
</script>
