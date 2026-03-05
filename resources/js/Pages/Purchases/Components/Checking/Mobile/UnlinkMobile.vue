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

        <div class="grid grid-cols-2 gap-2.5">
            <div
                v-for="item in unlinkedItems"
                :key="item.id"
                @click="handleProductSelection(item, true)"
                class="relative flex flex-col h-full overflow-hidden transition-all bg-white border-2 border-gray-200 border-dashed cursor-pointer group dark:bg-gray-900 dark:border-gray-700 rounded-xl active:scale-95 hover:border-blue-400 dark:hover:border-blue-500 shadow-sm"
            >
                <!-- 1:1 Image -->
                <div class="relative w-full aspect-square bg-gray-100 dark:bg-gray-800 shrink-0 overflow-hidden">
                    <img
                        v-if="item.product?.image_url"
                        :src="item.product.image_url"
                        alt="Product Image"
                        class="object-cover w-full h-full transition-opacity opacity-90 group-hover:opacity-100"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center justify-center w-full h-full bg-gradient-to-br from-gray-50 to-gray-200 dark:from-gray-800 dark:to-gray-700"
                    >
                        <span class="text-2xl font-black text-gray-300 dark:text-gray-600 uppercase select-none">
                            {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                        </span>
                    </div>

                    <!-- Brand badge -->
                    <div
                        v-if="item.product_snapshot?.brand"
                        class="absolute top-1.5 left-1.5 px-1.5 py-0.5 bg-black/50 backdrop-blur-sm text-white text-[9px] font-bold uppercase rounded-md max-w-[70%] truncate"
                    >
                        {{ item.product_snapshot?.brand }}
                    </div>

                    <!-- Add icon -->
                    <div class="absolute p-1 text-gray-500 transition-colors rounded-full shadow-sm top-1.5 right-1.5 bg-white/80 dark:bg-gray-900/60 backdrop-blur group-hover:text-blue-600 group-hover:bg-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Product name & code -->
                <div class="flex flex-col flex-grow px-2 py-1.5">
                    <h4 class="text-[11px] font-bold leading-snug text-gray-800 dark:text-gray-100 line-clamp-2 group-hover:text-blue-700 transition-colors">
                        {{ item.product?.name }}
                    </h4>
                    <span class="text-[9px] text-gray-400 font-medium mt-0.5 truncate">
                        {{ item.product?.code || item.product_snapshot?.category || '-' }}
                    </span>
                </div>

                <!-- Data Vital -->
                <div class="border-t border-gray-200/80 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/60 px-2 py-2">
                    <!-- Qty besar -->
                    <div class="flex items-baseline justify-between mb-1">
                        <span class="text-[9px] text-gray-400 font-bold uppercase">Qty Order</span>
                        <span class="text-2xl font-black leading-none text-blue-600 dark:text-blue-400">{{ item.quantity }}</span>
                    </div>
                    <!-- Harga & Est Total -->
                    <div class="pt-1 border-t border-gray-200/50 dark:border-gray-700 flex flex-col gap-0.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] text-gray-400">@ Harga</span>
                            <span class="text-[10px] font-semibold text-gray-700 dark:text-gray-300">Rp {{ (item.purchase_price || 0).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] text-gray-400 font-bold">Est. Total</span>
                            <span class="text-[11px] font-black text-gray-900 dark:text-white">Rp {{ ((item.purchase_price || 0) * (item.quantity || 0)).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
