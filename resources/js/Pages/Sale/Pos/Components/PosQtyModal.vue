<script setup>
import BottomSheet from "@/Components/BottomSheet.vue";
import InputRupiah from "@/Components/InputRupiah.vue";
import { computed, ref, watch, nextTick } from "vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close", "addToCart", "update:item"]);

const currentItem = computed({
    get: () => props.item,
    set: (val) => emit("update:item", val),
});

const activeTab = ref('qty');
const inputQty = ref(null);
const inputNominal = ref(null);

watch(() => props.show, (isShowing) => {
    if (isShowing) {
        activeTab.value = 'qty';
        currentItem.value.is_nominal_override = false;
        currentItem.value.subtotal = currentItem.value.quantity * currentItem.value.price;
        
        nextTick(() => {
            inputQty.value?.focus();
        });
    }
});

watch(activeTab, (newTab) => {
    nextTick(() => {
        if (newTab === 'qty') {
            inputQty.value?.focus();
        } else {
            // Target the input inside InputRupiah component
            const el = inputNominal.value?.$el?.querySelector('input') || inputNominal.value;
            el?.focus();
        }
    });
});

const handleNominalInput = () => {
    if (currentItem.value.price > 0 && currentItem.value.subtotal >= 0) {
        let newQty = currentItem.value.subtotal / currentItem.value.price;
        if (newQty > currentItem.value.stock) {
            newQty = currentItem.value.stock;
            currentItem.value.subtotal = newQty * currentItem.value.price;
        }
        currentItem.value.quantity = parseFloat(newQty.toFixed(4));
        currentItem.value.is_nominal_override = true;
    }
};

const handleQtyInput = () => {
    if (currentItem.value.quantity > currentItem.value.stock) {
        currentItem.value.quantity = currentItem.value.stock;
    }
    currentItem.value.subtotal = currentItem.value.quantity * currentItem.value.price;
    currentItem.value.is_nominal_override = false;
};

const handleAddQty = () => {
    if (currentItem.value.quantity < currentItem.value.stock) {
        currentItem.value.quantity++;
        handleQtyInput();
    }
};

const handleSubQty = () => {
    if (currentItem.value.quantity > 0.0001) { // Allow fractions if adjusting down
        currentItem.value.quantity--;
        if (currentItem.value.quantity < 0.0001) currentItem.value.quantity = 0.0001;
        handleQtyInput();
    }
};

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val || 0);
</script>

