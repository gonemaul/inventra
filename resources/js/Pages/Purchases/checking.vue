<template>
    <Head title="Detail Invoice"></Head>
    <AuthenticatedLayout headerTitle="Detail Invoice" :showSidebar="false">
        <div class="container p-4 mx-auto md:p-8">
            <div class="p-6 mb-6 bg-white rounded-lg shadow-md">
                <h1 class="mb-4 text-2xl font-bold text-gray-800">
                    Detail Faktur #{{ invoice.number }}
                </h1>
                <div
                    class="grid grid-cols-1 gap-4 text-gray-600 md:grid-cols-2"
                >
                    <div>
                        <p>
                            <strong>Nomor Pesanan:</strong>
                            {{ invoice.po_number }}
                        </p>
                        <p>
                            <strong>Tanggal Faktur:</strong> {{ invoice.date }}
                        </p>
                    </div>
                    <div>
                        <p><strong>Pemasok:</strong> {{ invoice.supplier }}</p>
                        <p>
                            <strong>Status:</strong>
                            <span class="font-semibold text-green-600">{{
                                invoice.status
                            }}</span>
                        </p>
                    </div>
                </div>
                <button
                    v-if="invoice.photo"
                    @click="viewPhoto"
                    class="mt-4 text-blue-600 hover:text-blue-800"
                >
                    Lihat Foto Faktur
                </button>
            </div>

            <div class="p-6 bg-white rounded-lg shadow-md">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold">Produk dalam Faktur</h2>
                    <div class="space-x-2">
                        <button
                            @click="showAddFromOrderModal = true"
                            class="px-4 py-2 text-white bg-blue-500 rounded"
                        >
                            + Tambah dari Pesanan
                        </button>
                        <button
                            @click="showAddManualModal = true"
                            class="px-4 py-2 text-white bg-green-500 rounded"
                        >
                            + Tambah Manual
                        </button>
                    </div>
                </div>

                <div
                    v-if="invoice.products.length === 0"
                    class="py-8 text-center text-gray-500"
                >
                    Belum ada produk di faktur ini.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Nama Produk
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Qty Pesan
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Harga Pesan
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Qty Diterima
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Harga Invoice
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Total
                                </th>
                                <th
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase"
                                >
                                    Status
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Aksi</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="product in invoice.products"
                                :key="product.id"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ product.name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ product.category }} -
                                        {{ product.size }} - {{ product.unit }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                >
                                    {{ product.ordered_quantity }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                >
                                    Rp{{
                                        product.ordered_price.toLocaleString(
                                            "id-ID"
                                        )
                                    }}
                                    <!-- {{ product.ordered_price }} -->
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                >
                                    <input
                                        type="number"
                                        v-model.number="
                                            product.received_quantity
                                        "
                                        class="w-20 px-2 py-1 text-center border rounded"
                                    />
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                >
                                    <input
                                        type="number"
                                        v-model.number="product.invoice_price"
                                        class="w-24 px-2 py-1 text-center border rounded"
                                    />
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap"
                                >
                                    Rp{{
                                        product.total.toLocaleString("id-ID")
                                    }}
                                    <!-- {{ product.total }} -->
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-semibold text-green-600 whitespace-nowrap"
                                >
                                    {{ product.status }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap"
                                >
                                    <button
                                        @click="removeProduct(product.id)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <AddOrderModal
            :show="showAddFromOrderModal"
            :products-from-order="productsFromOrder"
            @close="showAddFromOrderModal = false"
            @add-products="handleAddedProducts"
        />

        <AddManualModal
            :show="showAddManualModal"
            @close="showAddManualModal = false"
            @add-products="handleAddedProducts"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import AddManualModal from "./partials/AddManualModal.vue";
import AddOrderModal from "./partials/AddOrderModal.vue";

const showAddFromOrderModal = ref(false);
const showAddManualModal = ref(false);

const invoice = ref({
    number: "INV-001",
    po_number: "PO-2025-001",
    date: "22-09-2025",
    supplier: "PT Maju Terus",
    status: "Diterima",
    photo: "https://via.placeholder.com/600x400",
    products: [], // Produk yang sudah ada di faktur
});

// Ini adalah data dummy dari "pesanan" yang belum diterima
let productsFromOrder = ref([
    {
        id: 1,
        name: "Laptop ASUS ROG",
        category: "Elektronik",
        size: "15 inch",
        unit: "Unit",
        ordered_quantity: 2,
        ordered_price: 15000000,
    },
    {
        id: 2,
        name: 'Monitor Dell 27"',
        category: "Elektronik",
        size: "27 inch",
        unit: "Unit",
        ordered_quantity: 1,
        ordered_price: 5000000,
    },
    {
        id: 3,
        name: "Mouse Logitech",
        category: "Aksesoris",
        size: "Wireless",
        unit: "Unit",
        ordered_quantity: 3,
        ordered_price: 500000,
    },
]);

const handleAddedProducts = (addedProducts) => {
    addedProducts.forEach((newProduct) => {
        // Menambahkan properti yang diperlukan jika belum ada
        const fullProduct = {
            ...newProduct,
            category: newProduct.category || "",
            size: newProduct.size || "",
            ordered_quantity:
                newProduct.ordered_quantity || newProduct.quantity || 1,
            ordered_price: newProduct.ordered_price || 0,
            received_quantity:
                newProduct.received_quantity || newProduct.quantity || 1,
            invoice_price: newProduct.invoice_price || 0,
            status: "Diterima",
        };

        // Menggunakan computed untuk membuat total reaktif
        const productWithTotal = computed(() => ({
            ...fullProduct,
            total:
                fullProduct.received_quantity * fullProduct.invoice_price || 0,
        }));

        invoice.value.products.push(productWithTotal.value);

        // Logika untuk menghapus produk dari `productsFromOrder`
        if (fullProduct.source === "Pesanan") {
            productsFromOrder.value = productsFromOrder.value.filter(
                (p) => p.id !== fullProduct.id
            );
        }
    });
};

const removeProduct = (id) => {
    const productToRemove = invoice.value.products.find((p) => p.id === id);
    if (productToRemove) {
        // Kembalikan produk ke daftar "productsFromOrder" jika sumbernya dari pesanan
        if (productToRemove.source === "Pesanan") {
            productsFromOrder.value.push({
                id: productToRemove.id,
                name: productToRemove.name,
                category: productToRemove.category,
                size: productToRemove.size,
                unit: productToRemove.unit,
                ordered_quantity: productToRemove.ordered_quantity,
                ordered_price: productToRemove.ordered_price,
            });
        }
        // Hapus dari tabel utama
        invoice.value.products = invoice.value.products.filter(
            (p) => p.id !== id
        );
    }
};

const viewPhoto = () => {
    window.open(invoice.value.photo, "_blank");
};
</script>
