<script setup>
import BottomSheet from "@/Components/BottomSheet.vue";
import { computed } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close", "addToCart", "update:item"]);

const currentItem = computed({
    get: () => props.item,
    set: (val) => emit("update:item", val),
});

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
</script>

<template>
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="Tambah Pesanan"
    >
        <div class="space-y-6">
            <div
                class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800"
            >
                <div
                    class="flex items-center justify-center text-lg font-bold text-gray-400 bg-gray-100 border border-gray-200 w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-700 shrink-0"
                >
                    {{ currentItem.name?.substring(0, 2).toUpperCase() }}
                </div>

                <div class="flex-1 min-w-0">
                    <h3
                        class="text-lg font-bold leading-tight text-gray-800 truncate dark:text-white"
                    >
                        {{ currentItem.name }}
                    </h3>
                    <div class="flex items-center justify-between mt-1">
                        <p
                            class="text-xs text-gray-500 font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded"
                        >
                            {{ currentItem.code }}
                        </p>
                        <p
                            class="text-xs font-medium"
                            :class="
                                3 > currentItem.stock
                                    ? 'text-red-500'
                                    : 'text-gray-400'
                            "
                        >
                            Stok: {{ currentItem.stock }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center justify-between">
                    <button
                        @click="
                            currentItem.quantity > 1
                                ? currentItem.quantity--
                                : null
                        "
                        class="flex items-center justify-center text-gray-500 transition bg-white border border-gray-200 shadow-sm w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-600 active:scale-95 hover:text-red-500 disabled:opacity-50"
                        :disabled="currentItem.quantity <= 1"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M20 12H4"
                            />
                        </svg>
                    </button>

                    <div class="flex-1 px-2 text-center">
                        <label
                            class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5"
                            >Jumlah Beli</label
                        >
                        <input
                            v-model.number="currentItem.quantity"
                            type="number"
                            class="w-full p-0 text-3xl font-black text-center text-gray-800 bg-transparent border-none dark:text-white focus:ring-0"
                        />
                    </div>

                    <button
                        @click="currentItem.quantity++"
                        class="flex items-center justify-center text-white transition shadow-lg w-14 h-14 bg-lime-500 rounded-xl shadow-lime-500/30 active:scale-95"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <div
                class="flex items-center justify-between p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800 rounded-xl"
            >
                <div>
                    <p class="text-xs text-gray-400">Harga Satuan</p>
                    <p class="font-bold text-gray-600 dark:text-gray-300">
                        {{ rp(currentItem.price) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase">
                        Subtotal
                    </p>
                    <p
                        class="text-2xl font-black text-gray-900 dark:text-white"
                    >
                        {{ rp(currentItem.price * currentItem.quantity) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <!-- Tombol Scan Lagi -->
                <button
                    @click="$emit('addToCart', true)" 
                    class="w-full py-4 text-sm font-bold text-gray-700 bg-white border border-gray-200 shadow-sm rounded-2xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 dark:bg-gray-800 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 flex flex-col items-center justify-center gap-1"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 mb-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    <span>Masuk & Scan Lg</span>
                </button>

                <!-- Tombol Simpan -->
                <button
                    @click="$emit('addToCart', false)"
                    class="w-full py-4 text-sm font-bold text-white shadow-lg bg-lime-500 rounded-2xl hover:bg-lime-600 hover:shadow-lime-500/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 flex flex-col items-center justify-center gap-1"
                >
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
