<script setup>
import { computed } from "vue";

const props = defineProps(["modelValue", "placeholder"]);
const emit = defineEmits(["update:modelValue"]);

// Format tampilan (10000 -> 10.000)
const displayValue = computed({
    get() {
        if (!props.modelValue) return "";
        return new Intl.NumberFormat("id-ID").format(props.modelValue);
    },
    set(newValue) {
        // Hapus semua karakter selain angka
        const numericValue = newValue.replace(/\D/g, "");
        emit("update:modelValue", numericValue ? parseInt(numericValue) : 0);
    },
});
</script>

<template>
    <div class="relative">
        <span
            class="absolute text-lg font-bold text-gray-500 -translate-y-1/2 dark:text-gray-400 left-3 top-1/2"
            >Rp</span
        >
        <input
            type="text"
            inputmode="numeric"
            v-model="displayValue"
            :placeholder="placeholder"
            @focus="$event.target.select()"
            class="w-full py-2 pl-10 pr-4 text-lg font-bold text-right text-gray-800 bg-white border border-gray-300 dark:bg-gray-800 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-lime-500 dark:text-white"
        />
    </div>
</template>
