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

        <div class="grid grid-cols-2 gap-3">
            <div
                v-for="item in unlinkedItems"
                :key="item.id"
                @click="handleProductSelection(item, true)"
                class="relative flex flex-col h-full overflow-hidden transition-all bg-white border border-gray-200 cursor-pointer group dark:bg-gray-900 dark:border-gray-800 rounded-2xl active:scale-95 hover:border-lime-400 dark:hover:border-lime-500 shadow-sm"
            >
                <!-- 1:1 Image Section -->
                <div class="relative w-full aspect-square bg-gray-50 dark:bg-gray-800 shrink-0 overflow-hidden">
                    <img
                        v-if="item.product?.image_url"
                        :src="item.product.image_url"
                        alt="Product Image"
                        class="object-cover w-full h-full transition-transform group-hover:scale-110 duration-500"
                    />
                    <div
                        v-else
                        class="flex flex-col items-center justify-center w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900"
                    >
                        <span class="text-3xl font-black text-gray-300 dark:text-gray-700 uppercase select-none">
                            {{ item.product?.name?.substring(0, 2).toUpperCase() }}
                        </span>
                    </div>

                    <!-- Brand / Category Badge -->
                    <div
                        class="absolute top-2 left-2 px-1.5 py-0.5 bg-white/80 dark:bg-black/60 backdrop-blur-sm text-[9px] font-bold uppercase rounded-lg border border-gray-100 dark:border-gray-700 shadow-sm max-w-[70%] truncate dark:text-gray-300"
                    >
                        {{ item.product_snapshot?.brand || item.product_snapshot?.category || 'PO ITEM' }}
                    </div>

                    <!-- Add Indicator -->
                    <div class="absolute p-1.5 text-gray-400 transition-all rounded-full shadow-sm top-2 right-2 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 group-hover:text-lime-500 group-hover:bg-lime-50 dark:group-hover:bg-lime-500/20 group-hover:scale-110">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Product Details -->
                <div class="flex flex-col flex-grow px-3 py-2.5">
                    <h4 class="text-[11px] font-bold leading-tight text-gray-800 dark:text-gray-100 line-clamp-2 min-h-[28px]">
                        {{ item.product?.name }}
                    </h4>
                </div>

                <!-- Footer / Info Section -->
                <div class="border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 px-3 py-2.5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-tighter">Order Qty</span>
                        <div class="flex items-baseline gap-1">
                            <span class="text-lg font-black text-lime-600 dark:text-lime-400">{{ item.quantity }}</span>
                            <span class="text-[9px] font-bold text-gray-400">{{ item.product_snapshot?.unit || 'Pcs' }}</span>
                        </div>
                    </div>
                    
                    <div class="pt-2 border-t border-gray-200/50 dark:border-gray-700/50 flex flex-col gap-1">
                        <div class="flex items-center justify-between opacity-70">
                            <span class="text-[8px] font-bold text-gray-400 uppercase">@Harga</span>
                            <span class="text-[10px] font-bold text-gray-700 dark:text-gray-300">{{ (item.purchase_price || 0).toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black text-gray-400 uppercase">Estimasi</span>
                            <span class="text-[11px] font-black text-gray-900 dark:text-white">Rp{{ ((item.purchase_price || 0) * (item.quantity || 0)).toLocaleString('id-ID') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
