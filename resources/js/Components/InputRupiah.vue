<script setup>
import { ref, watch, useAttrs } from "vue";

// 1. Matikan inheritAttrs otomatis agar kita bisa kontrol manual penempatan class-nya
defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: [Number, String],
});

const emit = defineEmits(["update:modelValue"]);
const displayValue = ref("");

// Ambil attributes sisa (placeholder, required, class, dll)
const attrs = useAttrs();

// --- LOGIC FORMATTING (Sama seperti sebelumnya) ---
const formatRupiah = (number) => {
    if (number === "" || number === null || number === undefined) return "";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(number);
};

const onInput = (event) => {
    let val = event.target.value;
    let cleanVal = val.replace(/[^0-9]/g, "");
    let numberValue = cleanVal === "" ? null : parseInt(cleanVal);

    if (cleanVal) {
        displayValue.value = formatRupiah(numberValue);
    } else {
        displayValue.value = "";
    }
    emit("update:modelValue", numberValue);
};

watch(
    () => props.modelValue,
    (newVal) => {
        displayValue.value = formatRupiah(newVal);
    },
    { immediate: true }
);
</script>

<template>
    <div class="relative w-full">
        <input
            type="text"
            :value="displayValue"
            @input="onInput"
            v-bind="$attrs"
            :class="[
                // 1. CLASS DEFAULT (Style Dasar)
                'w-full py-3 pl-4 pr-4 border-gray-300 rounded-lg shadow-sm focus:border-green-500 focus:ring-green-500 transition-all duration-200',

                // 2. CLASS CUSTOM (Timpa style default jika ada class dari parent)
                attrs.class,
            ]"
        />
    </div>
</template>
