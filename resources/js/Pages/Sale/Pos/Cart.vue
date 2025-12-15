<script setup>
import MoneyInput from "../partials/MoneyInput.vue";
import { ref } from "vue";
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
const emit = defineEmits(["showDesktop", "showBayar"]);
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

    startScanner,
    removeItem,
    updateQty,
    rp,
} = props.reprops;
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
            <div class="flex items-center gap-2">
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
            class="px-5 py-3 bg-white border-b shrink-0 dark:border-gray-700 dark:bg-gray-800"
        >
            <div v-if="!selectedMember" class="relative">
                <input
                    v-model="memberSearch"
                    type="text"
                    placeholder="Cari / Scan Member..."
                    class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 pr-10 bg-gray-50 dark:bg-gray-900 dark:text-white focus:ring-lime-500 transition"
                />

                <button
                    @click="startScanner(member)"
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

            <div
                v-for="(item, index) in form.items"
                :key="index"
                class="relative overflow-hidden transition-colors duration-200 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700 group hover:border-lime-300"
            >
                <div
                    class="flex items-start justify-between p-3 pb-2 border-b border-gray-50 dark:border-gray-700/50"
                >
                    <div class="pr-2">
                        <h4
                            class="text-sm font-bold leading-tight text-gray-800 dark:text-gray-100"
                        >
                            {{ item.name }}
                        </h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                class="text-[10px] font-mono text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded tracking-wide"
                            >
                                {{ item.code }}
                            </span>
                            <span
                                v-if="item.unit"
                                class="text-[10px] font-bold text-gray-400"
                            >
                                / {{ item.unit.name }}
                            </span>
                            <span
                                class="text-[10px] font-bold"
                                :class="
                                    item.stock_max <= 5
                                        ? 'text-red-500'
                                        : 'text-green-500'
                                "
                                >( Stok : {{ item.stock_max }} )</span
                            >
                        </div>
                    </div>
                    <button
                        @click="removeItem(index)"
                        class="text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg p-1.5 transition"
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
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            ></path>
                        </svg>
                    </button>
                </div>

                <div
                    class="grid grid-cols-12 gap-0 divide-x divide-gray-100 dark:divide-gray-700 bg-gray-50/50 dark:bg-gray-900/30"
                >
                    <div class="flex flex-col justify-center col-span-4 p-2">
                        <label
                            class="text-[9px] font-bold text-gray-400 uppercase mb-0.5 ml-1"
                            >Harga @</label
                        >
                        <div class="relative group/price">
                            <input
                                type="number"
                                v-model="item.selling_price"
                                @input="recalcFromPrice(item)"
                                class="w-full py-1 pl-2 pr-1 text-xs font-bold text-gray-600 bg-transparent border-none rounded focus:ring-1 focus:ring-lime-500 dark:text-gray-300 tabular-nums"
                                @focus="$event.target.select()"
                            />
                            <div
                                class="absolute right-1 top-1.5 opacity-0 group-hover/price:opacity-100 transition-opacity"
                            >
                                <svg
                                    class="w-3 h-3 text-gray-300"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="flex flex-col items-center justify-center col-span-4 p-2"
                    >
                        <label
                            class="text-[9px] font-bold text-gray-400 uppercase mb-0.5"
                            >Qty</label
                        >
                        <div
                            class="flex items-center w-full overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm h-7 dark:bg-gray-800 dark:border-gray-600"
                        >
                            <button
                                @click="updateQty(index, -1)"
                                class="flex items-center justify-center w-8 h-full text-gray-400 transition hover:text-red-500 hover:bg-red-50 active:scale-95"
                            >
                                -
                            </button>
                            <input
                                type="number"
                                v-model="item.quantity"
                                @input="recalcFromQty(item)"
                                :step="item.unit?.is_decimal ? '0.001' : '1'"
                                class="z-10 w-full h-full p-0 text-xs font-black text-center text-gray-800 border-none focus:ring-0 dark:text-white dark:bg-gray-800 tabular-nums"
                                @focus="$event.target.select()"
                            />
                            <button
                                @click="updateQty(index, 1)"
                                class="flex items-center justify-center w-8 h-full text-gray-400 transition hover:text-lime-600 hover:bg-lime-50 active:scale-95"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <div
                        class="relative flex flex-col justify-center col-span-4 p-2"
                    >
                        <label
                            class="text-[9px] font-bold text-gray-400 uppercase mb-0.5 text-right mr-1"
                            >Subtotal</label
                        >

                        <input
                            type="number"
                            v-model="item.subtotal"
                            @change="recalcFromSubtotal(item)"
                            class="w-full px-2 py-1 text-xs font-black text-right transition-all border rounded focus:ring-2 focus:ring-lime-500 tabular-nums"
                            :class="[
                                item.unit?.is_decimal
                                    ? 'bg-lime-50 border-lime-200 text-lime-700 placeholder-lime-300 dark:bg-lime-900/20 dark:border-lime-800 dark:text-lime-400'
                                    : 'bg-white border-gray-200 text-gray-800 focus:bg-yellow-50 focus:border-yellow-400 dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                            ]"
                            @focus="$event.target.select()"
                        />

                        <div
                            v-if="item.unit?.is_decimal"
                            class="absolute top-2 left-2 text-lime-500"
                            title="Aman: Ubah nominal, Qty menyesuaikan"
                        >
                            <svg
                                class="w-3 h-3"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                        <div
                            v-else
                            class="absolute transition-opacity opacity-0 pointer-events-none top-9 right-2 group-hover:opacity-100"
                        >
                            <span
                                class="text-[8px] bg-yellow-100 text-yellow-800 px-1 rounded border border-yellow-200 shadow-sm"
                            >
                                ⚠️ Ubah = Ganti Harga
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="shrink-0 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] z-20 relative"
        >
            <div class="absolute left-0 z-10 flex justify-center w-full -top-3">
                <button
                    @click="showPaymentOptions = !showPaymentOptions"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-full px-4 py-0.5 text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-lime-600 hover:border-lime-500 shadow-sm transition-all flex items-center gap-1"
                >
                    {{ showPaymentOptions ? "Tutup" : "Opsi" }}
                    <svg
                        :class="{ 'rotate-180': showPaymentOptions }"
                        class="w-3 h-3 transition-transform"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M19 9l-7 7-7-7"
                        ></path>
                    </svg>
                </button>
            </div>

            <div class="px-5 pt-3 pb-5">
                <div
                    v-show="showPaymentOptions"
                    class="mb-4 space-y-4 animate-fade-in-down"
                >
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
                                        /* Tambahan: w-full agar memenuhi kotak grid, justify-center agar teks di tengah */
                                        'w-full px-3 py-2 rounded-lg text-xs uppercase border transition-all flex items-center justify-center gap-2',
                                    ]"
                                >
                                    <span v-if="method === 'cash'">💵</span>
                                    <!-- <span v-else-if="method === 'transfer'"
                                        >🏦</span
                                    > -->
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
                                    type="number"
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

                    <div>
                        <div
                            class="flex gap-2 pb-1 overflow-x-auto scrollbar-hide"
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
                </div>
                <div class="flex flex-col gap-3">
                    <div
                        class="flex items-end justify-between p-3 border border-gray-100 md:p-4 bg-gray-50 dark:bg-gray-900/50 rounded-2xl dark:border-gray-700"
                    >
                        <div class="flex flex-col flex-1 min-w-0 pr-2">
                            <span
                                class="text-[9px] md:text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5 md:mb-1"
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

                            <span
                                v-if="subTotal !== grandTotal"
                                class="text-[9px] md:text-[10px] text-gray-400 mt-1 truncate"
                            >
                                Subtotal:
                                <span class="line-through">{{
                                    rp(subTotal)
                                }}</span>
                            </span>
                        </div>

                        <div class="w-32 sm:w-40 shrink-0">
                            <label
                                class="text-[9px] md:text-[10px] font-bold text-right text-gray-400 uppercase block mb-1 truncate"
                            >
                                Bayar ({{ form.payment_method }})
                            </label>

                            <div class="relative">
                                <MoneyInput
                                    v-model="form.payment_amount"
                                    placeholder="0"
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

                    <div class="flex items-center gap-2 md:gap-3">
                        <div
                            v-if="form.payment_amount > 0"
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
                                {{
                                    isPaymentSufficient ? "Kembalian" : "Kurang"
                                }}
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

                        <button
                            @click="$emit('showBayar')"
                            :disabled="
                                !form.items.length ||
                                (!isPaymentSufficient &&
                                    form.payment_method === 'cash')
                            "
                            class="flex-1 h-[54px] sm:h-[58px] text-white font-bold rounded-xl text-xs sm:text-sm transition-all active:scale-95 flex justify-center items-center gap-2 group shadow-lg"
                            :class="[
                                !form.items.length
                                    ? 'bg-gray-300 opacity-50 cursor-not-allowed shadow-none'
                                    : !isPaymentSufficient &&
                                      form.payment_method === 'cash'
                                    ? 'bg-gray-800 dark:bg-gray-600 opacity-90 cursor-not-allowed'
                                    : 'bg-lime-500 hover:bg-lime-600 shadow-lime-500/30',
                            ]"
                        >
                            <span v-if="!form.items.length"
                                >KERANJANG KOSONG</span
                            >
                            <span
                                v-else-if="
                                    !isPaymentSufficient &&
                                    form.payment_method === 'cash'
                                "
                                >UANG KURANG</span
                            >
                            <span v-else class="text-sm sm:text-base"
                                >PROSES</span
                            >

                            <svg
                                v-if="
                                    form.items.length &&
                                    (isPaymentSufficient ||
                                        form.payment_method !== 'cash')
                                "
                                class="w-5 h-5 transition-transform group-hover:translate-x-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
