<script setup>
import { ref } from "vue";

const props = defineProps({
    products: {
        type: Array,
        required: true,
    },
    isFetching: {
        type: Boolean,
        default: false,
    },
    cartItems: {
        type: Array,
        default: () => [],
    },
    allProductsCount: {
        type: Number, // Total count to check if we can load more
        default: 0,
    },
    compareList: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["addToCart", "openDetail", "toggleCompare", "loadMore"]);

const productGridRef = ref(null);

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);

const getCartQty = (productId) => {
    const item = props.cartItems.find((i) => i.product_id === productId);
    return item ? item.quantity : 0;
};

const isDisabled = (product) => {
    const isService = ["Jasa", "Layanan"].includes(product.category?.name);
    return isService && getCartQty(product.id) > 0;
};

const isInCompare = (productId) => {
    return props.compareList.some((p) => p.id === productId);
};

// Berfungsi mendeteksi jika user scroll mentok bawah -> load data lagi
const handleScroll = (e) => {
    const element = e.target;
    // emit('scroll-list', e); // Removed to prevent auto-blur on programmatic scroll
    
    if (element.scrollHeight - element.scrollTop <= element.clientHeight + 50) {
        emit("loadMore");
    }
};

const handleTouchStart = () => {
    // Only blur if active element is an input
    if (document.activeElement && ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        document.activeElement.blur();
    }
};
</script>

