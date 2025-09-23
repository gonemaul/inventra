<template>
    <div
        class="flex flex-col p-2 shadow-sm lg:p-4 bg-lime-200 dark:bg-gray-800 rounded-xl"
    >
        <span class="text-xs text-gray-500 md:text-sm dark:text-gray-400">
            {{ label }}
        </span>
        <span :class="[baseClass, colorClass]">
            {{ value }}
        </span>
    </div>
</template>

<script setup>
import { computed } from "vue";

defineProps({
    label: String,
    value: [String, Number],
    highlight: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: "success", // success, danger
    },
    status: {
        type: Boolean,
        default: false,
    },
});

const baseClass = "text-sm md:text-lg font-semibold";

const colorClass = computed(() => {
    // if (props.highlight) return "text-lime-600 dark:text-lime-400";
    // if (props.type == "success") return "text-green-600 dark:text-green-400";
    // if (props.type == "danger") return "text-red-600 dark:text-red-400";

    // if (props.status) {
    //     return props.value === "Selesai"
    //         ? "text-green-600 dark:text-green-400"
    //         : "text-yellow-600 dark:text-yellow-400";
    // }

    return "text-gray-800 dark:text-gray-100";
});
</script>