<template>
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="Tambah Pesanan"
    >
        <div class="space-y-6">
            <div
                class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800"
            >
                <div
                    class="flex items-center justify-center text-lg font-bold text-gray-400 bg-gray-100 border border-gray-200 w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-700 shrink-0"
                >
                    {{ currentItem.name?.substring(0, 2).toUpperCase() }}
                </div>

                <div class="flex-1 min-w-0">
                    <h3
                        class="text-lg font-bold leading-tight text-gray-800 truncate dark:text-white"
                    >
                        {{ currentItem.name }}
                    </h3>
                    <div class="flex items-center justify-between mt-1">
                        <p
                            class="text-xs text-gray-500 font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded"
                        >
                            {{ currentItem.code }}
                        </p>
                        <p
                            class="text-xs font-medium"
                            :class="
                                3 > currentItem.stock
                                    ? 'text-red-500'
                                    : 'text-gray-400'
                            "
                        >
                            Stok: {{ currentItem.stock }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- TABS UNTUK PRODUK BISA DIECER -->
            <div v-if="currentItem.is_decimal" class="flex p-1 bg-gray-100 rounded-xl dark:bg-gray-800">
                <button 
                    @click="activeTab = 'qty'"
                    :class="activeTab === 'qty' ? 'bg-white text-lime-600 shadow-sm dark:bg-gray-700 dark:text-lime-400' : 'text-gray-500 hover:text-gray-700'"
                    class="flex-1 py-2 text-xs font-bold transition-all rounded-lg"
                >
                    Input Qty
                </button>
                <button 
                    @click="activeTab = 'nominal'; currentItem.subtotal = currentItem.subtotal || 0"
                    :class="activeTab === 'nominal' ? 'bg-white text-blue-600 shadow-sm dark:bg-gray-700 dark:text-blue-400' : 'text-gray-500 hover:text-gray-700'"
                    class="flex-1 py-2 text-xs font-bold transition-all rounded-lg"
                >
                    Input Nominal (Rp)
                </button>
            </div>

            <!-- TAB PENGISIAN QTY -->
            <div v-if="activeTab === 'qty'" class="bg-gray-50 dark:bg-gray-800/50 p-1.5 rounded-2xl border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <button
                        @click="handleSubQty"
                        class="flex items-center justify-center text-gray-500 transition bg-white border border-gray-200 shadow-sm w-14 h-14 dark:bg-gray-800 rounded-xl dark:border-gray-600 active:scale-95 hover:text-red-500 disabled:opacity-50"
                        :disabled="currentItem.quantity <= 0.0001"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                    </button>

                    <div class="flex-1 px-2 text-center">
                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Jumlah Beli</label>
                        <input
                            ref="inputQty"
                            v-model.number="currentItem.quantity"
                            @input="handleQtyInput"
                            type="number"
                            step="any"
                            class="w-full p-0 text-3xl font-black text-center text-gray-800 bg-transparent border-none dark:text-white focus:ring-0"
                            @focus="$event.target.select()"
                        />
                    </div>

                    <button
                        @click="handleAddQty"
                        class="flex items-center justify-center text-white transition shadow-lg w-14 h-14 bg-lime-500 rounded-xl shadow-lime-500/30 active:scale-95 disabled:opacity-50"
                        :disabled="currentItem.quantity >= currentItem.stock"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </button>
                </div>
            </div>

            <!-- TAB PENGISIAN NOMINAL -->
            <div v-if="activeTab === 'nominal'" class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                <div class="text-center">
                    <label class="block text-xs font-bold text-blue-400 uppercase tracking-wider mb-2">Beli Rp Berapa?</label>
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <InputRupiah
                            ref="inputNominal"
                            v-model="currentItem.subtotal"
                            @input="handleNominalInput"
                            class="w-full !p-0 text-4xl font-black text-center text-blue-800 bg-transparent border-none dark:text-blue-300 focus:ring-0 shadow-none"
                            placeholder="0"
                        />
                    </div>
                    <div class="bg-white/60 dark:bg-gray-800 rounded-lg py-2 px-3 inline-block">
                        <span class="text-xs text-gray-500 font-medium">Setara dengan: </span>
                        <span class="text-sm font-bold text-gray-800 dark:text-gray-200">{{ currentItem.quantity }} {{ currentItem.unit?.name || currentItem.unit }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 dark:border-gray-800 rounded-xl">
                <div>
                    <p class="text-xs text-gray-400">Harga Satuan</p>
                    <p class="font-bold text-gray-600 dark:text-gray-300">
                        {{ rp(currentItem.price) }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase">Subtotal</p>
                    <p class="text-2xl font-black text-gray-900 dark:text-white">
                        {{ rp(currentItem.subtotal) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <!-- Tombol Scan Lagi -->
                <button
                    @click="$emit('addToCart', true)" 
                    class="w-full py-4 text-sm font-bold text-gray-700 bg-white border border-gray-200 shadow-sm rounded-2xl hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 dark:bg-gray-800 dark:text-white dark:border-gray-700 dark:hover:bg-gray-700 flex flex-col items-center justify-center gap-1"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5 mb-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4v16m8-8H4"
                        />
                    </svg>
                    <span>Masuk & Scan Lg</span>
                </button>

                <!-- Tombol Simpan -->
                <button
                    @click="$emit('addToCart', false)"
                    class="w-full py-4 text-sm font-bold text-white shadow-lg bg-lime-500 rounded-2xl hover:bg-lime-600 hover:shadow-lime-500/30 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500 flex flex-col items-center justify-center gap-1"
                >
                    <svg class="w-5 h-5 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Simpan</span>
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
