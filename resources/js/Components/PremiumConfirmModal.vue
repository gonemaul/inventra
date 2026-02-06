<script setup>
const props = defineProps({
    show: Boolean,
    title: String,
    message: String,
    confirmText: {
        type: String,
        default: "Ya, Lanjutkan",
    },
    cancelText: {
        type: String,
        default: "Batal",
    },
    type: {
        type: String,
        default: "info", // info, danger, warning
    },
});

const emit = defineEmits(["confirm", "cancel", "close"]);

const close = () => {
    emit("close");
    emit("cancel");
};

const confirm = () => {
    emit("confirm");
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="show"
            class="fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-md"
        >
            <div
                class="relative w-full max-w-md overflow-hidden text-center bg-white shadow-2xl dark:bg-gray-800 rounded-3xl"
            >
                <!-- Header / Icon -->
                <div class="pt-8 pb-4">
                    <div
                        class="flex items-center justify-center w-16 h-16 mx-auto rounded-full"
                        :class="{
                            'bg-lime-100 text-lime-600': type === 'info',
                            'bg-red-100 text-red-600': type === 'danger',
                            'bg-orange-100 text-orange-600': type === 'warning',
                        }"
                    >
                        <!-- Warning / Danger Icon -->
                        <svg
                            v-if="type === 'danger' || type === 'warning'"
                            class="w-8 h-8"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            ></path>
                        </svg>
                        <!-- Info Icon -->
                        <svg
                            v-else
                            class="w-8 h-8"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-8 pb-4">
                    <h3 class="text-xl font-bold text-gray-800 dark:text-white">
                        {{ title }}
                    </h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ message }}
                    </p>
                </div>

                <!-- Buttons -->
                <div
                    class="flex gap-3 px-8 py-6 bg-gray-50 dark:bg-gray-700/50"
                >
                    <button
                        @click="close"
                        class="flex-1 py-2.5 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        @click="confirm"
                        class="flex-1 py-2.5 text-sm font-bold text-white rounded-xl shadow-lg transition"
                        :class="{
                            'bg-lime-600 hover:bg-lime-700 shadow-lime-200':
                                type === 'info',
                            'bg-red-600 hover:bg-red-700 shadow-red-200':
                                type === 'danger',
                            'bg-orange-600 hover:bg-orange-700 shadow-orange-200':
                                type === 'warning',
                        }"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
