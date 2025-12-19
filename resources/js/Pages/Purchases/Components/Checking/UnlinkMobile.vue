<script setup>
const props = defineProps({
    unlinkedItems: Object,
    handleProductSelection: Object, // berisi handleProductSelection
});
</script>
<template>
    <div>
        <div
            v-if="unlinkedItems.length === 0"
            class="flex flex-col items-center py-10 text-sm text-center text-gray-400"
        >
            <svg
                class="w-12 h-12 mb-2 text-gray-300"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7"
                />
            </svg>
            <p>Semua item PO sudah ditautkan!</p>
        </div>

        <div
            v-for="item in unlinkedItems"
            :key="item.id"
            @click="handleProductSelection(item, true)"
            class="bg-gray-200 mb-2 shadow-md dark:bg-gray-900 p-3 rounded-xl border border-gray-300 dark:border-gray-800 flex justify-between items-center opacity-75 hover:opacity-100 transition cursor-pointer active:scale-[0.99]"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center justify-center w-10 h-10 text-xs font-bold text-gray-200 bg-gray-400 border border-gray-200 rounded dark:bg-gray-800 dark:border-gray-700"
                >
                    {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                </div>
                <div>
                    <h4
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{ item.product?.name }}
                    </h4>
                    <p class="text-xs text-gray-900">
                        Order: <b>{{ item.quantity }}</b>
                        {{ item.unit?.name }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
