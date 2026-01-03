<script setup>
import { watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
    show: { type: Boolean, required: true },
    title: { type: String, default: "" },
    persistent: { type: Boolean, default: false },
});

const emit = defineEmits(["close"]);

const close = () => emit("close");

const handleBackdropClick = () => {
    if (!props.persistent) close();
};

// --- LOGIC 1: Scroll Lock Management ---
const toggleBodyScroll = (lock) => {
    document.body.style.overflow = lock ? "hidden" : "";
};

// --- LOGIC 2: Keyboard Support (ESC to Close) ---
const handleKeydown = (e) => {
    if (e.key === "Escape" && props.show && !props.persistent) {
        close();
    }
};

// Watcher untuk Scroll Lock
watch(
    () => props.show,
    (val) => {
        toggleBodyScroll(val);
    }
);

// Lifecycle Hooks
onMounted(() => {
    // Pasang listener tombol ESC saat komponen dimuat
    document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    // Bersihkan semua effect saat komponen dihancurkan
    toggleBodyScroll(false);
    document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="bottom-sheet">
            <div
                v-if="show"
                class="fixed inset-0 z-[60] flex items-end justify-center sm:items-center"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-title"
            >
                <div
                    class="absolute inset-0 transition-opacity bg-black/50 backdrop-blur-sm sheet-backdrop"
                    @click="handleBackdropClick"
                ></div>

                <div
                    class="relative w-full max-w-md mx-auto flex flex-col max-h-[90vh] bg-white dark:bg-gray-900 rounded-t-2xl sm:rounded-2xl shadow-2xl transform transition-transform sheet-content"
                    @click.stop
                >
                    <div
                        class="flex justify-center pt-3 pb-1 cursor-pointer shrink-0"
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
                            id="modal-title"
                            class="text-sm font-bold text-gray-800 dark:text-white"
                        >
                            {{ title }}
                        </h3>
                        <button
                            @click="close"
                            class="p-1.5 text-gray-500 transition bg-gray-100 rounded-full dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-lime-500"
                            aria-label="Close modal"
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
                        class="p-4 bg-white border-t border-gray-100 dark:border-gray-800 dark:bg-gray-900 shrink-0 rounded-b-2xl"
                    >
                        <slot name="footer"></slot>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* Transisi Smooth Apple-like */
.bottom-sheet-enter-active,
.bottom-sheet-leave-active {
    transition: all 0.3s ease;
}

/* Backdrop Fade */
.bottom-sheet-enter-active .sheet-backdrop,
.bottom-sheet-leave-active .sheet-backdrop {
    transition: opacity 0.3s ease;
}
.bottom-sheet-enter-from .sheet-backdrop,
.bottom-sheet-leave-to .sheet-backdrop {
    opacity: 0;
}

/* Content Slide Up */
.bottom-sheet-enter-active .sheet-content,
.bottom-sheet-leave-active .sheet-content {
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.bottom-sheet-enter-from .sheet-content,
.bottom-sheet-leave-to .sheet-content {
    transform: translateY(100%);
}

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
