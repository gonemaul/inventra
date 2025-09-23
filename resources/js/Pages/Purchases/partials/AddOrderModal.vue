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
                    Pilih Produk dari Pesanan
                </h2>

                <!-- Tombol close di kanan atas -->
                <button
                    @click="$emit('close')"
                    class="absolute right-0 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>
            <div v-if="productsFromOrder.length > 0" class="space-y-4">
                <div
                    v-for="product in productsFromOrder"
                    :key="product.id"
                    class="flex items-start p-4 space-x-4 transition-colors border border-gray-200 rounded-lg bg-gray-50 hover:bg-white"
                >
                    <input
                        type="checkbox"
                        :id="`prod-${product.id}`"
                        v-model="selectedProducts"
                        :value="product"
                        class="w-5 h-5 mt-1 text-blue-500 rounded focus:ring-blue-400"
                    />
                    <label
                        :for="`prod-${product.id}`"
                        class="flex-1 cursor-pointer"
                    >
                        <div class="text-lg font-medium text-gray-900">
                            {{ product.name }}
                        </div>
                        <div class="text-sm text-gray-600">
                            Jumlah:
                            <span class="font-semibold"
                                >{{ product.quantity }} {{ product.unit }}</span
                            >
                            <span v-if="product.size">
                                / Ukuran:
                                <span class="font-semibold">{{
                                    product.size
                                }}</span></span
                            >
                        </div>
                    </label>
                </div>
            </div>
            <div v-else class="py-4 text-center text-gray-500">
                Semua produk pesanan sudah dimasukkan.
            </div>
            <button
                @click="addSelectedProducts"
                :disabled="selectedProducts.length === 0"
                class="w-full px-4 py-2 mt-6 text-white transition-colors bg-blue-500 rounded-lg"
                :class="{
                    'opacity-50 cursor-not-allowed':
                        selectedProducts.length === 0,
                    'hover:bg-blue-600': selectedProducts.length > 0,
                }"
            >
                Tambahkan ke Faktur
            </button>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: Boolean,
    productsFromOrder: Array,
});

const emit = defineEmits(["close", "add-products"]);

const selectedProducts = ref([]);

const addSelectedProducts = () => {
    const productsToAdd = selectedProducts.value.map((p) => ({
        ...p,
        source: "Pesanan",
    }));
    emit("add-products", productsToAdd);
    selectedProducts.value = [];
    emit("close");
};
</script>
