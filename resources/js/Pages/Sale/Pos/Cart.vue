<script setup>
import InputRupiah from "@/Components/InputRupiah.vue";
import MoneyInput from "../partials/MoneyInput.vue";
import { ref, computed } from "vue";
const props = defineProps({
    showMobileCart: {
        type: Boolean,
        default: false,
    },
    reprops: {
        type: Object,
        required: true,
    },
});
const showPaymentOptions = ref(false);
const cartStep = ref(1); // 1 = Edit, 2 = Payment
const emit = defineEmits(["showDesktop", "showBayar", "openScanMember"]);
const {
    form,
    // 3. MEMBER / PELANGGAN
    memberSearch, // v-model input cari member
    memberSearchResults, // Array hasil pencarian member
    selectedMember, // Object member terpilih
    selectMember, // Aksi pilih member
    removeMember, // Aksi hapus member terpilih

    // 5. CALCULATIONS (Untuk di Footer/Cart)
    subTotal,
    discountAmount,
    moneySuggestions,
    grandTotal,
    changeAmount,
    isPaymentSufficient,

    // 6. PAYMENT ACTIONS
    recalcFromQty,
    recalcFromSubtotal,
    recalcFromPrice,
    handleMoneyClick,
    resetPayment,

    removeItem,
    updateQty,
    addItem, // Import addItem
    serviceProducts, // Import serviceProducts
    rp,
} = props.reprops;

const resetStep = () => {
    cartStep.value = 1;
};
const cartServices = computed(() => {
    return form.items.filter(item => ['Jasa', 'Layanan'].includes(item.category?.name));
});

