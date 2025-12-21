<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    show: Boolean,
    supplierId: [Number, String], // ID Supplier
});
const emit = defineEmits(["close", "add-items"]);

const recommendations = ref([]);
const loading = ref(false);
const selectedItems = ref([]);

const formatRupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);

// 1. FETCH DATA
async function fetchRecommendations() {
    if (!props.supplierId) return;
    loading.value = true;
    try {
        const res = await axios.get(
            route("purchases.recommendations", props.supplierId)
        );
        recommendations.value = res.data;
        // Default: Centang semua yang kritis
        selectedItems.value = res.data
            .filter((i) => i.is_critical)
            .map((i) => i.product_id);
        // Atau centang semua: selectedItems.value = res.data.map(i => i.product_id);
    } catch (e) {
        console.error("Gagal fetch rekomendasi:", e);
        recommendations.value = [];
    } finally {
        loading.value = false;
    }
}

watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) fetchRecommendations();
        else recommendations.value = [];
    }
);

function handleAdd() {
    const itemsToAdd = recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .map((item) => ({
            ...item,
            quantity: item.quantity,
            purchase_price: item.purchase_price,
        }));

    if (itemsToAdd.length === 0) return;
    emit("add-items", itemsToAdd);
    emit("close");
}

const allChecked = computed({
    get() {
        return (
            selectedItems.value.length === recommendations.value.length &&
            recommendations.value.length > 0
        );
    },
    set(value) {
        selectedItems.value = value
            ? recommendations.value.map((i) => i.product_id)
            : [];
    },
});
</script>

