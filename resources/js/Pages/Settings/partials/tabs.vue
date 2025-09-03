<template>
    <div>
        <!-- Tab Navigation -->
        <div class="w-full overflow-x-auto">
            <div
                class="flex border-b-2 border-gray-200 min-w-max flex-nowrap dark:border-gray-700"
            >
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        activeTab === tab.key
                            ? 'border-lime-500 dark:border-gray-800 text-white dark:text-white bg-lime-400 dark:bg-gray-800 rounded-t-lg'
                            : 'border-transparent text-gray-500 dark:text-gray-600 hover:text-gray-700 dark:hover:text-gray-400',
                        'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm flex items-center gap-2',
                    ]"
                >
                    <span>{{ tab.label }}</span>
                    <span
                        v-if="tab.count !== undefined && tab.count > 0"
                        class="flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-gray-700 rounded-full dark:bg-lime-500"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="mt-4">
            <slot :name="activeTab"></slot>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    tabs: {
        type: Array,
        required: true,
    },
    defaultTab: {
        type: String,
        default: "",
    },
});

const activeTab = ref(props.defaultTab || props.tabs[0].key);
</script>
