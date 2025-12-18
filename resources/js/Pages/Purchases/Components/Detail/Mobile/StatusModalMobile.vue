<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading"; // Jika Anda menggunakan ini

const props = defineProps({
    showStatusModal: Boolean,
});
const { isActionLoading } = useActionLoading();
const toast = useToast();

const executeAction = () => {
    isActionLoading.value = true;
    const inertiaMethod = method.value.toLowerCase();
    router[inertiaMethod](deleteUrl.value, data.value || {}, {
        preserveScroll: true,
        onSuccess: () => {
            close();
            emit("close");
        },
        onError: (errors) => {
            toast.error(
                errors.message || "Terjadi kesalahan saat menghapus data."
            );
        },
        onFinish: () => {
            isActionLoading.value = false;
            deleteUrl.value = "";
        },
    });
};
</script>
<template>
    <div
        v-if="showStatusModal"
        class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm"
    >
        <div class="w-full max-w-sm p-5 bg-white dark:bg-gray-900 rounded-2xl">
            <h3 class="mb-4 text-lg font-bold text-gray-800 dark:text-white">
                Ubah Status Transaksi
            </h3>
            <div class="space-y-2">
                <button
                    v-for="(label, key) in availableStatusOptions"
                    :key="key"
                    @click="
                        actions.updateStatus(key);
                        showStatusModal = false;
                    "
                    :class="[
                        'w-full py-3 px-4 rounded-lg text-left font-medium border transition',
                        purchase.status === key
                            ? 'bg-lime-50 border-lime-500 text-lime-700'
                            : 'border-gray-200 text-gray-700 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-800',
                    ]"
                >
                    {{ label }}
                    <span
                        v-if="purchase.status === key"
                        class="float-right font-bold text-lime-600"
                        >✓</span
                    >
                </button>

                <div
                    v-if="Object.keys(availableStatusOptions).length === 0"
                    class="py-2 text-sm text-center text-gray-500"
                >
                    Tidak ada opsi perubahan status.
                </div>
            </div>
            <button
                @click="showStatusModal = false"
                class="w-full py-2 mt-4 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
            >
                Batal
            </button>
        </div>
    </div>
</template>
