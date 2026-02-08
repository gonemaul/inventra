<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from "vue";
import debounce from "lodash/debounce";
import axios from "axios";

const props = defineProps({
    modelValue: [String, Number], // Product ID
    initialProduct: Object, // { id, name, code } for initial display
    placeholder: {
        type: String,
        default: "Cari Produk...",
    },
    error: String,
});

const emit = defineEmits(["update:modelValue", "change"]);

const searchQuery = ref("");
const results = ref([]);
const isLoading = ref(false);
const showDropdown = ref(false);
const inputRef = ref(null);
const dropdownRef = ref(null);

// Initialize Value
const initialize = () => {
    if (props.initialProduct) {
        searchQuery.value = `${props.initialProduct.code} - ${props.initialProduct.name}`;
    }
};

watch(() => props.initialProduct, initialize);
onMounted(initialize);

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
    if (
        dropdownRef.value &&
        !dropdownRef.value.contains(event.target) &&
        inputRef.value &&
        !inputRef.value.contains(event.target)
    ) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});

// Search Function
const performSearch = debounce(async (query) => {
    if (!query) {
        results.value = [];
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(route("products.searchProducts"), {
            params: { q: query, limit: 10 },
        });
        results.value = response.data;
        showDropdown.value = true;
    } catch (error) {
        console.error("Search error:", error);
        results.value = [];
    } finally {
        isLoading.value = false;
    }
}, 300);

const onInput = () => {
    if (searchQuery.value.length < 2) {
        results.value = [];
        showDropdown.value = false;
        return;
    }
    performSearch(searchQuery.value);
};

const selectProduct = (product) => {
    searchQuery.value = `${product.code} - ${product.name}`;
    emit("update:modelValue", product.id);
    emit("change", product);
    showDropdown.value = false;
    results.value = [];
};

const clearSelection = () => {
    searchQuery.value = "";
    emit("update:modelValue", null);
    emit("change", null);
    showDropdown.value = false;
    results.value = [];
    // Focus back to input
    if(inputRef.value) inputRef.value.focus();
};
</script>

<template>
    <div class="relative w-full">
        <div class="relative">
            <input
                ref="inputRef"
                type="text"
                v-model="searchQuery"
                @input="onInput"
                @focus="onInput"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                :class="{ 'border-red-500': error }"
                :placeholder="placeholder"
                autocomplete="off"
            />
            
            <!-- Loading Indicator -->
            <div
                v-if="isLoading"
                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"
            >
                <svg
                    class="w-5 h-5 text-gray-400 animate-spin"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>
            </div>

            <!-- Clear Button -->
             <div
                v-else-if="searchQuery && !isLoading"
                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                @click="clearSelection"
            >
                <svg class="w-5 h-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>

        <!-- Dropdown Results -->
        <div
            ref="dropdownRef"
            v-if="showDropdown && results.length > 0"
            class="absolute z-50 w-full mt-1 overflow-auto bg-white border border-gray-200 rounded-md shadow-lg max-h-60 dark:bg-gray-800 dark:border-gray-700"
        >
            <ul class="py-1">
                <li
                    v-for="product in results"
                    :key="product.id"
                    @click="selectProduct(product)"
                    class="px-4 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-gray-700 dark:text-gray-200"
                >
                    <div class="flex flex-col">
                        <span class="font-bold text-sm">{{ product.name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ product.code }} 
                            <span v-if="product.unit">• {{ product.unit.name }}</span>
                             <span v-if="product.category">• {{ product.category.name }}</span>
                        </span>
                    </div>
                </li>
            </ul>
        </div>
        
        <div 
             v-if="showDropdown && results.length === 0 && !isLoading && searchQuery.length >= 2"
             class="absolute z-50 w-full mt-1 p-4 text-center text-sm text-gray-500 bg-white border border-gray-200 rounded-md shadow-lg dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400"
        >
            Produk tidak ditemukan.
        </div>
    </div>
</template>
