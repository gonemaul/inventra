<script setup>
const props = defineProps({
    unlinkedItems: Object,
    handleProductSelection: Object, // berisi handleProductSelection
});
console.log(props.unlinkedItems);
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

        <div class="grid grid-cols-2 gap-3">
            <div
                v-for="item in unlinkedItems"
                :key="item.id"
                @click="handleProductSelection(item, true)"
                class="relative flex flex-col h-full overflow-hidden transition-all bg-white border-2 border-gray-300 border-dashed cursor-pointer group dark:bg-gray-900 dark:border-gray-700 rounded-xl active:scale-95 hover:border-blue-400 dark:hover:border-blue-500"
            >
                <div class="relative bg-gray-100 h-28 dark:bg-gray-800">
                    <img
                        v-if="item.product?.image_url"
                        :src="item.product.image_url"
                        alt="Product Image"
                        class="object-cover w-full h-full transition-opacity opacity-90 group-hover:opacity-100"
                    />
                    <div
                        v-else
                        class="flex items-center justify-center w-full h-full text-2xl font-bold text-gray-400 bg-gray-100 dark:bg-gray-800"
                    >
                        {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                    </div>

                    <div
                        v-if="item.product_snapshot?.brand"
                        class="absolute top-2 left-2 px-1.5 py-0.5 bg-black/50 backdrop-blur-sm text-white text-[9px] font-bold uppercase rounded-md"
                    >
                        {{ item.product_snapshot?.brand }}
                    </div>

                    <div
                        class="absolute p-1 text-gray-500 transition-colors rounded-full shadow-sm top-2 right-2 bg-white/80 dark:bg-gray-900/60 backdrop-blur group-hover:text-blue-600 group-hover:bg-white"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>

                <div class="flex flex-col flex-grow gap-1 p-2">
                    <span
                        class="text-[9px] text-gray-400 font-medium uppercase tracking-wider truncate"
                    >
                        {{ item.product_snapshot?.category || "No Category" }} |
                        {{ item.product_snapshot?.productType || "" }}
                    </span>
                    <h4
                        class="mb-auto text-xs font-bold leading-snug text-gray-800 transition-colors dark:text-gray-100 line-clamp-2 group-hover:text-blue-700"
                    >
                        {{ item.product?.name }}
                    </h4>
                </div>

                <div
                    class="flex items-center justify-between px-2 py-2 border-t border-gray-200 border-dashed bg-gray-50 dark:bg-gray-800/50 dark:border-gray-700"
                >
                    <div class="flex flex-col min-w-0 pr-2">
                        <span
                            class="text-[9px] text-gray-400 font-medium mb-0.5"
                            >Varian:</span
                        >
                        <div
                            class="flex items-center gap-1.5 text-[10px] font-bold text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-3 h-3 text-gray-400 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                                />
                            </svg>

                            <span class="truncate">
                                {{ item.product_snapshot?.size || "-" }} /
                                {{ item.product_snapshot?.unit }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex flex-col items-end pl-2 border-l border-gray-200 shrink-0 dark:border-gray-700"
                    >
                        <span
                            class="text-[9px] text-gray-400 font-medium mb-0.5"
                            >Order:</span
                        >
                        <span
                            class="text-lg font-black leading-none text-gray-800 transition-colors dark:text-white group-hover:text-blue-600"
                        >
                            {{ item.quantity }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
