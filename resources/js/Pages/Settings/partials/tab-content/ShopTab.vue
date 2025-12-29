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
                            loading="lazy"
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

        <div class="flex flex-col gap-4 bodyPrev">
            <h3 class="text-sm font-bold text-gray-500 uppercase">
                Preview Struk
            </h3>
            <div class="mx-auto sheet">
                <div class="text-center">
                    <div
                        class="uppercase bold"
                        style="font-size: 14px; margin-bottom: 3px"
                    >
                        {{ form.shop_name || "NAMA TOKO" }}
                    </div>
                    <div style="font-size: 11px">
                        {{ form.shop_address || "Alamat Toko Belum Diisi" }}
                    </div>
                    <div style="font-size: 11px">
                        {{ form.shop_phone || "" }}
                    </div>
                </div>

                <div class="border-dashed"></div>

                <div>
                    No : POS/101224/2029 <br />
                    Tgl : 12/12/2021 WIB<br />
                    Kasir : Admin <br />
                    Member: Customer
                </div>

                <div class="border-dashed"></div>

                <table>
                    <tr>
                        <td colspan="2" style="padding-bottom: 2px">
                            Produk A
                        </td>
                    </tr>
                    <tr>
                        <td>2 x 10.000</td>
                        <td class="text-right">20.000</td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-bottom: 2px">
                            Produk A
                        </td>
                    </tr>
                    <tr>
                        <td>2 x 10.000</td>
                        <td class="text-right">20.000</td>
                    </tr>
                </table>

                <div class="border-dashed"></div>

                <table>
                    <tr>
                        <td>Total</td>
                        <td class="text-right bold">Rp 88.000</td>
                    </tr>
                    <tr>
                        <td>Diskon</td>
                        <td class="text-right">-</td>
                    </tr>
                    <tr>
                        <td>Bayar (Cash)</td>
                        <td class="text-right">100.000</td>
                    </tr>
                    <tr>
                        <td>Kembali</td>
                        <td class="text-right">12.000</td>
                    </tr>
                </table>

                <div class="border-dashed"></div>

                <div class="text-center" style="margin-top: 10px">
                    <p>{{ form.receipt_footer }}</p>
                </div>
            </div>

            <p class="text-xs text-center text-gray-400">
                Preview tampilan pada printer 58mm/80mm
            </p>
        </div>
    </div>
</template>
<style scoped>
/* --- RESET & BASIC SETUP --- */
.bodyPrev {
    font-family: "Courier New", Courier, monospace;
    /* Font struk */
    font-size: 12px;
    margin: 0;
    /* padding: 20px 0; */
    /* Jarak atas bawah di mode preview */
    /* background-color: #525252; */
    /* Latar belakang gelap untuk mode preview */
    /* color: #000; */
    display: flex;
    justify-content: center;
    /* min-height: 100vh; */
    /* width: fit-content; */
    /* max-width: 2; */
    /* margin-left: auto; */
    /* margin-right: auto; */
}

/* --- KERTAS STRUK (PREVIEW MODE) --- */
.sheet {
    background-color: #fff;
    width: 58mm;
    /* Sesuaikan ukuran kertas (58mm atau 80mm) */
    padding: 5mm;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
    /* Efek bayangan kertas */
    /* margin-bottom: 60px; */
    /* Ruang untuk tombol di bawah */
}

/* --- UTILITY CLASSES --- */
.text-center {
    text-align: center;
}

.text-right {
    text-align: right;
}

.bold {
    font-weight: bold;
}

.uppercase {
    text-transform: uppercase;
}

.border-dashed {
    border-bottom: 1px dashed #000;
    margin: 8px 0;
}

table {
    width: 100%;
    border-collapse: collapse;
}

td {
    vertical-align: top;
    padding: 2px 0;
}
</style>
