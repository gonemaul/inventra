<script setup>
// PROPS: Data & Fungsi Helper dari Parent
const props = defineProps({
    items: {
        type: Array,
        required: true,
    },
    // Fungsi Validasi Desimal
    isDecimalAllowed: {
        type: Function,
        required: true,
    },
    // Fungsi Cek Input Keyboard
    checkInteger: {
        type: Function,
        required: true,
    },
});

// EMITS: Lapor perubahan ke Parent
const emit = defineEmits(["remove", "update-calc", "update-qty"]);

// WRAPPER FUNCTION
// Kita bungkus emit biar template lebih bersih
const onRemove = (index) => emit("remove", index);
const onCalcSubtotal = (item) => emit("update-calc", item);
const onCalcQty = (item) => emit("update-qty", item);

// Helper Visual
const getInitials = (name) =>
    name ? name.substring(0, 2).toUpperCase() : "??";
</script>

<template>
    <div
        class="relative flex flex-col flex-1 min-h-0 bg-white border border-gray-300 rounded-lg shadow-sm"
    >
        <div
            class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-50"
        >
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-300 border-b border-gray-500">
                    <tr>
                        <th
                            class="px-4 py-3 text-left min-w-[200px] max-w-[35%] text-xs font-bold text-gray-600 uppercase tracking-wider"
                        >
                            Produk
                        </th>
                        <th
                            class="px-2 py-3 text-center min-w-[130px] max-w-[15%] text-xs font-bold text-gray-600 uppercase tracking-wider"
                        >
                            Harga (@)
                        </th>
                        <th
                            class="px-2 py-3 text-center min-w-[130px] max-w-[10%] text-xs font-bold text-gray-600 uppercase tracking-wider"
                        >
                            Qty
                        </th>
                        <th
                            class="px-2 py-3 text-center min-w-[130px] max-w-[20%] text-xs font-bold text-gray-600 uppercase tracking-wider"
                        >
                            Subtotal
                        </th>
                        <th
                            class="w-[5%] text-center text-xs font-bold text-gray-600 uppercase"
                        >
                            #
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <tr v-if="items.length === 0">
                        <td colspan="5" class="h-64 text-center align-middle">
                            <div
                                class="flex flex-col items-center justify-center text-gray-400"
                            >
                                <svg
                                    class="w-16 h-16 mb-4 opacity-20"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.5"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                    ></path>
                                </svg>
                                <p class="text-sm font-medium">
                                    Belum ada barang
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr
                        v-for="(item, index) in items"
                        :key="index"
                        class="transition duration-75 group hover:bg-indigo-50/50"
                    >
                        <td class="px-4 py-3 min-w-[200px] w-[35%] align-top">
                            <div class="items-start gap-3 md:flex">
                                <div
                                    class="flex items-center justify-center flex-shrink-0 w-12 h-12 overflow-hidden border border-gray-200 rounded bg-gray-50"
                                >
                                    <img
                                        v-if="item.image"
                                        :src="item.image"
                                        class="object-cover w-full h-full"
                                    />
                                    <span
                                        v-else
                                        class="text-sm font-bold text-gray-400"
                                        >{{ getInitials(item.name) }}</span
                                    >
                                </div>
                                <div class="min-w-0 pt-0.5">
                                    <div
                                        class="text-sm font-bold leading-tight text-gray-900 md:text-base line-clamp-2"
                                    >
                                        {{ item.name }}
                                    </div>

                                    <div
                                        class="flex flex-wrap gap-2 mt-1 text-xs text-gray-500"
                                    >
                                        <span
                                            class="bg-indigo-50 hidden md:block text-indigo-700 px-1.5 py-0.5 rounded border border-indigo-100 font-medium"
                                        >
                                            {{ item.brand || "-" }}
                                        </span>
                                        <span
                                            class="font-mono text-gray-400 border border-gray-200 px-1.5 rounded"
                                        >
                                            {{ item.code }}
                                        </span>
                                        <span
                                            class="text-xs"
                                            :class="
                                                item.stock_max <= 0
                                                    ? 'text-red-600 font-bold '
                                                    : 'text-green-600 '
                                            "
                                            >Stok: {{ item.stock_max }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-2 py-3 min-w-[130px] w-[15%] align-top">
                            <div class="flex items-start gap-1">
                                <div
                                    class="relative w-full rounded-md shadow-sm"
                                >
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-gray-400 text-[10px]"
                                    >
                                        Rp
                                    </div>
                                    <input
                                        type="number"
                                        v-model="item.selling_price"
                                        :disabled="item.is_price_locked"
                                        @input="onCalcSubtotal(item)"
                                        class="block w-full rounded border-gray-300 pl-6 py-1.5 text-sm font-mono text-right focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
                                    />
                                </div>
                                <button
                                    @click="
                                        item.is_price_locked =
                                            !item.is_price_locked
                                    "
                                    class="mt-1.5 text-gray-400 hover:text-indigo-600 transition-colors"
                                    :title="
                                        item.is_price_locked
                                            ? 'Klik untuk ubah harga'
                                            : 'Klik untuk kunci harga'
                                    "
                                >
                                    <svg
                                        v-if="item.is_price_locked"
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        ></path>
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4 text-indigo-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <td class="px-2 py-3 min-w-[130px] w-[10%] align-top">
                            <div class="relative rounded-md shadow-sm">
                                <input
                                    type="number"
                                    step="any"
                                    min="0"
                                    v-model="item.quantity"
                                    @input="onCalcSubtotal(item)"
                                    @keydown="checkInteger($event, item.unit)"
                                    class="block w-full rounded border-gray-300 py-1.5 pl-2 pr-8 text-sm font-bold text-center focus:border-indigo-500 focus:ring-indigo-500"
                                    :class="
                                        item.quantity > item.stock_max
                                            ? 'text-red-700 bg-red-50 border-red-300'
                                            : ''
                                    "
                                />
                                <div
                                    class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none"
                                >
                                    <span
                                        class="text-gray-500 text-[10px] bg-gray-100 px-1 rounded"
                                        >{{ item.unit }}</span
                                    >
                                </div>
                            </div>
                            <div
                                v-if="item.quantity > item.stock_max"
                                class="text-[10px] text-red-600 font-bold mt-1 text-center leading-none"
                            >
                                Stok Kurang!
                            </div>
                        </td>

                        <td class="px-2 py-3 min-w-[130px] w-[20%] align-top">
                            <div class="flex items-start justify-end gap-1">
                                <div class="relative rounded-md shadow-sm">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2 text-amber-700 font-bold text-[10px]"
                                    >
                                        Rp
                                    </div>
                                    <input
                                        type="number"
                                        v-model="item.subtotal"
                                        :disabled="item.is_total_locked"
                                        @input="onCalcQty(item)"
                                        class="block w-full rounded border-lime-300 bg-lime-50 pl-7 py-1.5 text-sm font-medium text-gray-900 text-right focus:border-amber-500 focus:ring-amber-500 font-mono placeholder-gray-300"
                                        placeholder="0"
                                    />
                                </div>
                                <button
                                    @click="
                                        item.is_total_locked =
                                            !item.is_total_locked
                                    "
                                    class="mt-1.5 text-gray-400 hover:text-indigo-600 transition-colors"
                                    :title="
                                        item.is_total_locked
                                            ? 'Klik untuk ubah harga'
                                            : 'Klik untuk kunci harga'
                                    "
                                >
                                    <svg
                                        v-if="item.is_total_locked"
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                        ></path>
                                    </svg>
                                    <svg
                                        v-else
                                        class="w-4 h-4 text-indigo-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </td>

                        <td class="w-[5%] text-center align-top pt-2">
                            <button
                                @click="onRemove(index)"
                                tabindex="-1"
                                class="text-white bg-red-500 hover:text-red-500 p-1.5 rounded-md hover:bg-red-300 transition duration-150"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    ></path>
                                </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
