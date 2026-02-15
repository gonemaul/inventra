<script setup>
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    total: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["close", "newTransaction"]);

const rp = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
</script>

<template>
    <BottomSheet :show="show" :persistent="true" @close="$emit('newTransaction')">
        <div class="relative w-full text-center pt-4 overflow-hidden">
             <!-- Background Blur Glow -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-lime-400/20 rounded-full blur-[80px] pointer-events-none"></div>

             <!-- Animated Success Check -->
            <div class="relative mb-8 flex justify-center">
                 <div class="w-24 h-24 bg-gradient-to-tr from-lime-400 to-green-600 rounded-full flex items-center justify-center shadow-lg shadow-lime-500/40 animate-bounce-subtle">
                     <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                    </svg>
                 </div>
            </div>

            <!-- Title -->
            <h2 class="text-3xl font-black text-gray-800 dark:text-white mb-2 tracking-tight">Pembayaran Sukses</h2>
            <p class="text-sm font-medium text-gray-400 dark:text-gray-500 mb-8 uppercase tracking-widest">Transaksi Selesai</p>

            <!-- Amount Display -->
            <div class="mb-10">
                <span class="block text-sm text-gray-500 dark:text-gray-400 mb-1">Total Transaksi</span>
                <span class="block text-4xl font-black text-lime-600 dark:text-lime-400 tracking-tight">{{ rp(total) }}</span>
            </div>

            <!-- Action Button -->
            <button
                @click="$emit('newTransaction')"
                class="w-full py-4 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold text-lg rounded-2xl shadow-xl hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 flex justify-center items-center gap-3 group"
            >
                <span>Transaksi Baru</span>
                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
            </button>
        </div>
    </BottomSheet>
</template>

<style scoped>
.animate-bounce-subtle {
    animation: bounce-subtle 3s infinite ease-in-out;
}
@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}
</style>
