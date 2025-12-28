<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    title: { type: String, default: "Tidak ada data" },
    message: { type: String, default: "Belum ada item yang ditambahkan." },
    icon: { type: String, default: "box" }, // Options: 'box', 'search'
    actionLabel: String,
    actionUrl: String,
    actionType: { type: String, default: "link" }, // 'link' or 'button'
});

const emit = defineEmits(["action"]);
</script>

<template>
    <div
        class="flex flex-col items-center justify-center p-12 text-center bg-white border border-gray-300 border-dashed rounded-2xl dark:bg-gray-800 dark:border-gray-700 h-96"
    >
        <div class="relative mb-6">
            <div
                class="absolute inset-0 rounded-full opacity-50 bg-lime-100 dark:bg-lime-900/30 blur-xl animate-pulse"
            ></div>
            <div
                class="relative flex items-center justify-center w-20 h-20 border border-gray-100 rounded-full shadow-sm bg-gray-50 dark:bg-gray-700 dark:border-gray-600"
            >
                <svg
                    v-if="icon === 'box'"
                    class="w-10 h-10 text-gray-400 dark:text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    ></path>
                </svg>

                <svg
                    v-else-if="icon === 'search'"
                    class="w-10 h-10 text-gray-400 dark:text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    ></path>
                </svg>
            </div>
        </div>

        <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">
            {{ title }}
        </h3>
        <p
            class="max-w-xs mx-auto mb-8 text-sm leading-relaxed text-gray-500 dark:text-gray-400"
        >
            {{ message }}
        </p>

        <div v-if="actionLabel">
            <Link
                v-if="actionType === 'link'"
                :href="actionUrl"
                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold text-white transition-all duration-200 bg-lime-500 rounded-xl hover:bg-lime-600 hover:shadow-lg hover:shadow-lime-200 dark:hover:shadow-none focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-lime-500"
            >
                <svg
                    class="w-5 h-5 mr-2 -ml-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                    ></path>
                </svg>
                {{ actionLabel }}
            </Link>

            <button
                v-else
                @click="$emit('action')"
                class="inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 dark:bg-gray-700 dark:text-white dark:border-gray-600 dark:hover:bg-gray-600 transition-all shadow-sm"
            >
                {{ actionLabel }}
            </button>
        </div>
    </div>
</template>
