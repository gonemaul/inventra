<script setup>
import { useForm, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { useActionLoading } from "@/Composable/useActionLoading";

// Terima props dari Parent (Settings/Index.vue)
const props = defineProps({
    settings: Object, // Data { shop_name: '...', ... }
});
const { isActionLoading } = useActionLoading();
// Setup Form
const form = useForm({
    shop_name: props.settings.shop_name || "",
    shop_phone: props.settings.shop_phone || "",
    shop_address: props.settings.shop_address || "",
    receipt_footer:
        props.settings.receipt_footer || "Terima Kasih, Datang Kembali!",
    shop_logo: props.settings.shop_logo, // Untuk file upload
});
console.log(props.settings);

// Logic Preview Gambar (Agar saat pilih file, gambar langsung berubah tanpa refresh)
const previewImage = ref(props.settings.shop_logo ?? false);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.shop_logo = file;
        previewImage.value = URL.createObjectURL(file); // Buat URL sementara
    }
};

const submit = () => {
    // Gunakan post dengan forceFormData karena ada file upload
    isActionLoading.value = true;
    form.post(route("settings.shop.update"), {
        preserveScroll: true,
        onFinish: () => {
            isActionLoading.value = false;
            // Optional: reset file input
        },
    });
};
</script>

<template>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3 animate-fade-in">
        <div
            class="p-6 bg-white border border-gray-200 shadow-sm lg:col-span-2 dark:bg-gray-800 rounded-2xl dark:border-gray-700"
        >
            <h3
                class="flex items-center gap-2 mb-6 text-lg font-bold text-gray-800 dark:text-white"
            >
                <span>🏪</span> Identitas Toko
            </h3>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="flex items-start gap-6">
                    <div
                        class="flex items-center justify-center flex-shrink-0 w-24 h-24 overflow-hidden bg-gray-100 border border-gray-200 rounded-xl"
                    >
                        <img
                            v-if="previewImage"
                            :src="previewImage"
                            class="object-contain w-full h-full"
                        />
                        <span
                            v-else
                            class="px-2 text-xs text-center text-gray-400"
                            >No Logo</span
                        >
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-sm font-bold text-gray-700 dark:text-gray-300"
                            >Logo Toko (Struk & Laporan)</label
                        >
                        <input
                            type="file"
                            @change="handleFileChange"
                            accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-200"
                        />
                        <p class="mt-2 text-xs text-gray-400">
                            Format: JPG/PNG. Maks 1MB. Logo akan dicetak
                            Hitam-Putih di printer thermal.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >Nama Toko</label
                        >
                        <input
                            type="text"
                            v-model="form.shop_name"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:ring-indigo-500"
                            placeholder="Contoh: Inventra Mart"
                        />
                        <div
                            v-if="form.errors.shop_name"
                            class="mt-1 text-xs text-red-500"
                        >
                            {{ form.errors.shop_name }}
                        </div>
                    </div>
                    <div>
                        <label
                            class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                            >No. Telepon / WA</label
                        >
                        <input
                            type="text"
                            v-model="form.shop_phone"
                            class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:ring-indigo-500"
                            placeholder="0812-xxxx-xxxx"
                        />
                    </div>
                </div>

                <div>
                    <label
                        class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                        >Alamat Lengkap</label
                    >
                    <textarea
                        v-model="form.shop_address"
                        rows="2"
                        class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:ring-indigo-500"
                    ></textarea>
                </div>

                <div>
                    <label
                        class="block mb-1 text-xs font-bold text-gray-500 uppercase"
                        >Pesan Footer Struk</label
                    >
                    <textarea
                        v-model="form.receipt_footer"
                        rows="2"
                        class="w-full border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600 focus:ring-indigo-500"
                        placeholder="Terima kasih, barang yang dibeli tidak dapat ditukar."
                    ></textarea>
                </div>

                <div
                    class="flex justify-end pt-4 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-2 px-6 py-2 font-bold text-white transition bg-indigo-600 rounded-lg shadow-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                        <span v-if="form.processing" class="animate-spin"
                            >⏳</span
                        >
                        <span>Simpan Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>

        <div class="flex flex-col gap-4">
            <h3 class="text-sm font-bold text-gray-500 uppercase">
                Preview Struk
            </h3>

            <div
                class="p-4 mx-auto font-mono text-xs leading-tight text-gray-800 bg-white border border-gray-200 shadow-xl"
                style="
                    width: 280px;
                    min-height: 400px;
                    font-family: 'Courier New', Courier, monospace;
                "
            >
                <div class="mb-4 text-center">
                    <div
                        v-if="previewImage"
                        class="w-12 h-12 mx-auto mb-2 grayscale opacity-90"
                    >
                        <img
                            :src="previewImage"
                            class="object-contain w-full h-full"
                        />
                    </div>
                    <h2 class="mb-1 text-sm font-black uppercase">
                        {{ form.shop_name || "NAMA TOKO" }}
                    </h2>
                    <p class="mb-1">
                        {{ form.shop_address || "Alamat Toko..." }}
                    </p>
                    <p>Telp: {{ form.shop_phone || "08xx-xxxx" }}</p>
                </div>

                <div class="my-2 border-b border-gray-400 border-dashed"></div>

                <div class="flex justify-between mb-1">
                    <span>Kopi Susu</span>
                    <span>15.000</span>
                </div>
                <div class="flex justify-between mb-1 text-gray-500">
                    <span class="pl-2">x 1</span>
                    <span>15.000</span>
                </div>

                <div class="flex justify-between mb-1">
                    <span>Roti Bakar</span>
                    <span>20.000</span>
                </div>
                <div class="flex justify-between mb-1 text-gray-500">
                    <span class="pl-2">x 2</span>
                    <span>40.000</span>
                </div>

                <div class="my-2 border-b border-gray-400 border-dashed"></div>

                <div class="flex justify-between text-sm font-bold">
                    <span>TOTAL</span>
                    <span>55.000</span>
                </div>
                <div class="flex justify-between mt-1">
                    <span>TUNAI</span>
                    <span>60.000</span>
                </div>
                <div class="flex justify-between mt-1">
                    <span>KEMBALI</span>
                    <span>5.000</span>
                </div>

                <div class="my-4 border-b border-gray-400 border-dashed"></div>
                <div class="text-center whitespace-pre-wrap">
                    {{ form.receipt_footer }}
                </div>
                <div class="text-center mt-4 text-[10px] text-gray-400">
                    --- INVENTRA POS ---
                </div>
            </div>

            <p class="text-xs text-center text-gray-400">
                Preview tampilan pada printer 58mm/80mm
            </p>
        </div>
    </div>
</template>
