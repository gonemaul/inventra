<script setup>
import { computed } from "vue";
import { usePosState } from "@/Composables/POS/usePosState";
import { storeToRefs } from "pinia";
import CartStepVehicle from "./Components/CartStepVehicle.vue";
import CartStepItems from "./Components/CartStepItems.vue";
import CartStepPayment from "./Components/CartStepPayment.vue";

const props = defineProps({
    showMobileCart: { type: Boolean, default: false },
});

const emit = defineEmits(["showDesktop", "showBayar", "openScanMember"]);

const posState = usePosState();
const { activeDraft, maxSteps } = storeToRefs(posState);
const { prevStep, resetCartStep } = posState;

const currentStep = computed(() => activeDraft.value.current_cart_step);
const mode = computed(() => activeDraft.value.mode);

// Map current_cart_step to which component to show
const activeComponent = computed(() => {
    if (mode.value === 'bengkel') {
        if (currentStep.value === 1) return 'vehicle';
        if (currentStep.value === 2) return 'items';
        return 'payment';
    }
    // Retail
    if (currentStep.value === 1) return 'items';
    return 'payment';
});

const stepLabel = computed(() => {
    const labels = {
        vehicle: '🏍️ Kendaraan',
        items: '🛒 Keranjang',
        payment: '💳 Pembayaran',
    };
    return labels[activeComponent.value] || '';
});

const canGoBack = computed(() => currentStep.value > 1);

const resetStep = () => resetCartStep();

defineExpose({ resetStep });
</script>

<template>
    <div
        :class="[
            'lg:static lg:w-full',
            'fixed inset-0 z-[70] bg-white dark:bg-gray-800 transition-transform duration-300 ease-in-out flex flex-col h-full',
            showMobileCart ? 'translate-y-0' : 'translate-y-full lg:translate-y-0',
        ]"
    >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b shrink-0 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 lg:bg-white lg:dark:bg-gray-800">
            <div class="flex items-center gap-2">
                <!-- Back button -->
                <button
                    v-if="canGoBack"
                    @click="prevStep"
                    class="p-1.5 text-gray-500 bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <h2 class="text-base font-black text-gray-800 dark:text-white">{{ stepLabel }}</h2>
            </div>

            <div class="flex items-center gap-2">
                <!-- Reset (only on items step) -->
                <button
                    v-if="activeComponent === 'items' && activeDraft.cart_items.length"
                    @click="activeDraft.cart_items = []"
                    class="text-[10px] text-red-500 font-bold bg-red-50 dark:bg-red-900/20 px-2 py-1 rounded hover:bg-red-100 transition"
                >Reset</button>

                <!-- Step indicator dots -->
                <div class="flex items-center gap-1 mr-2">
                    <div
                        v-for="s in maxSteps"
                        :key="s"
                        class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="s === currentStep
                            ? (mode === 'bengkel' ? 'bg-blue-500 w-4' : 'bg-lime-500 w-4')
                            : s < currentStep
                                ? (mode === 'bengkel' ? 'bg-blue-300' : 'bg-lime-300')
                                : 'bg-gray-300 dark:bg-gray-600'"
                    ></div>
                </div>

                <!-- Close (mobile) -->
                <button
                    @click="$emit('showDesktop')"
                    class="p-1.5 text-gray-500 bg-gray-100 rounded-full lg:hidden dark:bg-gray-700 dark:text-gray-200"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <!-- Dynamic Step Content -->
        <CartStepVehicle v-if="activeComponent === 'vehicle'" />
        <CartStepItems v-else-if="activeComponent === 'items'" />
        <CartStepPayment v-else @showBayar="$emit('showBayar')" @openScanMember="$emit('openScanMember')" />
    </div>
</template>
