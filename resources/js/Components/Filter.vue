<script setup>
import { ref } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    isSearch: {
        type: Boolean,
        default: true,
    },
    isFilter: {
        type: Boolean,
        default: true,
    },
    actions: {
        type: Array,
        default: () => [],
    },
    modelValue: String,
    filterCount: {
        type: Number,
        default: 0,
    },
});
const emit = defineEmits(["showFilter", "update:modelValue"]);
</script>
<template>
    <div
        class="flex flex-col justify-between gap-2 px-5 py-3 bg-gray-100 rounded shadow-md md:flex-row dark:bg-customBg-tableDark"
    >
        <TextInput
            class="md:w-3/6"
            v-if="isSearch"
            :model-value="modelValue"
            @update:model-value="(value) => emit('update:modelValue', value)"
            placeholder="Cari..."
        />
        <div class="flex justify-between md:justify-end">
            <PrimaryButton
                class="relative md:ms-4"
                v-if="isFilter"
                @click="$emit('showFilter')"
                >Filter
                <span
                    v-if="filterCount > 0"
                    class="absolute flex items-center justify-center w-5 h-5 text-xs font-medium text-white bg-red-500 rounded-full -top-2 -right-2"
                    >{{ filterCount }}</span
                ></PrimaryButton
            >
            <Link
                v-for="action in actions"
                :href="action.route"
                class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-center text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md cursor-pointer ms-4 dark:bg-gray-800 bg-lime-500 hover:bg-lime-400 dark:hover:bg-gray-900 focus:bg-lime-700 focus:outline-none focus:ring-2 focus:ring-lime-500 focus:ring-offset-2 active:bg-lime-900"
            >
                {{ action.buttonText }}
            </Link>
        </div>
    </div>
</template>
