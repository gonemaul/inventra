<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    href: { type: String, required: true },
    active: { type: Boolean, default: false },
});

const classes = computed(() =>
    props.active
        ? "bg-lime-500 bg-opacity-80 !text-white flex items-center p-2 pl-3 text-lg font-medium rounded-lg transition-all duration-200"
        : "flex items-center p-2 pl-3 text-lg font-medium text-lime-500 rounded-lg transition-all duration-200 hover:bg-lime-400 hover:bg-opacity-50 hover:text-white dark:text-white dark:hover:bg-white dark:hover:text-lime-400 dark:hover:bg-opacity-30"
);
</script>

<template>
    <Link :href="href" :class="classes">
        <slot />
    </Link>
</template>
