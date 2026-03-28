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
        class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 p-1.5 bg-gray-100/50 dark:bg-gray-800/40 rounded-2xl border border-gray-200/50 dark:border-gray-700/50 backdrop-blur-sm"
    >
        <!-- Search Input with Icon -->
        <div v-if="isSearch" class="relative flex-1 group">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none transition-colors group-focus-within:text-lime-500">
                <svg class="h-4 w-4 text-gray-400 group-focus-within:text-lime-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <TextInput
                class="w-full !pl-10 !py-2.5 !bg-white/80 dark:!bg-gray-900/60 !border-transparent !rounded-xl !text-sm focus:!ring-2 focus:!ring-lime-500/20 focus:!border-lime-500/50 transition-all placeholder:text-gray-400 dark:placeholder:text-gray-500"
                :model-value="modelValue"
                @update:model-value="(value) => emit('update:modelValue', value)"
                @focus="$event.target.select()"
                type="search"
                enterkeyhint="search"
                placeholder="Cari transaksi, item, atau customer..."
            />
        </div>

        <div class="flex items-center gap-2 px-1 justify-between md:justify-end">
            <!-- Filter Button -->
            <button
                v-if="isFilter"
                @click="$emit('showFilter')"
                class="relative flex items-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 font-bold text-xs rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800 active:scale-95 transition-all group"
            >
                <svg class="w-4 h-4 text-gray-400 group-hover:text-lime-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span>Filter</span>
                <span
                    v-if="filterCount > 0"
                    class="absolute -top-1.5 -right-1.5 flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-red-500 rounded-full shadow-lg ring-2 ring-white dark:ring-gray-900"
                >
                    {{ filterCount }}
                </span>
            </button>

            <!-- Action Buttons -->
            <Link
                v-for="action in actions"
                :key="action.route"
                :href="action.route"
                class="inline-flex items-center gap-2 px-5 py-2.5 text-xs font-black tracking-tighter text-white uppercase bg-lime-600 hover:bg-lime-700 dark:bg-lime-600 dark:hover:bg-lime-500 rounded-xl shadow-md shadow-lime-500/20 active:scale-95 transition-all"
            >
                <span v-if="action.buttonText.includes('+')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </span>
                {{ action.buttonText.replace('+', '') }}
            </Link>
        </div>
    </div>
</template>
