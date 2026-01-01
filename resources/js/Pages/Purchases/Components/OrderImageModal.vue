<script setup>
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { ref, computed } from "vue";
import html2canvas from "html2canvas";
import BottomSheet from "@/Components/BottomSheet.vue";

const props = defineProps({
    show: Boolean,
    purchase: Object,
    storeName: { type: String, default: "INVENTRA STORE" },
});

const emit = defineEmits(["close"]);
const loading = ref(false);
const customMessage = ref("");

// Helper Format Tanggal
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

// Helper Format HP
const getWhatsappUrl = () => {
    const phone = props.purchase.supplier?.phone;
    if (!phone) return null;
    let p = phone.toString().replace(/[^0-9]/g, "");
    if (p.startsWith("0")) p = "62" + p.slice(1);

    // Pesan teks pendamping (penting jika list di-cut)
    let text = `Halo, ini orderan baru (Ref: ${props.purchase.reference_no}).\n`;
    text += `Detail pesanan ada di gambar terlampir.`;

    // Jika ada item yang terpotong di gambar, ingatkan di teks
    const message = customMessage.value != "" ? customMessage.value : text;
    if (remainingCount.value > 0) {
        message += `\n(Catatan: Ada ${remainingCount.value} barang lagi yang tidak muat di gambar. Mohon cek detail lengkapnya).`;
    }

    return `https://wa.me/${p}?text=${encodeURIComponent(message)}`;
};

// --- LOGIC "CUT" (PEMBATASAN ITEM) ---
const MAX_ITEMS_DISPLAY = 15; // Atur batas maksimal baris agar gambar tidak kepanjangan
const displayedItems = computed(() => {
    return props.purchase.items.slice(0, MAX_ITEMS_DISPLAY);
});
const remainingCount = computed(() => {
    return Math.max(0, props.purchase.items.length - MAX_ITEMS_DISPLAY);
});

// --- FUNGSI UTAMA ---
const processToWhatsapp = async () => {
    loading.value = true;
    // Ambil elemen khusus yang mau difoto
    const element = document.getElementById("clean-receipt");

    try {
        const canvas = await html2canvas(element, {
            scale: 2.5, // Scale tinggi biar teks tajam banget
            backgroundColor: "#ffffff",
            logging: false,
        });

        canvas.toBlob(async (blob) => {
            try {
                const item = new ClipboardItem({ "image/png": blob });
                await navigator.clipboard.write([item]);

                const url = getWhatsappUrl();
                if (url) {
                    window.open(url, "_blank");
                } else {
                    alert("Nomor HP Supplier tidak valid.");
                }
                emit("close");
            } catch (err) {
                console.error(err);
                alert("Gagal auto-copy. Browser tidak support.");
            } finally {
                loading.value = false;
            }
        });
    } catch (error) {
        console.error("Gagal render gambar:", error);
        loading.value = false;
    }
};
</script>

<template>
    <BottomSheet
        :show="show"
        @close="$emit('close')"
        title="📸 Preview Nota Order"
    >
        <div class="bg-white dark:bg-gray-800">
            <div
                class="flex justify-center p-4 mb-4 overflow-y-auto max-h-[350px] bg-gray-200 border border-gray-200 rounded-lg"
            >
                <div
                    id="clean-receipt"
                    class="bg-white text-gray-800 p-6 w-[320px] shadow-sm relative text-sm"
                >
                    <table class="w-full mb-2 text-xs">
                        <thead class="pb-2 font-bold">
                            <tr>
                                <th class="py-1 text-left w-[10%]">#</th>
                                <th class="py-1 text-left w-[70%]">
                                    NAMA BARANG
                                </th>
                                <th class="py-1 text-right w-[20%]">QTY</th>
                            </tr>
                        </thead>
                        <tbody
                            class="mt-2 border-t border-gray-800 divide-y divide-gray-300"
                        >
                            <tr
                                v-for="(item, index) in displayedItems"
                                :key="item.id"
                            >
                                <td class="py-2 text-gray-500 align-top">
                                    {{ index + 1 }}.
                                </td>
                                <td class="py-2 pr-1 align-top">
                                    <span class="block text-sm font-bold">{{
                                        item.product?.name
                                    }}</span>
                                    <span class="text-[10px] text-gray-600">
                                        {{ item.product?.brand?.name }}
                                        {{
                                            item.product?.size?.name
                                                ? " • " +
                                                  item.product?.size.name
                                                : ""
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="py-2 text-sm font-black text-right align-top whitespace-nowrap"
                                >
                                    {{ item.quantity }}
                                    <span class="text-[10px] font-normal">{{
                                        item.product?.unit?.name
                                    }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="remainingCount > 0"
                        class="p-2 mb-2 text-xs text-center bg-gray-100 border-t border-b border-gray-300"
                    >
                        <p class="italic font-bold text-gray-500">
                            ... dan {{ remainingCount }} barang lainnya.
                        </p>
                    </div>
                    <div
                        class="pt-2 ,t-4 text-center border-t-2 border-gray-600 border-dashed"
                    >
                        <p class="mb-1 text-xs font-bold uppercase">
                            TOTAL ITEM: {{ purchase.items.length }}
                        </p>
                        <p class="mb-1 text-xs font-medium text-gray-600">
                            Mohon info stok & harga terbaru. Terima Kasih.
                        </p>
                        <p class="text-[10px] text-gray-400">
                            {{ formatDate(purchase.transaction_date) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-5 space-y-2">
                <label
                    for="wa-caption"
                    class="block text-xs font-bold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                >
                    Pesan Custom
                </label>
                <textarea
                    id="wa-caption"
                    v-model="customMessage"
                    rows="2"
                    placeholder="Contoh: Halo gan, mohon segera diproses ya..."
                    class="w-full px-4 py-3 text-sm text-gray-900 transition-all border border-gray-300 resize-none bg-gray-50 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white"
                ></textarea>
            </div>

            <div class="flex flex-col gap-3">
                <button
                    @click="processToWhatsapp"
                    :disabled="loading"
                    class="flex items-center justify-center w-full gap-2 py-3 font-bold text-white transition transform bg-green-500 shadow-lg hover:bg-green-600 rounded-xl shadow-green-200 dark:shadow-none active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <svg
                        v-if="!loading"
                        class="w-5 h-5"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-5 h-5 text-white animate-spin"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    {{
                        loading
                            ? "Memproses Gambar..."
                            : "Salin & Buka WhatsApp"
                    }}
                </button>

                <SecondaryButton @click="$emit('close')" class="justify-center"
                    >Batal</SecondaryButton
                >
            </div>

            <p class="text-[10px] text-gray-400 text-center mt-4">
                *Gambar otomatis tersalin. Tinggal Paste (Ctrl+V) di chat WA.
            </p>
        </div>
    </BottomSheet>
</template>
