<script setup>
defineProps({
    showScanner: {
        type: Boolean,
        default: false,
    },
    activeScannerType: String,
});

const emit = defineEmits(["stopScanner"]);
</script>
<template>
    <div
        v-if="showScanner"
        class="fixed inset-0 z-[70] bg-black flex flex-col animate-fade-in"
    >
        <div
            class="absolute top-0 z-10 flex items-center justify-between w-full p-4 text-white bg-black/50 backdrop-blur-sm"
        >
            <div class="flex items-center gap-2">
                <span v-if="activeScannerType === 'product'">📦</span>
                <span v-else>👤</span>

                <span class="text-lg font-bold">
                    Scan
                    {{ activeScannerType === "product" ? "Produk" : "Member" }}
                </span>
            </div>
            <button
                @click="$emit('stopScanner')"
                class="p-2 transition rounded-full bg-gray-800/80 hover:bg-gray-700"
            >
                ✕
            </button>
        </div>

        <div id="reader" class="w-full h-full bg-black"></div>

        <div class="absolute w-full px-4 text-center bottom-10">
            <div
                class="inline-block px-4 py-2 text-sm text-white border rounded-full bg-black/60 backdrop-blur-md border-white/20"
            >
                Arahkan kamera ke
                {{
                    activeScannerType === "product"
                        ? "Barcode Barang"
                        : "Kartu/QR Member"
                }}
            </div>
        </div>
    </div>
</template>
