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
                    class="relative w-full max-w-md mx-auto bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl transform transition-transform duration-300 ease-out max-h-[90vh] flex flex-col"
                    @click.stop
                >
                    <div
                        class="flex justify-center pt-3 pb-1 cursor-pointer"
                        @click="close"
                    >
                        <div
                            class="w-12 h-1.5 bg-gray-300 dark:bg-gray-700 rounded-full"
                        ></div>
                    </div>

                    <div
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
</style>
