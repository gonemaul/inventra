<script setup>
import { ref, watch, useAttrs, computed } from "vue";
import { twMerge } from "tailwind-merge";

defineOptions({
    inheritAttrs: false,
});

const props = defineProps({
    modelValue: [Number, String],
    // Tambahkan prop max agar bisa dibaca di script
    max: {
        type: [Number, String],
        default: null,
    },
});

const emit = defineEmits(["update:modelValue"]);
const displayValue = ref("");
const attrs = useAttrs();

const inputClasses = computed(() => {
    return twMerge(
        // 1. Default Class
        "w-full py-3 pl-4 pr-4 border border-gray-300 rounded-lg shadow-sm focus:border-lime-500 focus:ring-lime-500 transition-all duration-200 placeholder-gray-400 text-gray-900 text-right",

        // 2. Custom Class dari Parent (akan menimpa default jika konflik)
        attrs.class
    );
});
// --- FORMATTER ---
const formatRupiah = (number) => {
    if (
        number === "" ||
        number === null ||
        number === undefined ||
        isNaN(number)
    )
        return "";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(number);
};

// --- HANDLER INPUT (Logika Utama) ---
const onInput = (event) => {
    let val = event.target.value;

    // 1. Hapus semua karakter selain angka
    let cleanVal = val.replace(/[^0-9]/g, "");

    // 2. Cegah angka 0 di depan (misal: 05000 -> 5000)
    if (cleanVal.startsWith("0")) {
        cleanVal = cleanVal.replace(/^0+/, "");
    }

    let numberValue = cleanVal === "" ? null : parseInt(cleanVal);

    // 3. LOGIKA MAX NUMBER
    // Jika ada props max dan nilai melebihi max, paksa ke nilai max
    if (
        props.max &&
        numberValue !== null &&
        numberValue > parseInt(props.max)
    ) {
        numberValue = parseInt(props.max);
    }

    // 4. Update Tampilan & Emit
    if (numberValue !== null) {
        // Format ulang ke Rupiah
        const formatted = formatRupiah(numberValue);

        // Update ref displayValue
        displayValue.value = formatted;

        // Paksa update value di elemen input (penting jika user ketik cepat/paste)
        if (event.target.value !== formatted) {
            event.target.value = formatted;
        }
    } else {
        displayValue.value = "";
    }

    emit("update:modelValue", numberValue);
};

// --- HANDLER KEYDOWN (Mencegah Huruf) ---
const onKeydown = (event) => {
    const allowedKeys = [
        "Backspace",
        "Delete",
        "Tab",
        "Escape",
        "Enter",
        "ArrowLeft",
        "ArrowRight",
        "ArrowUp",
        "ArrowDown",
        "Home",
        "End",
    ];

    // Izinkan Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
    if (
        (event.ctrlKey || event.metaKey) &&
        ["a", "c", "v", "x"].includes(event.key.toLowerCase())
    ) {
        return;
    }

    // Izinkan tombol kontrol
    if (allowedKeys.includes(event.key)) {
        return;
    }

    // Cegah jika bukan angka (0-9)
    if (!/^[0-9]$/.test(event.key)) {
        event.preventDefault();
    }
};

// --- WATCHER ---
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
            inputmode="numeric"
            :value="displayValue"
            @input="onInput"
            @keydown="onKeydown"
            @focus="$event.target.select()"
            v-bind="{ ...$attrs, class: null }"
            :class="inputClasses"
        />
    </div>
</template>