<template>
    <!-- <Modal :show="show" @close="$emit('close')" maxWidth="3xl"> -->
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="💡 Rekomendasi Restok Cerdas"
    >
        <div class="px-6 py-3 bg-white dark:bg-gray-800">
            <div class="items-start mb-4">
                <!-- <div>
                    <h2
                        class="flex items-center gap-2 text-lg font-bold text-gray-900 dark:text-gray-100"
                    >
                        💡 Rekomendasi Restock Cerdas
                    </h2>
                </div> -->
                <p class="mt-1 text-sm text-gray-500">
                    Daftar barang dari Supplier ini yang perlu dibeli
                    berdasarkan
                    <span class="font-bold text-blue-500">Forecasting</span>
                    dan
                    <span class="font-bold text-red-500">Stok Minimum</span>.
                </p>
                <div
                    class="px-3 py-1 text-xs font-bold text-blue-600 border border-blue-100 rounded-lg bg-blue-50"
                >
                    {{ recommendations.length }} Item Ditemukan
                </div>
            </div>

            <div
                v-if="loading"
                class="flex flex-col items-center justify-center py-12 text-gray-500 animate-pulse"
            >
                <div
                    class="w-8 h-8 mx-auto mb-2 border-b-2 rounded-full animate-spin border-lime-500"
                ></div>
                <span>Menganalisa Kebutuhan Stok...</span>
            </div>

            <div
                v-else-if="recommendations.length === 0"
                class="flex flex-col items-center py-12 text-center text-gray-500"
            >
                <div
                    class="flex items-center justify-center w-12 h-12 mb-2 text-green-600 bg-green-100 rounded-full"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M5 13l4 4L19 7"
                        ></path>
                    </svg>
                </div>
                <span class="font-medium text-gray-800 dark:text-white"
                    >Stok Aman!</span
                >
                <span class="mt-1 text-sm"
                    >Tidak ada barang yang perlu di-restock dari supplier
                    ini.</span
                >
            </div>

            <div
                v-else
                class="overflow-auto max-h-[60vh] border rounded-lg dark:border-gray-700"
            >
                <table
                    class="min-w-full text-sm text-left divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead
                        class="sticky top-0 z-10 text-xs text-gray-700 uppercase shadow-sm bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
                    >
                        <tr>
                            <th
                                class="w-10 px-4 py-3 bg-gray-50 dark:bg-gray-700"
                            >
                                <input
                                    type="checkbox"
                                    v-model="allChecked"
                                    class="border-gray-300 rounded cursor-pointer text-lime-600 focus:ring-lime-500"
                                />
                            </th>
                            <th
                                class="w-1/3 px-4 py-3 bg-gray-50 dark:bg-gray-700"
                            >
                                Produk
                            </th>
                            <th
                                class="w-1/2 px-4 py-3 bg-gray-50 dark:bg-gray-700"
                            >
                                Analisa / Alasan
                            </th>
                            <th
                                class="px-4 py-3 text-center w-28 bg-gray-50 dark:bg-gray-700"
                            >
                                Stok
                            </th>
                            <th
                                class="px-4 py-3 text-center w-28 bg-gray-50 dark:bg-gray-700"
                            >
                                Saran Qty
                            </th>
                            <th
                                class="px-4 py-3 text-right bg-gray-50 dark:bg-gray-700"
                            >
                                Estimasi
                            </th>
                        </tr>
                    </thead>
                    <tbody
                        class="bg-white divide-y divide-gray-200 dark:divide-gray-700 dark:bg-gray-800"
                    >
                        <tr
                            v-for="item in recommendations"
                            :key="item.product_id"
                            class="transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            :class="{
                                'bg-red-50/30 dark:bg-red-900/10':
                                    item.is_critical,
                            }"
                            @click="
                                !selectedItems.includes(item.product_id)
                                    ? selectedItems.push(item.product_id)
                                    : selectedItems.splice(
                                          selectedItems.indexOf(
                                              item.product_id
                                          ),
                                          1
                                      )
                            "
                        >
                            <td class="px-4 py-3" @click.stop>
                                <input
                                    type="checkbox"
                                    :value="item.product_id"
                                    v-model="selectedItems"
                                    class="border-gray-300 rounded cursor-pointer text-lime-600 focus:ring-lime-500"
                                />
                            </td>

                            <td class="px-4 py-3 min-w-80%">
                                <div class="flex items-center gap-3">
                                    <!-- <div
                                        class="flex-shrink-0 w-10 h-10 overflow-hidden bg-gray-100 border rounded"
                                    >
                                        <img
                                            :src="item.image_url"
                                            class="object-contain w-full h-full"
                                        />
                                    </div> -->
                                    <p
                                        class="font-bold text-gray-800 dark:text-gray-200 line-clamp-1"
                                        :title="item.name"
                                    >
                                        {{ item.name }}
                                    </p>
                                    <!-- <div> -->
                                    <!-- <div
                                            class="flex items-center gap-1 text-xs text-gray-500"
                                        >
                                            <span
                                                class="px-1 font-mono bg-gray-100 rounded"
                                                >{{ item.code }}</span
                                            >
                                            <span>• {{ item.brand }}</span>
                                        </div> -->
                                    <!-- </div> -->
                                </div>
                            </td>

                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold border shadow-sm"
                                    :class="
                                        item.is_critical
                                            ? 'bg-red-100 text-red-700 border-red-200'
                                            : 'bg-yellow-100 text-yellow-700 border-yellow-200'
                                    "
                                >
                                    <span v-if="item.is_critical" class="mr-1"
                                        >🚨</span
                                    >
                                    {{ item.reason }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <div class="flex flex-col items-center">
                                    <span
                                        class="font-bold"
                                        :class="
                                            item.current_stock <= item.min_stock
                                                ? 'text-red-600'
                                                : 'text-gray-700'
                                        "
                                    >
                                        {{ item.current_stock }}
                                    </span>
                                    <span class="text-[10px] text-gray-400"
                                        >Min: {{ item.min_stock }}</span
                                    >
                                </div>
                            </td>

                            <td class="px-4 py-3 text-center">
                                <span
                                    class="px-3 py-1 font-bold text-blue-700 border border-blue-200 rounded-lg shadow-sm bg-blue-50"
                                >
                                    {{ item.quantity }}
                                </span>
                            </td>

                            <td
                                class="px-4 py-3 font-medium text-right text-gray-600 dark:text-gray-300"
                            >
                                {{
                                    formatRupiah(
                                        item.purchase_price * item.quantity
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex items-center justify-end gap-3 pt-4 mt-6 border-t dark:border-gray-700"
            >
                <p class="mr-auto text-sm text-gray-500">
                    {{ selectedItems.length }} item dipilih
                </p>

                <SecondaryButton @click="$emit('close')">Batal</SecondaryButton>

                <PrimaryButton
                    @click="handleAdd"
                    :disabled="selectedItems.length === 0"
                    class="flex items-center gap-2"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        ></path>
                    </svg>
                    Masukkan ke Keranjang
                </PrimaryButton>
            </div>
        </div>
    </BottomSheet>
    <!-- </Modal> -->
</template>