const cartProducts = computed(() => {
    return form.items.filter(item => !['Jasa', 'Layanan'].includes(item.category?.name));
});
defineExpose({
    resetStep,
});
</script>
<template>
    <div
        :class="[
            'lg:static lg:w-[420px] lg:border-l lg:border-gray-200 dark:lg:border-gray-700',
            'fixed inset-0 z-40 bg-white dark:bg-gray-800 transition-transform duration-300 ease-in-out flex flex-col h-full',
            showMobileCart
                ? 'translate-y-0'
                : 'translate-y-full lg:translate-y-0',
        ]"
    >
        <div
            class="flex items-center justify-between px-5 py-4 border-b shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 lg:bg-white lg:dark:bg-gray-800"
        >
            <!-- Layer 1 Header -->
            <div v-if="cartStep === 1" class="flex items-center gap-2">
                <h2 class="text-lg font-black text-gray-800 dark:text-white">
                    <span class="text-lime-500">🛒</span> Keranjang
                </h2>
                <button
                    v-if="form.items.length"
                    @click="form.items = []"
                    class="text-[10px] text-red-500 font-bold bg-red-50 px-2 py-1 rounded ml-2 hover:bg-red-100 transition"
                >
                    Reset
                </button>
            </div>

            <!-- Layer 2 Header -->
            <div v-else class="flex items-center gap-2">
                <button
                    @click="cartStep = 1"
                    class="p-1 mr-2 text-gray-500 bg-white border rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h2 class="text-lg font-black text-gray-800 dark:text-white">
                    <span class="text-lime-500">💳</span> Pembayaran
                </h2>
            </div>

            <button
                @click="$emit('showDesktop')"
                class="p-2 text-gray-500 bg-gray-100 rounded-full lg:hidden dark:bg-gray-700 dark:text-gray-200"
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
                        d="M6 18L18 6M6 6l12 12"
                    ></path>
                </svg>
            </button>
        </div>

        <div
            v-if="cartStep === 2"
            class="px-5 py-3 bg-white border-b shrink-0 dark:border-gray-700 dark:bg-gray-800"
        >
            <div v-if="!selectedMember" class="relative">
                <input
                    v-model="memberSearch"
                    @focus="$event.target.select()"
                    type="search"
                    enterkeyhint="search"
                    placeholder="Cari / Scan Member..."
                    class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 pr-10 bg-gray-50 dark:bg-gray-900 dark:text-white focus:ring-lime-500 transition"
                />

                <button
                    @click="$emit('openScanMember')"
                    class="absolute right-1.5 top-1.5 p-1 bg-white dark:bg-gray-700 rounded-lg shadow-sm text-gray-500 dark:text-gray-300 hover:text-lime-600 border border-gray-200 dark:border-gray-600"
                    title="Scan Kartu Member"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                        ></path>
                    </svg>
                </button>
                <div
                    v-if="memberSearchResults.length"
                    class="absolute left-0 z-50 w-full mt-2 overflow-hidden bg-white border shadow-xl top-full dark:bg-gray-800 dark:border-gray-600 rounded-xl"
                >
                    <div
                        v-for="c in memberSearchResults"
                        :key="c.id"
                        @click="selectMember(c)"
                        class="p-3 text-xs text-gray-800 border-b cursor-pointer hover:bg-lime-50 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-200 last:border-0"
                    >
                        <b>{{ c.name }}</b>
                        <span class="text-gray-400">({{ c.phone }})</span>
                    </div>
                </div>
            </div>
            <div
                v-else
                class="flex justify-between bg-lime-50 dark:bg-gray-700 p-2.5 rounded-xl border border-lime-200 dark:border-gray-600"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full shadow-sm bg-lime-500"
                    >
                        {{ selectedMember.name[0] }}
                    </div>
                    <div>
                        <div
                            class="text-xs font-bold text-gray-800 dark:text-white"
                        >
                            {{ selectedMember.name }}
                        </div>
                        <div
                            class="text-[10px] text-gray-500 dark:text-gray-300 tracking-wide"
                        >
                            {{ selectedMember.member_code }}
                        </div>
                    </div>
                </div>
                <button
                    @click="selectMember(null)"
                    class="p-1 text-gray-400 hover:text-red-500"
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
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div>
        </div>

        <div
            v-if="serviceProducts?.length > 0 && cartStep === 1"
            class="px-5 py-2 bg-white border-b shrink-0 dark:bg-gray-800 dark:border-gray-700"
        >
            <h3 class="text-[10px] font-bold text-gray-400 uppercase mb-2">
                Layanan Cepat
            </h3>
            <div class="flex gap-2 pb-2 overflow-x-auto scrollbar-hide">
                <button
                    v-for="srv in serviceProducts"
                    :key="srv.id"
                    @click="addItem(srv)"
                    class="flex-shrink-0 px-4 py-2 bg-lime-50 text-lime-700 border border-lime-200 rounded-lg text-xs font-bold whitespace-nowrap active:scale-95 hover:bg-lime-100 transition dark:bg-lime-900/30 dark:text-lime-400 dark:border-lime-800"
                >
                    + {{ srv.name }}
                </button>
            </div>
        </div>

        <div
            class="flex-1 px-4 py-2 space-y-3 overflow-y-auto bg-gray-50 custom-scroll dark:bg-gray-900"
        >
            <div
                v-if="form.items.length === 0"
                class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-600 opacity-70"
            >
                <div
                    class="p-6 mb-4 bg-white rounded-full shadow-sm dark:bg-gray-800"
                >
                    <span class="text-5xl">🛍️</span>
                </div>
                <span class="text-sm font-bold tracking-wide uppercase"
                    >Keranjang Kosong</span
                >
                <span class="mt-1 text-xs font-normal"
                    >Scan barcode atau cari produk...</span
                >
            </div>

            <!-- LAYER 1: EDIT MODE -->
            <div v-if="cartStep === 1" class="space-y-4">
                
                <!-- SECTION: SERVICES (JASA) -->
                <div v-if="cartServices.length > 0" class="space-y-2">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1">Jasa & Layanan</h3>
                    <div
                        v-for="(item, index) in form.items"
                        :key="'svc-' + index"
                        v-show="['Jasa', 'Layanan'].includes(item.category?.name)"
                        class="flex flex-col p-3 bg-white border border-lime-200 shadow-sm dark:bg-gray-800 rounded-xl dark:border-lime-900/50 relative overflow-hidden"
                    >
                        <div class="absolute top-0 left-0 w-1 h-full bg-lime-500"></div>
                        
                        <div class="flex items-start justify-between gap-3 pl-2">
                            <!-- Name -->
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-gray-800 dark:text-white leading-tight">
                                    {{ item.name }}
                                </h4>
                                <div class="mt-2 flex items-center gap-2">
                                     <!-- Toggle Button -->
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
                                            ? 'bg-lime-100 text-lime-700 border-lime-200' 
                                            : 'bg-gray-100 text-gray-500 border-gray-200 hover:bg-gray-200'"
                                     >
                                        {{ item.selling_price == 0 ? '💵 Bayar' : '🎁 Gratis' }}
                                     </button>

                                    <!-- Price Input -->
                                    <div v-if="item.selling_price != 0" class="relative w-32">
                                        <InputRupiah
                                            v-model="item.selling_price"
                                            @input="recalcFromPrice(item)"
                                            class="w-full py-1 text-right text-sm font-bold border-gray-200 rounded-lg focus:ring-lime-500 focus:border-lime-500 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            placeholder="0"
                                            @focus="$event.target.select()"
                                        />
                                    </div>
                                    <span v-else class="text-xs font-bold text-lime-600 ml-1">Gratis</span>
                                </div>
                            </div>

                            <!-- Delete Action -->
                            <button
                                @click="removeItem(form.items.indexOf(item))"
                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                                title="Hapus Jasa"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- SECTION: PRODUCTS -->
                <div v-if="cartProducts.length > 0" class="space-y-2">
                     <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1">Produk</h3>
                    <div
                        v-for="(item, index) in form.items"
                        :key="'prod-' + index"
                        v-show="!['Jasa', 'Layanan'].includes(item.category?.name)"
                        class="relative flex gap-3 p-3 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700 group hover:border-lime-300"
                    >
                        <!-- 1. Product Image -->
                        <div class="shrink-0">
                            <img
                                v-if="item.image_url"
                                :src="item.image_url"
                                alt="Product"
                                class="w-12 h-12 object-cover rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50"
                            />
                            <div
                                v-else
                                class="w-12 h-12 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>

                        <!-- 2. Product Details & Controls -->
                        <div class="flex-1 min-w-0 flex flex-col justify-between">
                            <!-- Top: Name & Remove -->
                            <div class="flex justify-between items-start gap-1">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800 dark:text-gray-100 leading-tight line-clamp-2">
                                        {{ item.name }}
                                    </h4>
                                    <!-- Code/SKU & Attributes -->
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-[10px] text-gray-500">
                                        <span v-if="item.code" class="font-mono bg-gray-100 dark:bg-gray-700 px-1 rounded text-gray-600 dark:text-gray-300">{{ item.code }}</span>
                                        <span v-if="item.size" class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">{{ item.size?.name }}</span>
                                        <span v-if="item.unit">{{ item.unit?.name }}</span>
                                    </div>
                                </div>
                                <button
                                    @click="removeItem(form.items.indexOf(item))"
                                    class="shrink-0 p-1.5 -mr-1 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>

                            <!-- Bottom: Price & Qty -->
                            <div class="flex items-end justify-between gap-2 mt-2">
                                 <!-- Price Input -->
                                 <div class="flex-1">
                                    <InputRupiah
                                        v-model="item.selling_price"
                                        @input="recalcFromPrice(item)"
                                        class="w-full !py-0 !px-0 bg-transparent border-0 border-b border-gray-200 dark:border-gray-600 text-sm font-bold text-lime-600 focus:ring-0 focus:border-lime-500 p-0"
                                    />
                                 </div>

                                 <!-- Qty Control -->
                                 <div class="flex items-center border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shrink-0 h-8 bg-white dark:bg-gray-800">
                                    <button @click="updateQty(form.items.indexOf(item), -1)" class="w-8 h-full flex items-center justify-center hover:bg-red-50 hover:text-red-500 active:bg-gray-100 transition text-lg font-bold">
                                        -
                                    </button>
                                    <input
                                        type="number"
                                        v-model="item.quantity"
                                        @input="recalcFromQty(item)"
                                        :step="item.unit?.is_decimal ? '0.001' : '1'"
                                        class="w-10 h-full text-center text-sm font-bold border-none bg-transparent p-0 focus:ring-0 appearance-none"
                                        @focus="$event.target.select()"
                                    />
                                    <button @click="updateQty(form.items.indexOf(item), 1)" class="w-8 h-full flex items-center justify-center hover:bg-lime-50 hover:text-lime-600 active:bg-gray-100 transition text-lg font-bold">
                                        +
                                    </button>
                                 </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LAYER 2: READ ONLY SUMMARY -->
            <div v-else class="space-y-2">
                <div v-for="(item, index) in form.items" :key="index" class="flex justify-between items-center p-3 bg-white border border-gray-100 rounded-xl dark:bg-gray-800 dark:border-gray-700">
                     <div class="flex items-center gap-3">
                         <!-- Image Layer 2 -->
                        <div class="shrink-0">
                            <img
                                v-if="item.image_url"
                                :src="item.image_url"
                                alt="Product"
                                class="w-10 h-10 object-cover rounded-md border border-gray-100 dark:border-gray-700 bg-gray-50"
                            />
                            <div
                                v-else
                                class="w-10 h-10 flex items-center justify-center rounded-md bg-gray-100 dark:bg-gray-700 text-gray-400"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>

                         <div class="flex flex-col">
                             <span class="text-xs font-bold text-gray-800 dark:text-white line-clamp-1">{{ item.name }}</span>
                             <div class="text-[10px] text-gray-500">
                                 <span v-if="['Jasa', 'Layanan'].includes(item.category?.name)">Jasa</span>
                                 <span v-else>{{ item.quantity }} x {{ rp(item.selling_price) }}</span>
                             </div>
                         </div>
                     </div>
                     <div class="text-xs font-black text-lime-600">
                         {{ rp(item.subtotal) }}
                     </div>
                </div>
            </div>
        </div>

        <!-- FOOTER: TOTAL & ACTIONS -->
        <div
            class="shrink-0 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] z-20 relative"
        >
            <!-- LAYER 2: PAYMENT OPTIONS (Discount, Notes, Suggestions) -->
            <div v-if="cartStep === 2" class="px-5 pt-3 pb-2 transition-all">
                <div class="mb-4 space-y-4 animate-fade-in-down">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block"
                                >Pembayaran</label
                            >
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="method in [
                                        'cash',
                                        'qris',
                                        // 'transfer',
                                    ]"
                                    :key="method"
                                    @click="form.payment_method = method"
                                    :class="[
                                        form.payment_method === method
                                            ? 'bg-lime-100 text-lime-700 border-lime-300 font-bold'
                                            : 'bg-gray-50 text-gray-500 border-gray-100 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300',
                                        'w-full px-3 py-2 rounded-lg text-xs uppercase border transition-all flex items-center justify-center gap-2',
                                    ]"
                                >
                                    <span v-if="method === 'cash'">💵</span>
                                    <span v-else>📱</span>
                                    {{ method }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block"
                                >Diskon Global</label
                            >
                            <div class="flex mb-2">
                                <button
                                    @click="form.discount_type = 'fixed'"
                                    :class="
                                        form.discount_type === 'fixed'
                                            ? 'bg-lime-500 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 dark:text-gray-300 text-gray-500'
                                    "
                                    class="px-2 py-1 text-xs font-bold border border-r-0 border-gray-200 rounded-l-lg dark:border-gray-600"
                                >
                                    Rp
                                </button>
                                <button
                                    @click="form.discount_type = 'percent'"
                                    :class="
                                        form.discount_type === 'percent'
                                            ? 'bg-lime-500 text-white'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'
                                    "
                                    class="px-2 py-1 text-xs font-bold border border-l-0 border-gray-200 rounded-r-lg dark:border-gray-600"
                                >
                                    %
                                </button>
                                <input
                                    v-model="form.discount_value"
                                    type="text"
                                    inputmode="numeric"
                                    @focus="$event.target.select()"
                                    placeholder="0"
                                    class="w-full px-2 py-1 ml-2 text-sm font-bold bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-300 focus:ring-lime-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="relative">
                            <input
                                v-model="form.notes"
                                type="text"
                                placeholder="Catatan transaksi (opsional)..."
                                class="w-full py-2 pl-8 pr-3 text-xs transition-colors border border-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-300 bg-gray-50 focus:bg-white focus:ring-lime-500"
                            />
                            <span class="absolute left-2.5 top-2 text-gray-400"
                                >📝</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Money Suggestions -->
                <div
                    v-if="moneySuggestions.length > 0"
                    class="flex gap-2 py-3 overflow-x-auto scrollbar-hide"
                >
                    <button
                        @click="resetPayment"
                        class="px-3 py-1.5 bg-red-50 text-red-500 border border-red-100 rounded-lg text-xs font-bold whitespace-nowrap active:scale-95"
                    >
                        Reset
                    </button>
                    <button
                        v-for="suggestion in moneySuggestions"
                        :key="suggestion.label"
                        @click="handleMoneyClick(suggestion)"
                        class="px-3 py-1.5 bg-white border dark:bg-gray-700 dark:text-gray-300 border-gray-200 text-gray-600 rounded-lg text-xs font-bold whitespace-nowrap shadow-sm hover:border-lime-500 hover:text-lime-600 active:scale-95 transition"
                    >
                        {{ suggestion.label }}
                    </button>
                </div>
            </div>

            <!-- BOTTOM BAR -->
            <div class="px-5 pb-5 pt-3">
                <!-- TOTAL DISPLAY (Shared but styled differently) -->
                <div
                    class="flex items-end justify-between p-3 border border-lime-500 md:p-4 bg-lime-50 dark:bg-gray-900/50 rounded-2xl dark:border-gray-700 mb-3"
                >
                    <div class="flex flex-col flex-1 min-w-0 pr-2">
                        <span
                            class="text-[9px] md:text-[10px] dark:text-lime-400 font-bold text-lime-800 uppercase tracking-widest mb-0.5 md:mb-1"
                        >
                            Total Tagihan
                        </span>

                        <div
                            class="flex flex-wrap items-baseline gap-x-2 gap-y-1"
                        >
                            <span
                                class="text-xl font-black leading-none tracking-tight text-gray-800 truncate sm:text-2xl md:text-3xl dark:text-white"
                            >
                                {{ rp(grandTotal) }}
                            </span>
                             <span
                                v-if="discountAmount > 0"
                                class="text-[9px] md:text-[10px] font-bold text-white bg-green-500 px-1.5 py-0.5 rounded-md animate-pulse whitespace-nowrap"
                            >
                                Hemat {{ rp(discountAmount) }}
                            </span>
                        </div>
                    </div>

                    <!-- PAYMENT INPUT (Only Layer 2) -->
                    <div v-if="cartStep === 2" class="w-32 sm:w-40 shrink-0">
                        <label
                            class="text-[9px] md:text-[10px] font-bold text-right text-lime-800 uppercase block mb-1 truncate dark:text-lime-400"
                        >
                            Bayar ({{ form.payment_method }})
                        </label>

                        <div class="relative">
                            <MoneyInput
                                ref="paymentInputRef"
                                v-model="form.payment_amount"
                                placeholder="0"
                                @focus="$event.target.select()"
                                class="w-full p-0 text-xl font-black text-right placeholder-gray-300 transition-colors bg-transparent border-none sm:text-2xl focus:ring-0"
                                :class="
                                    isPaymentSufficient
                                        ? 'text-lime-600 dark:text-lime-400'
                                        : 'text-red-500'
                                "
                            />
                            <div
                                class="w-full h-1 mt-0.5 md:mt-1 rounded-full transition-colors"
                                :class="
                                    isPaymentSufficient
                                        ? 'bg-lime-500'
                                        : 'bg-red-200'
                                "
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex items-center gap-2 md:gap-3">
                    <!-- Layer 2: Change Display -->
                    <div
                        v-if="cartStep === 2 && form.payment_amount > 0"
                        class="flex flex-col justify-center px-3 md:px-4 py-1 rounded-xl border min-w-[100px] sm:min-w-[120px] transition-colors h-[54px] sm:h-[58px]"
                        :class="
                            isPaymentSufficient
                                ? 'bg-lime-50 border-lime-200'
                                : 'bg-red-50 border-red-200'
                        "
                    >
                        <span
                            class="text-[10px] font-bold uppercase leading-none mb-1"
                            :class="
                                isPaymentSufficient
                                    ? 'text-lime-700'
                                    : 'text-red-500'
                            "
                        >
                            {{ isPaymentSufficient ? "Kembalian" : "Kurang" }}
                        </span>
                        <span
                            class="text-sm font-black leading-none truncate sm:text-lg"
                            :class="
                                isPaymentSufficient
                                    ? 'text-lime-700'
                                    : 'text-red-600'
                            "
                        >
                            {{ rp(Math.abs(changeAmount)) }}
                        </span>
                    </div>

                    <!-- Button Layer 1: Lanjut Pembayaran -->
                    <button
                        v-if="cartStep === 1"
                        @click="cartStep = 2"
                        :disabled="!form.items.length"
                        class="flex-1 h-[54px] sm:h-[58px] text-white font-bold rounded-xl text-xs sm:text-sm transition-all active:scale-95 flex justify-center items-center gap-2 group shadow-lg bg-lime-500 hover:bg-lime-600 shadow-lime-500/30"
                        :class="{'bg-gray-300 opacity-50 cursor-not-allowed shadow-none': !form.items.length}"
                    >
                         <span>{{ !form.items.length ? 'KERANJANG KOSONG' : 'LANJUT PEMBAYARAN' }}</span>
                         <svg v-if="form.items.length" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>

                    <!-- Button Layer 2: Proses Bayar -->
                    <button
                        v-else
                        @click="$emit('showBayar')"
                        :disabled="
                            !form.items.length ||
                            (!isPaymentSufficient && form.payment_method === 'cash')
                        "
                        class="flex-1 h-[54px] sm:h-[58px] text-white font-bold rounded-xl text-xs sm:text-sm transition-all active:scale-95 flex justify-center items-center gap-2 group shadow-lg"
                        :class="[
                            !isPaymentSufficient && form.payment_method === 'cash'
                                ? 'bg-gray-800 dark:bg-gray-600 opacity-90 cursor-not-allowed'
                                : 'bg-lime-500 hover:bg-lime-600 shadow-lime-500/30',
                        ]"
                    >
                         <span v-if="!isPaymentSufficient && form.payment_method === 'cash'">UANG KURANG</span>
                         <span v-else>BAYAR / KONFIRMASI</span>
                         <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
