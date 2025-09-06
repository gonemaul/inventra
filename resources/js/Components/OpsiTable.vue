<template>
    <div class="relative inline-block text-left" ref="triggerRef">
        <!-- Tombol trigger -->
        <button
            @click="toggle($event)"
            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700"
        >
            ⋮
        </button>

        <!-- Teleport dropdown ke body -->
        <teleport to="body">
            <transition name="fade">
                <div
                    v-if="isOpen"
                    :style="positionStyle"
                    ref="menuRef"
                    class="absolute z-[9999] mt-1 min-w-[8rem] rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5"
                >
                    <!-- Slot untuk isi custom -->
                    <slot />
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";

const props = defineProps({
    rowId: {
        type: [Number, String],
        required: true,
    },
    activeId: {
        type: [Number, String, null],
        default: null,
    },
    position: {
        type: Object,
        default: () => ({ top: 0, left: 0 }),
    },
});

const emit = defineEmits(["toggle", "close"]);

const triggerRef = ref(null);
const menuRef = ref(null);

const isOpen = computed(() => props.activeId === props.rowId);

const positionStyle = computed(() => ({
    top: props.position?.top + "px",
    left: props.position?.left + "px",
    position: "absolute",
}));

function toggle(e) {
    emit("toggle", props.rowId, e);
}

function handleClickOutside(e) {
    if (
        isOpen.value &&
        !menuRef.value?.contains(e.target) &&
        !triggerRef.value?.contains(e.target)
    ) {
        emit("close");
    }
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
