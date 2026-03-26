<script setup>
import MoneyInput from "../../partials/MoneyInput.vue";
import { computed } from "vue";
import { usePosState } from "@/Composables/POS/usePosState";
import { storeToRefs } from "pinia";

const emit = defineEmits(["showBayar", "openScanMember"]);

const posState = usePosState();
const {
    form,
    activeDraft,
    selectedMember,
    memberSearch,
    memberSearchResults,
    subTotal,
    discountAmount,
    grandTotal,
    changeAmount,
    isPaymentSufficient,
    moneySuggestions,
} = storeToRefs(posState);

const { selectMember, removeMember, handleMoneyClick, resetPayment, rp } = posState;
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto custom-scroll">

            <!-- Customer Placeholder / Stub -->
            <div class="px-5 py-3 border-b dark:border-gray-700 bg-white dark:bg-gray-800">
                <div v-if="!selectedMember" class="relative">
                    <input
                        v-model="memberSearch"
                        @focus="$event.target.select()"
                        type="search"
                        enterkeyhint="search"
                        placeholder="Pilih Customer (Opsional)..."
                        class="w-full text-xs border border-gray-200 dark:border-gray-600 rounded-xl p-2.5 pr-20 bg-gray-50 dark:bg-gray-900 dark:text-white focus:ring-lime-500 transition"
                    />
                    <div class="absolute right-2 top-1.5 flex items-center gap-1">
                        <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[9px] font-bold rounded-md">COMING SOON</span>
                    </div>

                    <!-- Member Search Results Dropdown -->
                    <div
                        v-if="memberSearchResults.length"
                        class="absolute left-0 z-50 w-full mt-1 overflow-hidden bg-white border shadow-xl top-full dark:bg-gray-800 dark:border-gray-600 rounded-xl"
                    >
                        <div
                            v-for="c in memberSearchResults"
                            :key="c.id"
                            @click="selectMember(c)"
                            class="p-3 text-xs text-gray-800 border-b cursor-pointer hover:bg-lime-50 dark:hover:bg-gray-700 dark:border-gray-700 dark:text-gray-200 last:border-0"
                        >
                            <b>{{ c.name }}</b>
                            <span class="text-gray-400 ml-1">({{ c.phone }})</span>
                        </div>
                    </div>
                </div>
                <div v-else class="flex justify-between bg-lime-50 dark:bg-gray-700 p-2.5 rounded-xl border border-lime-200 dark:border-gray-600">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-8 h-8 text-xs font-bold text-white rounded-full shadow-sm bg-lime-500">
                            {{ selectedMember.name[0] }}
                        </div>
                        <div>
                            <div class="text-xs font-bold text-gray-800 dark:text-white">{{ selectedMember.name }}</div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-300 tracking-wide">{{ selectedMember.member_code }}</div>
                        </div>
                    </div>
                    <button @click="removeMember" class="p-1 text-gray-400 hover:text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>

            <!-- Order Summary (Read-only items) -->
            <div class="px-4 py-3 space-y-2">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1 mb-1">Ringkasan Pesanan</h3>
                <div
                    v-for="(item, index) in activeDraft.cart_items"
                    :key="index"
                    class="flex justify-between items-center p-2.5 bg-white border border-gray-100 rounded-xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="shrink-0">
                            <img v-if="item.image_url" :src="item.image_url" alt="" class="w-9 h-9 object-cover rounded-lg border border-gray-100 dark:border-gray-700 bg-gray-50" />
                            <div v-else class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-gray-800 dark:text-white line-clamp-1">{{ item.name }}</span>
                            <div class="text-[10px] text-gray-500">
                                <span v-if="['Jasa', 'Layanan'].includes(item.category?.name)">Jasa</span>
                                <span v-else>{{ item.quantity }} × {{ rp(item.selling_price) }}</span>
                            </div>
                        </div>
                    </div>
                    <div :class="activeDraft.mode === 'bengkel' ? 'text-blue-600 dark:text-blue-400' : 'text-lime-600 dark:text-lime-400'" class="text-xs font-black shrink-0 ml-2">{{ rp(item.subtotal) }}</div>
                </div>
            </div>

            
        </div>
        <!-- Sticky Footer: Total + CTA -->
        <div class="shrink-0 bg-white dark:bg-gray-800 border-t dark:border-gray-700 shadow-[0_-5px_20px_rgba(0,0,0,0.05)] z-20 px-5 pb-5 pt-3">
            <!-- Payment Options -->
                        <div class="px-2 py-3 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Payment Method -->
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block">Pembayaran</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <button
                                            v-for="method in ['cash', 'qris']"
                                            :key="method"
                                            :disabled="method !== 'cash'"
                                            @click="form.payment_method = method"
                                            :class="[
                                                form.payment_method === method
                                                    ? activeDraft.mode === 'bengkel' ? 'bg-blue-100 text-blue-700 border-blue-300 font-bold dark:bg-blue-900/40 dark:text-blue-400 dark:border-blue-700' : 'bg-lime-100 text-lime-700 border-lime-300 font-bold dark:bg-lime-900/40 dark:text-lime-400 dark:border-lime-700'
                                                    : 'bg-gray-50 text-gray-500 border-gray-100 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
                                                'w-full px-3 py-2 rounded-lg text-xs uppercase border transition-all flex items-center justify-center gap-1.5',
                                            ]"
                                        >
                                            <!-- <span v-if="method === 'cash'">💵</span>
                                            <span v-else>📱</span> -->
                                            {{ method }}
                                        </button>
                                    </div>
                                </div>
            
                                <!-- Discount -->
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase mb-1.5 block">Diskon Global</label>
                                    <div class="flex mb-1.5">
                                        <button
                                            @click="form.discount_type = 'fixed'"
                                            :class="form.discount_type === 'fixed' ? activeDraft.mode === 'bengkel' ? 'bg-blue-500 text-white' : 'bg-lime-500 text-white' : 'bg-gray-100 dark:bg-gray-700 dark:text-gray-300 text-gray-500'"
                                            class="px-2 py-1 text-xs font-bold border border-r-0 border-gray-200 rounded-l-lg dark:border-gray-600"
                                        >Rp</button>
                                        <button
                                            @click="form.discount_type = 'percent'"
                                            :class="form.discount_type === 'percent' ? activeDraft.mode === 'bengkel' ? 'bg-blue-500 text-white' : 'bg-lime-500 text-white' : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300'"
                                            class="px-2 py-1 text-xs font-bold border border-l-0 border-gray-200 rounded-r-lg dark:border-gray-600"
                                        >%</button>
                                        <input
                                            v-model="form.discount_value"
                                            type="text"
                                            inputmode="numeric"
                                            @focus="$event.target.select()"
                                            placeholder="0"
                                            :class="activeDraft.mode === 'bengkel' ? 'focus:ring-blue-500' : 'focus:ring-lime-500'"
                                            class="w-full px-2 py-1 ml-2 text-sm font-bold bg-white border border-gray-200 rounded-lg dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600"
                                        />
                                    </div>
                                </div>
                            </div>
            
                            <!-- Notes -->
                            <div class="relative">
                                <input
                                    v-model="form.notes"
                                    type="text"
                                    placeholder="Catatan transaksi (opsional)..."
                                    :class="activeDraft.mode === 'bengkel' ? 'focus:ring-blue-500' : 'focus:ring-lime-500'"
                                    class="w-full py-2 pl-8 pr-3 text-xs transition-colors border border-gray-200 rounded-xl dark:bg-gray-700 dark:text-gray-300 bg-gray-50 focus:bg-white  dark:border-gray-600"
                                />
                                <span class="absolute left-2.5 top-2 text-gray-400">📝</span>
                            </div>
            
                            <!-- Money Suggestions -->
                            <div v-if="moneySuggestions.length > 0" class="flex gap-2 overflow-x-auto scrollbar-hide pb-1">
                                <button
                                    @click="resetPayment"
                                    class="px-3 py-1.5 bg-red-50 text-red-500 border border-red-100 rounded-lg text-xs font-bold whitespace-nowrap active:scale-95 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400"
                                >Reset</button>
                                <button
                                    v-for="suggestion in moneySuggestions"
                                    :key="suggestion.label"
                                    @click="handleMoneyClick(suggestion)"
                                    :class="activeDraft.mode === 'bengkel' ? 'hover:border-blue-500 hover:text-blue-600' : 'hover:border-lime-500 hover:text-lime-600'"
                                    class="px-3 py-1.5 bg-white border dark:bg-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600 text-gray-600 rounded-lg text-xs font-bold whitespace-nowrap shadow-sm active:scale-95 transition"
                                >{{ suggestion.label }}</button>
                            </div>
                        </div>
            <!-- Total Display -->
            <div :class="activeDraft.mode === 'bengkel' ? 'border border-blue-500 bg-blue-50 dark:bg-gray-900/50 rounded-2xl dark:border-gray-700' : 'border border-lime-500 bg-lime-50 dark:bg-gray-900/50 rounded-2xl dark:border-gray-700'" class="flex items-end justify-between p-3 mb-3">
                <div class="flex flex-col flex-1 min-w-0 pr-2">
                    <span :class="activeDraft.mode === 'bengkel' ? 'text-blue-800 dark:text-blue-400' : 'text-lime-800 dark:text-lime-400'" class="text-[9px] font-bold uppercase tracking-widest mb-0.5">Total Tagihan</span>
                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                        <span :class="activeDraft.mode === 'bengkel' ? 'text-blue-800 dark:text-blue-400' : 'text-lime-800 dark:text-lime-400'" class="text-xl font-black leading-none tracking-tight truncate sm:text-2xl dark:text-white">{{ rp(grandTotal) }}</span>
                        <span v-if="discountAmount > 0" :class="activeDraft.mode === 'bengkel' ? 'text-blue-800 dark:text-blue-400' : 'text-lime-800 dark:text-lime-400'" class="text-[9px] font-bold text-white bg-green-500 px-1.5 py-0.5 rounded-md animate-pulse whitespace-nowrap">
                            Hemat {{ rp(discountAmount) }}
                        </span>
                    </div>
                </div>
                <!-- Payment Input -->
                <div class="w-32 sm:w-40 shrink-0">
                    <label :class="activeDraft.mode === 'bengkel' ? 'text-blue-800 dark:text-blue-400' : 'text-lime-800 dark:text-lime-400'" class="text-[9px] font-bold text-right uppercase block mb-1 truncate">
                        Bayar ({{ form.payment_method }})
                    </label>
                    <div class="relative">
                        <MoneyInput
                            v-model="form.payment_amount"
                            placeholder="0"
                            @focus="$event.target.select()"
                            class="w-full p-0 text-xl font-black text-right placeholder-gray-300 transition-colors bg-transparent border-none sm:text-2xl focus:ring-0"
                            :class="isPaymentSufficient ? activeDraft.mode === 'bengkel' ? 'text-blue-600 dark:text-blue-400' : 'text-lime-600 dark:text-lime-400' : 'text-red-500'"
                        />
                        <div class="w-full h-1 mt-0.5 rounded-full transition-colors" :class="isPaymentSufficient ? activeDraft.mode === 'bengkel' ? 'bg-blue-500' : 'bg-lime-500' : 'bg-red-200'"></div>
                    </div>
                </div>
            </div>

            <!-- CTA Row -->
            <div class="flex items-center gap-2">
                <!-- Change Display -->
                <div
                    v-if="form.payment_amount > 0"
                    class="flex flex-col justify-center px-3 py-1 rounded-xl border min-w-[100px] transition-colors h-[52px]"
                    :class="isPaymentSufficient ? activeDraft.mode === 'bengkel' ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'bg-lime-50 border-lime-200 dark:bg-lime-900/20 dark:border-lime-800' : 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800'"
                >
                    <span class="text-[10px] font-bold uppercase leading-none mb-1" :class="isPaymentSufficient ? activeDraft.mode === 'bengkel' ? 'text-blue-700 dark:text-blue-400' : 'text-lime-700 dark:text-lime-400' : 'text-red-500'">
                        {{ isPaymentSufficient ? 'Kembalian' : 'Kurang' }}
                    </span>
                    <span class="text-sm font-black leading-none truncate sm:text-lg" :class="isPaymentSufficient ? activeDraft.mode === 'bengkel' ? 'text-blue-700 dark:text-blue-400' : 'text-lime-700 dark:text-lime-400' : 'text-red-600'">
                        {{ rp(Math.abs(changeAmount)) }}
                    </span>
                </div>

                <!-- Pay Button -->
                <button
                    @click="$emit('showBayar')"
                    :disabled="!activeDraft.cart_items.length || (!isPaymentSufficient && form.payment_method === 'cash')"
                    class="flex-1 h-[52px] text-white font-bold rounded-xl text-sm transition-all active:scale-[0.98] flex justify-center items-center gap-2 shadow-lg"
                    :class="[
                        !isPaymentSufficient && form.payment_method === 'cash'
                            ? 'bg-gray-800 dark:bg-gray-600 opacity-90 cursor-not-allowed'
                            : activeDraft.mode === 'bengkel' ? 'bg-blue-500 hover:bg-blue-600 shadow-blue-500/30' : 'bg-lime-500 hover:bg-lime-600 shadow-lime-500/30',
                    ]"
                >
                    <span v-if="!isPaymentSufficient && form.payment_method === 'cash'">UANG KURANG</span>
                    <span v-else>BAYAR / KONFIRMASI</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </button>
            </div>
        </div>
    </div>
</template>
