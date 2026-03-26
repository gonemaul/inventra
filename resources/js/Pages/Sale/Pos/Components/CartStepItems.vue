<script setup>
import InputRupiah from "@/Components/InputRupiah.vue";
import { computed } from "vue";
import { usePosState } from "@/Composables/POS/usePosState";
import { storeToRefs } from "pinia";

const posState = usePosState();
const { activeDraft, serviceProducts } = storeToRefs(posState);
const { removeItem, recalcFromQty, recalcFromPrice, updateQty, addItem, nextStep, rp } = posState;

const cartServices = computed(() => {
    return activeDraft.value.cart_items.filter(item => ['Jasa', 'Layanan'].includes(item.category?.name));
});

const cartProducts = computed(() => {
    return activeDraft.value.cart_items.filter(item => !['Jasa', 'Layanan'].includes(item.category?.name));
});

const isBengkel = computed(() => activeDraft.value.mode === 'bengkel');
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Quick Services (Bengkel only) -->
        <div
            v-if="isBengkel && serviceProducts?.length > 0"
            class="px-4 py-2 bg-white border-b shrink-0 dark:bg-gray-800 dark:border-gray-700"
        >
            <h3 class="text-[10px] font-bold text-gray-400 uppercase mb-1.5">Layanan Cepat</h3>
            <div class="flex gap-2 pb-1 overflow-x-auto scrollbar-hide">
                <button
                    v-for="srv in serviceProducts"
                    :key="srv.id"
                    @click="addItem(srv)"
                    class="flex-shrink-0 px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-[11px] font-bold whitespace-nowrap active:scale-95 hover:bg-blue-100 transition dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800"
                >
                    + {{ srv.name }}
                </button>
            </div>
        </div>

        <!-- Cart Items Scroll Area -->
        <div class="flex-1 px-4 py-3 space-y-3 overflow-y-auto bg-gray-50 custom-scroll dark:bg-gray-900">
            <!-- Empty State -->
            <div
                v-if="activeDraft.cart_items.length === 0"
                class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-600 opacity-70"
            >
                <div class="p-5 mb-3 bg-white rounded-2xl shadow-sm dark:bg-gray-800">
                    <span class="text-4xl">🛍️</span>
                </div>
                <span class="text-sm font-bold tracking-wide uppercase">Keranjang Kosong</span>
                <span class="mt-1 text-xs font-normal">Scan barcode atau cari produk...</span>
            </div>

            <!-- SECTION: SERVICES (JASA) -->
            <div v-if="cartServices.length > 0" class="space-y-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1">Jasa & Layanan</h3>
                <div
                    v-for="(item, index) in activeDraft.cart_items"
                    :key="'svc-' + index"
                    v-show="['Jasa', 'Layanan'].includes(item.category?.name)"
                    class="flex flex-col p-3 bg-white border border-blue-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-blue-900/50 relative overflow-hidden"
                >
                    <div class="absolute top-0 left-0 w-1 h-full bg-blue-500"></div>
                    <div class="flex items-start justify-between gap-3 pl-2">
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">{{ item.name }}</h4>
                            <div class="mt-2 flex items-center gap-2">
                                <button
                                    @click="() => {
                                        if (item.selling_price == 0) {
                                            item.selling_price = item.original_price || 0;
                                        } else {
                                            item.original_price = item.selling_price;
                                            item.selling_price = 0;
                                        }
                                        recalcFromPrice(item);
                                    }"
                                    class="flex items-center gap-1 px-2 py-1 rounded-md text-[10px] font-bold border transition-all active:scale-95 uppercase"
                                    :class="item.selling_price == 0
                                        ? 'bg-blue-100 text-blue-700 border-blue-200'
                                        : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200'"
                                >
                                    {{ item.selling_price == 0 ? '💵 Bayar' : '🎁 Gratis' }}
                                </button>
                                <div v-if="item.selling_price != 0" class="relative w-28">
                                    <InputRupiah
                                        v-model="item.selling_price"
                                        @input="recalcFromPrice(item)"
                                        class="w-full py-1 text-right text-sm font-bold border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="0"
                                        @focus="$event.target.select()"
                                    />
                                </div>
                                <span v-else class="text-xs font-bold text-blue-600 ml-1">Gratis</span>
                            </div>
                        </div>
                        <button
                            @click="removeItem(activeDraft.cart_items.indexOf(item))"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- SECTION: PRODUCTS -->
            <div v-if="cartProducts.length > 0" class="space-y-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1">Produk</h3>
                <div
                    v-for="(item, index) in activeDraft.cart_items"
                    :key="'prod-' + index"
                    v-show="!['Jasa', 'Layanan'].includes(item.category?.name)"
                    class="relative flex gap-3 p-3 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700 group hover:border-lime-300 transition"
                >
                    <!-- Product Image -->
                    <div class="shrink-0">
                        <img v-if="item.image_url" :src="item.image_url" alt="Product" class="w-11 h-11 object-cover rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50" />
                        <div v-else class="w-11 h-11 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                    </div>

                    <!-- Product Details & Controls -->
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <div class="flex justify-between items-start gap-1">
                            <div>
                                <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-tight line-clamp-2">{{ item.name }}</h4>
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-0.5 text-[10px] text-gray-500">
                                    <span v-if="item.code" class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-gray-600 dark:text-gray-300">{{ item.code }}</span>
                                    <span v-if="item.size" class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ item.size?.name }}</span>
                                    <span v-if="item.unit">{{ item.unit?.name }}</span>
                                </div>
                            </div>
                            <button
                                @click="removeItem(activeDraft.cart_items.indexOf(item))"
                                class="shrink-0 p-1 -mr-1 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>

                        <!-- Price & Qty -->
                        <div class="flex items-end justify-between gap-2 mt-2">
                            <div class="flex-1">
                                <InputRupiah
                                    v-model="item.selling_price"
                                    @input="recalcFromPrice(item)"
                                    class="w-full !py-0 !px-0 bg-transparent border-0 border-b border-gray-200 dark:border-gray-600 text-sm font-bold text-lime-600 focus:ring-0 focus:border-lime-500 p-0"
                                />
                            </div>
                            <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shrink-0 h-8 bg-white dark:bg-gray-800">
                                <button @click="updateQty(activeDraft.cart_items.indexOf(item), -1)" class="w-8 h-full flex items-center justify-center hover:bg-red-50 hover:text-red-500 active:bg-gray-100 transition text-base font-bold">−</button>
                                <input
                                    type="number"
                                    v-model="item.quantity"
                                    @input="recalcFromQty(item)"
                                    :step="item.unit?.is_decimal ? '0.001' : '1'"
                                    class="w-10 h-full text-center text-sm font-bold border-none bg-transparent p-0 focus:ring-0 appearance-none"
                                    @focus="$event.target.select()"
                                />
                                <button @click="updateQty(activeDraft.cart_items.indexOf(item), 1)" class="w-8 h-full flex items-center justify-center hover:bg-lime-50 hover:text-lime-600 active:bg-gray-100 transition text-base font-bold">+</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer CTA -->
        <div class="shrink-0 px-5 pb-5 pt-3 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-4px_12px_rgba(0,0,0,0.04)]">
            <button
                @click="nextStep"
                :disabled="!activeDraft.cart_items.length"
                class="w-full h-[52px] text-white font-bold rounded-xl text-sm transition-all active:scale-[0.98] flex justify-center items-center gap-2 shadow-lg"
                :class="activeDraft.cart_items.length
                    ? activeDraft.mode === 'bengkel' ? 'bg-blue-500 hover:bg-blue-600 shadow-blue-500/20' : 'bg-lime-500 hover:bg-lime-600 shadow-lime-500/20'
                    : 'bg-gray-300 cursor-not-allowed shadow-none'"
            >
                <span>{{ activeDraft.cart_items.length ? 'LANJUT PEMBAYARAN' : 'KERANJANG KOSONG' }}</span>
                <svg v-if="activeDraft.cart_items.length" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
            </button>
        </div>
    </div>
</template>