<template>
    <div
        ref="productGridRef"
        class="h-[calc(100vh-220px)] flex-1 p-4 overflow-y-auto bg-gray-100 custom-scroll scroll-smooth pb-28 lg:pb-4 dark:bg-gray-900"
        @scroll="handleScroll"
        @touchstart.passive="handleTouchStart"
    >
        <div
            class="grid grid-cols-2 gap-3 pb-10 md:grid-cols-3 lg:grid-cols-4"
        >
            <div
                v-for="product in products"
                :key="product.id"
                class="relative flex flex-col justify-between overflow-hidden transition-all duration-200 border shadow-sm group rounded-xl bg-white dark:bg-gray-800"
                :class="[
                    product.stock == 0 ? 'opacity-75 grayscale-[0.5]' : '',
                    getCartQty(product.id) > 0
                        ? 'border-lime-500 ring-1 ring-lime-500 bg-lime-50/30 dark:bg-lime-900/10'
                        : 'border-gray-200 dark:border-gray-700 hover:shadow-md',
                ]"
            >
                <!-- 1. Main Click Area (Image & Info) triggers Add to Cart -->
                <div 
                    class="flex-1 flex flex-col transition-transform"
                    :class="isDisabled(product) ? 'cursor-not-allowed opacity-80 grayscale-[0.5]' : 'cursor-pointer active:scale-[0.98]'"
                    @click="!isDisabled(product) && $emit('addToCart', product)"
                >
                    <div
                        class="relative w-full overflow-hidden bg-gray-100 border-b border-gray-100 aspect-square dark:border-gray-700 dark:bg-gray-700"
                    >
                        <!-- Badges Unit/Size (Prominent) -->
                        <div
                            class="absolute z-20 flex flex-col items-start gap-1 top-2 left-2"
                        >
                            <span
                                v-if="product.unit"
                                class="text-[10px] font-bold px-2 py-0.5 rounded shadow-sm backdrop-blur text-gray-700 bg-white/90 dark:text-gray-200 dark:bg-gray-900/80 border border-gray-200 dark:border-gray-600"
                            >
                                {{ product.unit?.name }}
                            </span>
                            <span
                                v-if="product.size"
                                class="text-[10px] font-bold text-white px-2 py-0.5 rounded shadow-sm backdrop-blur bg-gray-800/90 dark:bg-gray-600/90 border border-gray-700"
                            >
                                {{ product.size?.name }}
                            </span>
                        </div>

                        <!-- BADGES STATUS (Right Side) -->
                        <div class="absolute z-20 flex flex-col items-end gap-1 top-2 right-2">
                             <span v-if="product.is_best_seller" class="text-[10px] font-bold px-2 py-0.5 bg-orange-200 rounded shadow-sm text-orange-600 border border-orange-400 animate-pulse flex items-center gap-1">
                                🔥 Best Seller
                             </span>
                             <span v-if="product.is_dead_stock" class="text-[10px] font-bold px-2 py-0.5 rounded shadow-sm text-white bg-blue-500 border border-blue-600 flex items-center gap-1">
                                📦 Stok Banyak
                             </span>
                        </div>

                        <img
                            v-if="product.image_url"
                            :src="product.image_url"
                            loading="lazy"
                            class="absolute inset-0 z-10 object-cover w-full h-full transition-transform duration-500 group-hover:scale-105"
                            alt=""
                            onerror="this.style.display='none'"
                        />
                        
                        <div
                            v-if="!product.image_url"
                            class="flex items-center justify-center w-full h-full bg-gradient-to-br from-gray-300 to-gray-50 dark:from-gray-700 dark:to-gray-800 p-2"
                        >
                            <span class="text-[12px] md:text-[15px] font-bold text-center text-gray-600 dark:text-gray-400 line-clamp-3 break-words select-none">{{ product.name }}</span>
                        </div>

                        <!-- Overlay Stock 0 -->
                        <div
                            v-if="product.stock <= 0"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-gray-900/60 backdrop-blur-[1px]"
                        >
                            <span
                                class="px-2 py-1 text-xs font-bold text-white transform border-2 border-white rounded -rotate-12"
                                >KOSONG</span
                            >
                        </div>

                        <!-- Overlay Service Added (isDisabled) -->
                        <div
                            v-if="isDisabled(product)"
                            class="absolute inset-0 z-20 flex items-center justify-center bg-lime-900/20 backdrop-blur-[1px]"
                        >
                            <div class="flex flex-col items-center justify-center p-2 bg-white/90 rounded-xl shadow-lg animate-in zoom-in duration-200">
                                <svg class="w-6 h-6 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                <span class="text-[9px] font-black text-lime-700 uppercase">Dipilih</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col flex-1 p-3">
                        <div class="mb-1 flex justify-between items-start gap-2">
                            <span
                                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wide truncate"
                            >
                                {{ product.brand?.name || "No Brand" }}
                            </span>
                            <span v-if="product.purchase_price" class="text-[9px] font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400 px-1.5 py-0.5 rounded shadow-sm border border-emerald-100 dark:border-emerald-800 shrink-0 whitespace-nowrap">
                               Untung: {{ rp((product.selling_price || product.price) - product.purchase_price) }}
                            </span>
                        </div>

                        <h3
                            class="text-xs font-bold leading-snug text-gray-800 dark:text-gray-100 line-clamp-2 min-h-[2.5em] mb-2"
                            :title="product.name"
                        >
                            {{ product.name }}
                        </h3>

                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1.5">
                                <div
                                    class="w-1.5 h-1.5 rounded-full"
                                    :class="
                                        product.stock <= 5
                                            ? 'bg-red-500 animate-pulse'
                                            : 'bg-green-500 dark:bg-green-400'
                                    "
                                ></div>
                                <span
                                    class="text-[10px] font-medium"
                                    :class="
                                        product.stock <= 5
                                            ? 'text-red-500 dark:text-red-400'
                                            : 'text-gray-500 dark:text-gray-400'
                                    "
                                >
                                    {{
                                        product.stock <= 5
                                            ? `Sisa ${parseFloat(
                                                    product.stock
                                                )}`
                                            : `Stok ${parseFloat(
                                                    product.stock
                                                )}`
                                    }}
                                </span>
                            </div>
                            <span v-if="product.total_sold > 0" class="text-[9px] font-bold text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">
                                {{ product.total_sold }} Terjual
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between pt-2 mt-auto border-t border-gray-100 dark:border-gray-700"
                        >
                            <span
                                class="text-sm font-black text-lime-600 dark:text-lime-400"
                            >
                                {{
                                    rp(
                                        product.selling_price ||
                                            product.price
                                    )
                                }}
                            </span>

                            <div
                                v-if="getCartQty(product.id) > 0"
                                class="flex items-center justify-center text-xs font-bold text-white bg-orange-500 rounded-full shadow-md w-7 h-7 animate-bounce-short"
                            >
                                {{ getCartQty(product.id) }}
                            </div>

                            <div
                                v-else
                                class="p-1.5 rounded-lg text-lime-600 bg-lime-50 dark:text-lime-400 dark:bg-lime-500/10"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Separate Action Buttons (Always Visible) -->
                <div class="grid grid-cols-2 border-t border-gray-100 dark:border-gray-700 divide-x divide-gray-100 dark:divide-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <button
                        @click.stop="$emit('openDetail', product)"
                        class="py-2.5 flex items-center justify-center gap-1.5 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        Detail
                    </button>
                    <button
                        @click.stop="$emit('toggleCompare', product)"
                        class="py-2.5 flex items-center justify-center gap-1.5 text-xs font-semibold transition"
                        :class="isInCompare(product.id) 
                            ? 'bg-lime-100 text-lime-700 dark:bg-lime-900/30 dark:text-lime-400' 
                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        {{ isInCompare(product.id) ? 'Batal' : 'Bandingkan' }}
                    </button>
                </div>
            </div>
        </div>
        <div
            v-if="isFetching"
            class="py-10 text-center"
        >
            <div
                class="w-8 h-8 mx-auto mb-2 border-b-2 rounded-full animate-spin border-lime-500"
            ></div>
            <span class="text-xs text-gray-400"
                >Memuat database produk...</span
            >
        </div>
        <div
            v-else-if="products.length === 0"
            class="flex flex-col items-center justify-center px-4 py-16 text-center animate-fade-in"
        >
            <div class="relative mb-6">
                <div
                    class="flex items-center justify-center w-24 h-24 rounded-full shadow-inner bg-gray-50 dark:bg-gray-800"
                >
                    <svg
                        class="w-12 h-12 text-gray-300 dark:text-gray-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>

                <div
                    class="absolute -top-2 -right-2 bg-white dark:bg-gray-700 rounded-full p-1.5 shadow-md border border-gray-100 dark:border-gray-600"
                >
                    <div
                        class="p-1 rounded-full bg-lime-100 dark:bg-lime-900/50"
                    >
                        <svg
                            class="w-5 h-5 text-lime-500 dark:text-lime-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <h3
                class="mb-2 text-lg font-bold text-gray-900 dark:text-white"
            >
                Produk tidak ditemukan
            </h3>
            <p
                class="max-w-xs mx-auto mb-6 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
            >
                Kami tidak dapat menemukan produk dengan kata kunci atau
                kategori tersebut.
            </p>
        </div>

        <div
            v-if="
                products.length > 0 &&
                allProductsCount > products.length
            "
            class="py-4 text-center text-[10px] text-gray-400"
        >
            Scroll untuk memuat lebih banyak...
        </div>
    </div>
</template>
