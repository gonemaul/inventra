<script setup>
import Modal from "@/Components/Modal.vue";
import DangerButton from "@/Components/DangerButton.vue"; // Asumsi tombol merah
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import { useActionLoading } from "@/Composable/useActionLoading"; // Jika Anda menggunakan ini

const { isActionLoading } = useActionLoading();
const toast = useToast();

const show = ref(false);
const title = ref("Konfirmasi Aksi");
const message = ref("Anda yakin ingin melanjutkan aksi ini?");
const itemName = ref("");
const deleteUrl = ref(""); // URL yang akan dipanggil (misal: purchases.destroy)
const method = ref("post"); // Method yang digunakan (delete/put/post)
const data = ref({});

const open = (config) => {
    title.value = config.title || "Konfirmasi Penghapusan";
    message.value = config.message || "Anda yakin ingin menghapus data ini?";
    itemName.value = config.itemName || "";
    deleteUrl.value = config.url || ""; // <-- Parent mengirim URL ke sini
    method.value = config.method || "post";
    data.value = config.data || {};

    show.value = true;
};

defineExpose({
    open,
});

const close = () => {
    show.value = false;
    emit("close");
};

// 1. Fungsi Aksi Utama (Delete/Put/Post)
const executeAction = () => {
    if (!deleteUrl.value) {
        toast.error("URL aksi tidak terdefinisi.");
        return;
    }

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
const emit = defineEmits(["close", "success"]);
</script>

<template>
    <Modal :show="show" @close="emit('close')" maxWidth="md">
        <div class="p-6">
            <h3 class="mb-4 text-xl font-bold text-red-600">
                {{ title }}
            </h3>

            <p class="mb-4 text-gray-700 dark:text-gray-300">
                {{ message }}
            </p>

            <p v-if="itemName" class="mb-6 text-lg font-semibold">
                Item:
                <span class="text-red-700 dark:text-red-400">{{
                    itemName
                }}</span>
            </p>

            <div class="flex justify-end gap-3 mt-4">
                <SecondaryButton @click="close" type="button">
                    Batal
                </SecondaryButton>

                <DangerButton
                    @click="executeAction"
                    :disabled="isActionLoading"
                >
                    <span v-if="isActionLoading">Memproses...</span>
                    <span v-else>Ya</span>
                </DangerButton>
            </div>
        </div>
    </Modal>
</template>
