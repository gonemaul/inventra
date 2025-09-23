<template>
    <!-- <Modal :show="show" @close="$emit('close')" max-width="lg"> -->
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
            @click.self="$emit('close')"
        >
            <div
                class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-auto overflow-y-auto max-h-[90vh]"
                @click.stop
            >
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-xl font-semibold text-gray-800">
                        Tambah Produk
                    </h3>
                    <button
                        @click="$emit('close')"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        &times;
                    </button>
                </div>

                <div class="p-4">
                    <div class="mb-6">
                        <h4 class="mb-2 font-semibold text-gray-700">
                            Pilih Produk dari Pesanan
                        </h4>
                        <div class="space-y-2">
                            <div
                                v-for="product in productsFromOrder"
                                :key="product.id"
                                class="flex items-center space-x-2"
                            >
                                <input
                                    type="checkbox"
                                    :id="`prod-${product.id}`"
                                    v-model="selectedProducts"
                                    :value="product"
                                    class="text-blue-500 rounded"
                                />
                                <label
                                    :for="`prod-${product.id}`"
                                    class="flex-grow text-gray-700"
                                    >{{ product.name }} ({{ product.quantity }}
                                    {{ product.unit }})</label
                                >
                            </div>
                            <p
                                v-if="productsFromOrder.length === 0"
                                class="text-sm text-gray-500"
                            >
                                Semua produk pesanan sudah dimasukkan.
                            </p>
                        </div>
                    </div>

                    <div class="pt-6 mt-6 border-t">
                        <h4 class="mb-2 font-semibold text-gray-700">
                            Atau Tambah Produk Baru (Manual)
                        </h4>
                        <div class="space-y-4">
                            <div>
                                <label
                                    for="manual-name"
                                    class="block text-sm font-medium text-gray-700"
                                    >Nama Produk</label
                                >
                                <input
                                    type="text"
                                    id="manual-name"
                                    v-model="manualProduct.name"
                                    class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label
                                    for="manual-quantity"
                                    class="block text-sm font-medium text-gray-700"
                                    >Jumlah</label
                                >
                                <input
                                    type="number"
                                    id="manual-quantity"
                                    v-model.number="manualProduct.quantity"
                                    class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <div>
                                <label
                                    for="manual-unit"
                                    class="block text-sm font-medium text-gray-700"
                                    >Unit</label
                                >
                                <input
                                    type="text"
                                    id="manual-unit"
                                    v-model="manualProduct.unit"
                                    class="block w-full px-3 py-2 mt-1 border border-gray-300 rounded-md shadow-sm"
                                />
                            </div>
                            <button
                                @click="addManualProduct"
                                class="w-full px-4 py-2 text-white transition-colors bg-green-500 rounded-lg hover:bg-green-600"
                            >
                                Tambahkan Manual
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end p-4 space-x-2 border-t">
                    <button
                        @click="saveProducts"
                        :disabled="
                            selectedProducts.length === 0 &&
                            addedManualProducts.length === 0
                        "
                        class="px-4 py-2 text-white transition-colors bg-blue-500 rounded-lg"
                        :class="{
                            'opacity-50 cursor-not-allowed':
                                selectedProducts.length === 0 &&
                                addedManualProducts.length === 0,
                            'hover:bg-blue-600':
                                selectedProducts.length > 0 ||
                                addedManualProducts.length > 0,
                        }"
                    >
                        Selesai & Simpan
                    </button>
                </div>
            </div>
        </div>
    </Transition>
    <!-- </Modal> -->
</template>

<script setup>
import { ref } from "vue";
// import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: Boolean,
    productsFromOrder: Array,
});

const emit = defineEmits(["close", "add-products"]);

const selectedProducts = ref([]);
const manualProduct = ref({
    id: Date.now(), // ID sementara
    name: "",
    quantity: null,
    unit: "",
});
const addedManualProducts = ref([]);

const addManualProduct = () => {
    if (manualProduct.value.name && manualProduct.value.quantity > 0) {
        addedManualProducts.value.push({
            ...manualProduct.value,
            source: "Manual",
        });
        // Reset form
        manualProduct.value = {
            id: Date.now(),
            name: "",
            quantity: null,
            unit: "",
        };
    }
};

const saveProducts = () => {
    const finalProducts = [
        ...selectedProducts.value.map((p) => ({ ...p, source: "Pesanan" })),
        ...addedManualProducts.value,
    ];
    emit("add-products", finalProducts);
    // Reset state modal setelah dikirim
    selectedProducts.value = [];
    addedManualProducts.value = [];
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
