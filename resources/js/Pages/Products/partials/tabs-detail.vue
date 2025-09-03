<template>
    <div>
        <div class="w-full overflow-x-auto">
            <!-- Tab Navigation -->
            <div class="flex border-b-2 border-gray-200 dark:border-gray-700">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        activeTab === tab.key
                            ? 'border-lime-500 dark:border-gray-800 text-white dark:text-white bg-lime-400 dark:bg-gray-800 rounded-t-lg'
                            : 'border-transparent text-gray-500 dark:text-gray-600 hover:text-gray-700 dark:hover:text-gray-400',
                        'whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm',
                    ]"
                >
                    {{ tab.label }}
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
