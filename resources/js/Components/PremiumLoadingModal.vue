<script setup>
import { computed } from "vue";

const props = defineProps({
    show: Boolean,
    message: {
        type: String,
        default: "Memproses...",
    },
    subMessage: {
        type: String,
        default: "Mohon tunggu sebentar, jangan tutup halaman ini.",
    },
});
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
                class="relative w-full max-w-sm overflow-hidden text-center bg-white shadow-2xl dark:bg-gray-800 rounded-3xl"
            >
                <!-- Decorative Elements -->
                <div
                    class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-lime-300 via-lime-500 to-lime-300 animate-gradient-x"
                ></div>
                <div
                    class="absolute bottom-0 right-0 w-32 h-32 transform translate-x-10 translate-y-10 bg-lime-500 rounded-full opacity-10 blur-3xl animate-pulse"
                ></div>
                <div
                    class="absolute top-0 left-0 w-24 h-24 transform -translate-x-8 -translate-y-8 bg-lime-300 rounded-full opacity-10 blur-2xl animate-pulse"
                    style="animation-delay: 1s"
                ></div>

                <div class="relative px-8 py-10">
                    <!-- Custom Premium Spinner/Animation -->
                    <div class="flex items-center justify-center mb-6">
                        <div class="relative w-16 h-16">
                            <!-- Outer Ring -->
                            <div
                                class="absolute inset-0 border-4 rounded-full border-lime-100 dark:border-gray-700"
                            ></div>
                            <!-- Spinning Ring -->
                            <div
                                class="absolute inset-0 border-4 border-t-4 rounded-full border-transparent border-t-lime-500 animate-spin"
                            ></div>
                            <!-- Inner Pulse -->
                            <div
                                class="absolute inset-0 flex items-center justify-center"
                            >
                                <div
                                    class="w-8 h-8 rounded-full bg-lime-500/20 animate-pulse"
                                >
                                    <div
                                        class="w-full h-full bg-lime-500 rounded-full opacity-40 animate-ping"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Text Content -->
                    <h3
                        class="mb-2 text-xl font-bold text-gray-800 dark:text-white"
                    >
                        {{ message }}
                    </h3>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        {{ subMessage }}
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.animate-gradient-x {
    background-size: 200% 100%;
    animation: gradient-x 2s ease infinite;
}

@keyframes gradient-x {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}
</style>
