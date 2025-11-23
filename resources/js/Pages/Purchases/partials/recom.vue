<script setup>
import { ref, watch, computed } from "vue";
import axios from "axios";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
const props = defineProps({
    show: Boolean,
    supplierId: [Number, String], // ID Supplier dari form header (untuk filter BE)
});
const emit = defineEmits(["close", "add-items"]);

// --- STATE INTERNAL MODAL ---
const recommendations = ref([]); // Data yang diterima dari BE
const loading = ref(false);
const selectedItems = ref([]); // ID produk yang dicentang user

// Helper Rupiah
const formatRupiah = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n);

// 1. FUNGSI FETCH DATA (Dipanggil saat modal show)
async function fetchRecommendations() {
    if (!props.supplierId) return; // Jangan fetch jika supplier belum dipilih

    loading.value = true;
    try {
        // Panggil API Backend (yang sudah kita buat di PurchaseController)
        const res = await axios.get(route("purchases.recommendations"), {
            params: { supplier_id: props.supplierId },
        });

        recommendations.value = res.data;

        // Atur default: Auto-check semua rekomendasi yang muncul
        selectedItems.value = res.data.map((i) => i.product_id);
    } catch (e) {
        console.error("Gagal fetch rekomendasi:", e);
        recommendations.value = [];
    } finally {
        loading.value = false;
    }
}

// 2. WATCHER (Pemicu Otomatis)
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            fetchRecommendations();
        } else {
            // Bersihkan state saat modal tertutup
            recommendations.value = [];
        }
    }
);

// 3. FUNGSI TAMBAH MASSAL (Handler Tombol 'Ambil Terpilih')
function handleAdd() {
    // Filter hanya item yang ID-nya ada di array 'selectedItems'
    const itemsToAdd = recommendations.value
        .filter((item) => selectedItems.value.includes(item.product_id))
        .map((item) => ({
            // Kirim data lengkap yang dibutuhkan oleh usePurchaseCart.addMultipleItems
            ...item,
            quantity: item.quantity, // Quantity ini adalah 'Saran Qty' dari BE
            purchase_price: item.purchase_price,
        }));

    if (itemsToAdd.length === 0) return;

    emit("add-items", itemsToAdd); // Kirim array item ke parent
    emit("close");
}

// 4. Aksi Cepat (Check/Uncheck All)
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
    <Modal :show="show" @close="$emit('close')">
        <!-- <div
            class="w-full p-6 bg-white shadow-lg maxs-w-md rounded-xl dark:bg-gray-800"
        >
            <div
                class="relative flex items-center justify-center pb-3 border-b"
            >
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Rekomendasi Pembelian
                </h2>
                <button
                    @click="$emit('close')"
                    class="absolute right-0 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    ✕
                </button>
            </div>

            <div class="overflow-x-auto">
                <table
                    class="w-full text-sm border border-gray-300 rounded-lg dark:border-gray-600"
                >
                    <thead class="bg-gray-100 dark:bg-gray-700 dark:text-white">
                        <tr>
                            <th class="px-3 py-2 text-left">
                                <input type="checkbox" @change="toggleAll" />
                            </th>
                            <th class="px-3 py-2 text-left">Produk</th>
                            <th class="px-3 py-2 text-center">Satuan</th>
                            <th class="px-3 py-2 text-center">Stok</th>
                            <th class="px-3 py-2 text-center">Rekom</th>
                            <th class="px-3 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(item, index) in items"
                            :key="index"
                            :class="
                                item.inRAB
                                    ? 'bg-yellow-100 dark:bg-yellow-800'
                                    : ''
                            "
                            class="border-t border-gray-300 dark:border-gray-600 dark:text-white"
                        >
                            <td class="px-3 py-2">
                                <input
                                    type="checkbox"
                                    v-model="selected"
                                    :value="item.id"
                                    :disabled="item.inRAB"
                                />
                            </td>
                            <td class="px-3 py-2">{{ item.nama }}</td>
                            <td class="px-3 py-2 text-center">
                                {{ item.satuan }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{ item.stok }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                {{ item.rekom }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span
                                    v-if="item.inRAB"
                                    class="px-2 py-1 text-xs font-semibold text-yellow-700 bg-yellow-200 rounded dark:bg-yellow-600 dark:text-white"
                                >
                                    Sudah di RAB
                                </span>
                                <span
                                    v-else
                                    class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-200 rounded dark:bg-green-600 dark:text-white"
                                >
                                    Baru
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button
                    @click="$emit('close')"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-white dark:hover:bg-gray-500"
                >
                    Batal
                </button>
                <button
                    @click="$emit('tambah', selected)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                >
                    Tambah
                </button>
            </div>
        </div> -->
        <div class="p-6 bg-white dark:bg-gray-800">
            <h2
                class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100"
            >
                💡 Rekomendasi Restock Cerdas
            </h2>
            <p class="mb-4 text-sm text-gray-500">
                Daftar barang dari Supplier ini yang stoknya kritis (<span
                    class="font-bold text-red-500"
                    >Stok Kini &lt; Min Stok</span
                >).
            </p>

            <div v-if="loading" class="py-8 text-center text-gray-500">
                Menganalisa Stok dan Kebutuhan...
            </div>

            <div
                v-else-if="recommendations.length === 0"
                class="py-8 text-center text-gray-500"
            >
                Semua stok dari Supplier ini masih aman.
            </div>

            <div
                v-else
                class="overflow-y-auto max-h-[60vh] border rounded-lg dark:border-gray-700"
            >
                <table
                    class="min-w-full text-sm text-left divide-y divide-gray-200 dark:divide-gray-700"
                >
                    <thead
                        class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
                    >
                        <tr>
                            <th class="w-10 px-4 py-3">
                                <input
                                    type="checkbox"
                                    v-model="allChecked"
                                    class="border-gray-300 rounded text-lime-600 focus:ring-lime-500"
                                />
                            </th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-center">Stok/Min</th>
                            <th class="px-4 py-3 text-center">Saran Qty</th>
                            <th class="px-4 py-3 text-right">Harga Beli</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="item in recommendations"
                            :key="item.product_id"
                            class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <td class="px-4 py-2">
                                <input
                                    type="checkbox"
                                    :value="item.product_id"
                                    v-model="selectedItems"
                                    class="border-gray-300 rounded text-lime-600 focus:ring-lime-500"
                                />
                            </td>
                            <td class="px-4 py-2 font-medium">
                                {{ item.name }}
                                <div class="text-xs text-gray-500">
                                    ({{ item.code }})
                                </div>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span
                                    class="font-bold"
                                    :class="
                                        item.current_stock <= item.min_stock
                                            ? 'text-red-500'
                                            : 'text-green-500'
                                    "
                                >
                                    {{ item.current_stock }}
                                </span>
                                <span class="text-gray-400">
                                    / {{ item.min_stock }}</span
                                >
                            </td>
                            <td
                                class="px-4 py-2 font-bold text-center text-blue-600"
                            >
                                {{ item.quantity }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                {{ formatRupiah(item.purchase_price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="flex justify-end gap-3 pt-4 mt-6 border-t dark:border-gray-700"
            >
                <SecondaryButton @click="$emit('close')">Tutup</SecondaryButton>
                <PrimaryButton
                    @click="handleAdd"
                    :disabled="selectedItems.length === 0"
                >
                    Ambil Terpilih ({{ selectedItems.length }})
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
