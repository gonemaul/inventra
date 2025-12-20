<script setup>
import Modal from "@/Components/Modal.vue";
import { computed, ref } from "vue"; // 1. Impor 'ref'
import BottomSheet from "./BottomSheet.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    imageUrl: {
        // Ini adalah path relatif, cth: 'products/image.webp'
        type: String,
        default: null,
    },
    productName: {
        type: String,
        default: "Detail Gambar",
    },
});

const emit = defineEmits(["close"]);

const fullImageUrl = computed(() => {
    return props.imageUrl ?? null;
});

// 2. Tambahkan state untuk loading
const isDownloading = ref(false);

/**
 * Fungsi untuk mengunduh gambar menggunakan Fetch/Blob
 * dan mengganti namanya.
 */
async function downloadImage() {
    if (!fullImageUrl.value) return;

    isDownloading.value = true;
    try {
        // 1. Ambil data gambar
        const response = await fetch(fullImageUrl.value);
        if (!response.ok) throw new Error("Gagal mengambil gambar");

        // 2. Ubah jadi Blob
        const blob = await response.blob();

        // 3. Buat URL sementara
        const blobUrl = URL.createObjectURL(blob);

        // 4. Buat link tersembunyi
        const link = document.createElement("a");
        link.style.display = "none";
        link.href = blobUrl;

        // 5. Tentukan nama file baru (PENTING)
        // Ambil ekstensi file dari URL asli
        const fileExtension = props.imageUrl.split(".").pop();
        // Bersihkan nama produk dari karakter ilegal
        const safeName = props.productName.replace(/[/\\?%*:|"<>]/g, "-");

        link.download = `${safeName}.${fileExtension}`; // Cth: "Kaos Polos Hitam.webp"

        // 6. Klik link & bersihkan
        document.body.appendChild(link);
        link.click();

        // 7. Hapus link dan URL sementara
        document.body.removeChild(link);
        URL.revokeObjectURL(blobUrl);
    } catch (error) {
        console.error("Gagal mengunduh gambar:", error);
        // (Opsional) Tampilkan toast error di sini
    } finally {
        isDownloading.value = false;
    }
}
</script>

<template>
    <!-- <Modal :show="show" @close="$emit('close')" maxWidth="xl"> -->
    <BottomSheet :show="show" @close="$emit('close')" :title="productName">
        <div
            class="relative pb-3 bg-white border-2 border-gray-300 rounded-lg md:border-0 dark:bg-gray-800"
        >
            <!-- <div
                class="flex items-center justify-between pb-3 border-b dark:border-gray-700"
            >
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    {{ productName }}
                </h3>
                <button
                    @click="$emit('close')"
                    class="p-1 text-gray-400 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-lime-500"
                >
                    <svg
                        class="w-6 h-6"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        ></path>
                    </svg>
                </button>
            </div> -->

            <div class="mt-4">
                <img
                    loading="lazy"
                    :src="fullImageUrl == null ? '/no-image.png' : fullImageUrl"
                    alt="Product Image Large"
                    class="object-contain w-full rounded-lg aspect-square"
                />
            </div>

            <div class="mt-5 text-center" v-if="fullImageUrl">
                <button
                    @click="downloadImage"
                    :disabled="isDownloading"
                    class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md bg-lime-500 hover:bg-lime-600 focus:outline-none focus:ring-2 focus:ring-lime-500 focus:ring-offset-2 disabled:opacity-50"
                >
                    {{ isDownloading ? "Mengunduh..." : "Unduh Gambar" }}
                </button>
            </div>
        </div>
    </BottomSheet>
</template>
