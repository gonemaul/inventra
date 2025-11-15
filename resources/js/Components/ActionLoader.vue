<script setup>
import { useActionLoading } from "@/Composable/useActionLoading";
import { ref, watch, onMounted } from "vue";

// Ambil state global
const { isActionLoading } = useActionLoading();
const loaderDialog = ref(null);

watch(isActionLoading, (newValue) => {
    // Pastikan elemen dialog sudah ada
    if (!loaderDialog.value) return;

    if (newValue) {
        // 4. Buka dialog loader (ini akan membuatnya di atas modal lain)
        loaderDialog.value.showModal();
    } else {
        // 5. Tutup dialog loader
        loaderDialog.value.close();
    }
});
</script>

<template>
    <dialog
        ref="loaderDialog"
        class="p-0 m-0 bg-transparent backdrop:bg-transparent"
    >
        <div
            class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50"
        >
            <div
                class="inline-block w-12 h-12 border-4 rounded-full border-lime-500 border-t-transparent animate-spin"
                role="status"
            >
                <span class="sr-only">Processing...</span>
            </div>
        </div>
    </dialog>
</template>
