<script setup>
const props = defineProps({
    unlinkedItems: Object,
    handleProductSelection: Object, // berisi handleProductSelection
});
</script>
<template>
    <div>
        <div
            v-if="!unlinkedItems"
            class="flex flex-col items-center justify-center py-10 text-center border border-blue-100 rounded-xl bg-blue-50/50 dark:bg-blue-900/10 dark:border-blue-800"
        >
            <div class="p-3 mb-3 bg-blue-100 rounded-full dark:bg-blue-900/30">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-8 h-8 text-blue-600 dark:text-blue-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                    />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">
                Invoice Terkunci
            </h3>
            <p class="max-w-xs mt-1 text-xs text-gray-500 dark:text-gray-400">
                Status invoice sudah divalidasi. Penambahan item tidak lagi
                diizinkan.
            </p>
        </div>

        <div
            v-else-if="unlinkedItems?.length === 0"
            class="flex flex-col items-center justify-center py-10 text-center border-2 border-gray-100 border-dashed rounded-xl bg-gray-50/50 dark:bg-gray-800/30 dark:border-gray-700"
        >
            <div
                class="p-3 mb-3 bg-green-100 rounded-full dark:bg-green-900/20"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-8 h-8 text-green-600 dark:text-green-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
                    />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-gray-800 dark:text-gray-200">
                Semua Item Tertaut
            </h3>
            <p class="max-w-xs mt-1 text-xs text-gray-500 dark:text-gray-400">
                Tidak ada sisa item pending dari PO ini. Semua sudah masuk ke
                nota.
            </p>
        </div>

        <div
            v-else
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
