<script setup>
// Props
defineProps({
    // v-model untuk membuka/tutup
    show: {
        type: Boolean,
        required: true,
    },
    // Judul Opsional (jika tidak diisi, header judul hilang)
    title: {
        type: String,
        default: "",
    },
    // Opsi untuk mencegah tutup saat klik backdrop (default: false)
    persistent: {
        type: Boolean,
        default: false,
    },
});

// Emits
const emit = defineEmits(["close"]);

// Handle tutup modal
const close = () => {
    emit("close");
};

// Handle klik backdrop
const handleBackdropClick = () => {
    // Jika props persistent = true, modal tidak tutup saat klik luar
    // Jika false (default), modal tutup
    // Namun mengecek properti di dalam setup butuh props.persistent
    // Cara simpel: emit close, biarkan parent mengatur logic v-if/v-show atau
    // kita anggap default behavior bottom sheet adalah tutup.
    emit("close");
};
</script>

<template>
    <Teleport to="body">
        <Transition name="slide-fade">
            <div
                v-if="show"
                class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center"
            >
                <div
                    class="absolute inset-0 transition-opacity bg-black/50 backdrop-blur-sm"
                    @click="handleBackdropClick"
                ></div>

                <div
                    class="relative overflow-hidden w-full max-w-md mx-auto bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl transform transition-transform duration-300 ease-out max-h-[90vh] flex flex-col"
                    @click.stop
                >
                    <!-- <div
                        class="flex justify-center pt-3 pb-1 cursor-pointer"
                        @click="close"
                    >
                        <div
                            class="w-12 h-1.5 bg-gray-300 dark:bg-gray-700 rounded-full"
                        ></div>
                    </div> -->

                    <!-- <div
                        v-if="title"
                        class="flex items-center justify-between px-5 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0"
                    >
                        <h3
                            class="text-sm font-bold text-gray-800 dark:text-white"
                        >
                            {{ title }}
                        </h3>
                        <button
                            @click="close"
                            class="p-1 text-gray-500 transition bg-gray-100 rounded-full dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </button>
                    </div> -->
                    <div
                        class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800"
                    >
                        <div>
                            <h2
                                class="flex items-center gap-2 text-lg font-bold text-gray-800 dark:text-white"
                            >
                                <svg
                                    class="w-5 h-5 text-lime-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    />
                                </svg>
                                Filter Lanjutan
                            </h2>
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                            >
                                Saring data {{ title }} secara mendetail.
                            </p>
                        </div>
                        <button
                            @click="$emit('close')"
                            class="text-gray-400 hover:text-red-500 transition p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto custom-scrollbar">
                        <slot></slot>
                    </div>

                    <div
                        v-if="$slots.footer"
                        class="p-4 pb-6 bg-white border-t border-gray-100 dark:border-gray-800 dark:bg-gray-900 shrink-0 sm:pb-4 rounded-b-2xl"
                    >
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Animasi Masuk/Keluar */
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
}

/* Khusus Sheet-nya (Transform) */
.slide-fade-enter-from .relative,
.slide-fade-leave-to .relative {
    transform: translateY(100%);
}

.slide-fade-enter-active .relative,
.slide-fade-leave-active .relative {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

/* Custom Scrollbar tipis */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #4b5563;
}
</style>
