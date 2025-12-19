<script setup>
const props = defineProps({
    modelValue: String,
    filteredProducts: Object,
    unlinkedItems: Object,
});
const emit = defineEmits(["update:modelValue", "close", "onSelectSearch"]);
</script>
<template>
    <div
        class="fixed inset-0 z-40 p-4 pt-20 bg-white dark:bg-gray-900 animate-fade-in"
    >
        <div class="relative">
            <input
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                type="text"
                placeholder="Ketik nama / kode (atau hasil scan)..."
                class="w-full py-3 pl-4 pr-10 bg-gray-100 border-none dark:bg-gray-800 rounded-xl focus:ring-2 focus:ring-lime-500 dark:text-white"
                autoFocus
            />
            <button
                @click="$emit('close')"
                class="absolute text-gray-400 -translate-y-1/2 right-3 top-1/2"
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
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div class="mt-4 space-y-2 overflow-y-auto max-h-[80vh]">
            <!-- <p
                class="mt-10 text-sm text-center text-gray-400"
            >
                Ketik minimal 2 huruf...
            </p> -->

            <div
                v-if="filteredProducts.length === 0"
                class="mt-10 text-sm text-center text-gray-400"
            >
                Produk tidak ditemukan di database.
            </div>

            <div
                v-for="prod in filteredProducts"
                :key="prod.id"
                @click="$emit('onSelectSearch', prod)"
                class="flex items-center justify-between p-3 transition bg-white border border-gray-100 dark:bg-gray-800 rounded-xl dark:border-gray-700 active:bg-lime-50 dark:active:bg-lime-900/20"
            >
                <div>
                    <h4 class="text-sm font-bold text-gray-800 dark:text-white">
                        {{ prod.name }}
                    </h4>
                    <p class="text-xs text-gray-500">{{ prod.code }}</p>
                </div>
                <span
                    v-if="unlinkedItems.find((u) => u.product_id === prod.id)"
                    class="text-[9px] bg-lime-100 text-lime-700 px-2 py-0.5 rounded font-bold"
                >
                    MATCH PO
                </span>
            </div>
        </div>
    </div>
</template>
