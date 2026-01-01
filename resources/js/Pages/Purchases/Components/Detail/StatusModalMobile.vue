<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading"; // Jika Anda menggunakan ini

const props = defineProps({
    targetStatus: String,
    purchase: Object,
});
const { isActionLoading } = useActionLoading();
const toast = useToast();

const showStatusModal = ref(false);
const data = ref({});
const emit = defineEmits(["close"]);
const open = (config) => {
    data.value = config.data;
    showStatusModal.value = true;
};
const close = () => {
    showStatusModal.value = false;
    emit("close");
};
defineExpose({
    open,
});
const executeAction = () => {
    isActionLoading.value = true;
    router.put(
        route("purchases.update-status", props.purchase.id),
        data.value || {},
        {
            preserveScroll: true,
            onSuccess: () => {
                close();
                emit("close");
            },
            onError: (errors) => {
                toast.error(errors.message || "Terjadi kesalahan.");
            },
            onFinish: () => {
                isActionLoading.value = false;
            },
        }
    );
};
const statusConfig = {
    draft: {
        label: "DRAFT",
        // Icon Document Text
        icon: "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
    },
    dipesan: {
        label: "DIPESAN",
        // Icon Shopping Cart
        icon: "M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z",
    },
    dikirim: {
        label: "DIKIRIM",
        // Icon Truck (Style: Heroicons Outline)
        // Visual: Truk box dengan kabin terpisah dan dua roda yang jelas
        icon: "M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0",
    },
    diterima: {
        label: "DITERIMA",
        // Icon Cube/Box (Paket)
        icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
    },
    checking: {
        label: "CHECKING",
        // Icon Clipboard Check
        icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
    },
    selesai: {
        label: "SELESAI",
        // Icon Badge Check (Verified)
        icon: "M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z",
    },
    dibatalkan: {
        label: "BATAL",
        // Icon X Circle
        icon: "M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z",
    },
};
// Helper untuk mengambil config berdasarkan status
const getConfig = (status) => {
    return statusConfig[status] || statusConfig["draft"];
};
</script>
<template>
    <div
        v-if="showStatusModal"
        class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-black/50 backdrop-blur-sm"
    >
        <div class="w-full max-w-sm p-6 bg-white dark:bg-gray-900 rounded-2xl">
            <h3
                class="mb-4 text-lg font-bold text-center text-gray-800 dark:text-white"
            >
                Konfirmasi Perubahan Status
            </h3>

            <div
                class="items-center justify-between p-3 mb-6 border border-blue-100 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-800 rounded-xl"
            >
                <div class="mb-2">
                    <p
                        class="text-[10px] text-gray-400 uppercase tracking-wider font-bold"
                    >
                        Kode Transaksi
                    </p>
                    <p
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{ purchase.reference_no }}
                    </p>
                </div>
                <div>
                    <p
                        class="text-[10px] text-gray-400 uppercase tracking-wider font-bold"
                    >
                        Supplier
                    </p>
                    <p
                        class="text-xs font-medium text-gray-600 dark:text-gray-300 truncate max-w-[120px]"
                    >
                        {{ purchase.supplier?.name }}
                    </p>
                </div>
            </div>

            <div
                class="relative p-5 border border-gray-100 bg-gray-50 dark:bg-gray-800 rounded-xl dark:border-gray-700"
            >
                <div
                    class="absolute top-1/2 left-4 right-4 h-0.5 bg-gray-200 dark:bg-gray-700 -translate-y-1/2 z-0"
                ></div>

                <div class="relative z-10 flex items-center justify-between">
                    <div class="flex flex-col items-center w-1/3 gap-2 group">
                        <div
                            class="flex items-center justify-center w-10 h-10 text-gray-400 bg-gray-200 rounded-full shadow-sm dark:bg-gray-700 dark:text-gray-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="getConfig(purchase.status).icon"
                                />
                            </svg>
                        </div>
                        <div class="text-center">
                            <span
                                class="text-[10px] text-gray-400 uppercase font-bold tracking-wider block mb-0.5"
                                >Dari</span
                            >
                            <span
                                class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded text-[10px] font-bold border border-gray-200 block truncate"
                            >
                                {{ getConfig(purchase.status).label }}
                            </span>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-center w-8 h-8 text-gray-400 bg-white border border-gray-100 rounded-full shadow-sm dark:bg-gray-600 dark:border-gray-500"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-4 h-4 animate-pulse text-lime-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3"
                            />
                        </svg>
                    </div>

                    <div class="flex flex-col items-center w-1/3 gap-2">
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-full shadow-sm bg-lime-100 dark:bg-lime-900/30 text-lime-600 dark:text-lime-400 ring-4 ring-lime-50 dark:ring-lime-900/10"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="w-5 h-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="getConfig(targetStatus).icon"
                                />
                            </svg>
                        </div>
                        <div class="text-center">
                            <span
                                class="text-[10px] text-lime-600 uppercase font-bold tracking-wider block mb-0.5"
                                >Menjadi</span
                            >
                            <span
                                class="px-2 py-0.5 bg-lime-50 text-lime-700 border border-lime-200 rounded text-[10px] font-bold block truncate"
                            >
                                {{ getConfig(targetStatus).label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 mt-6">
                <button
                    @click="close()"
                    class="flex-1 py-3 text-sm font-bold text-gray-600 transition bg-gray-100 rounded-xl hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    Batal
                </button>
                <button
                    @click="executeAction()"
                    class="flex-1 py-3 text-sm font-bold text-white rounded-xl bg-lime-500 hover:bg-lime-600 shadow-lg shadow-lime-500/30 transition active:scale-[0.98]"
                >
                    Ya, Ubah Status
                </button>
            </div>
        </div>
    </div>
</template>
