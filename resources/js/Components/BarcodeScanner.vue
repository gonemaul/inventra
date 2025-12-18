<script setup>
import { onMounted, onUnmounted, ref } from "vue";
import { Html5Qrcode } from "html5-qrcode";

// --- EMITS ---
// 'result': Mengirim hasil scan ke parent
// 'close': Memberitahu parent untuk menutup scanner
const emit = defineEmits(["result", "close"]);

// --- STATE ---
let html5QrCode = null;
const isCameraReady = ref(false); // Untuk indikator loading kamera
const isFlashOn = ref(false);

// --- METHODS ---
const startScanner = async () => {
    // Pastikan DOM sudah render
    const elementId = "global-reader";

    try {
        html5QrCode = new Html5Qrcode(elementId);

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
            disableFlip: false,
        };
        // Config Resolusi (Pro Mode)
        const videoConstraints = {
            facingMode: "environment",
            focusMode: "continuous", // Mencoba memaksa auto-focus terus menerus
            width: { min: 640, ideal: 1920 }, // Minta HD
            height: { min: 480, ideal: 1080 },
        };

        // Mulai Kamera Belakang
        await html5QrCode.start(
            { facingMode: "environment" },
            { ...config, videoConstraints },
            onScanSuccess,
            onScanFailure
        );

        isCameraReady.value = true;
    } catch (err) {
        console.error("Error starting scanner:", err);
        alert("Gagal mengakses kamera. Pastikan izin diberikan.");
        emit("close"); // Tutup jika gagal
    }
};

const onScanSuccess = (decodedText, decodedResult) => {
    // Matikan scanner dulu sebelum emit result agar tidak double scan
    stopScanner().then(() => {
        emit("result", decodedText);
    });
};

const onScanFailure = (error) => {
    // Biarkan kosong agar console tidak penuh log error saat mencari fokus
    // console.warn(`Code scan error = ${error}`);
};

const stopScanner = async () => {
    if (html5QrCode) {
        try {
            // Cek apakah scanner sedang running sebelum stop
            if (html5QrCode.isScanning) {
                await html5QrCode.stop();
            }
            html5QrCode.clear();
        } catch (err) {
            console.error("Failed to stop scanner", err);
        }
    }
    html5QrCode = null;
    isCameraReady.value = false;
};
// 2. Logika FLASH / SENTER
const toggleFlash = () => {
    // if (!scanner.value) return;

    // Cek state saat ini dan balik kondisinya
    const targetState = !isFlashOn.value;

    html5QrCode
        .applyVideoConstraints({
            advanced: [{ torch: targetState }],
        })
        .then(() => {
            isFlashOn.value = targetState;
        })
        .catch((err) => {
            console.error("Fitur Flash tidak didukung di device ini", err);
            alert("Flash tidak didukung di perangkat/browser ini.");
        });
};

// --- LIFECYCLE ---
onMounted(() => {
    startScanner();
});

onUnmounted(() => {
    // Pastikan kamera mati saat komponen di-destroy/v-if false
    stopScanner();
});
</script>

<template>
    <div class="fixed inset-0 z-[9999] bg-black flex flex-col justify-center">
        <div id="global-reader" class="w-full h-full bg-black"></div>

        <div
            v-if="!isCameraReady"
            class="absolute inset-0 flex items-center justify-center bg-black z-[10000]"
        >
            <div class="text-center">
                <svg
                    class="w-10 h-10 mx-auto mb-2 animate-spin text-lime-500"
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
                <p class="text-sm font-medium text-white">Membuka Kamera...</p>
            </div>
        </div>
        <div class="absolute z-10 bottom-4 right-4">
            <button
                @click="toggleFlash"
                class="p-3 transition-colors rounded-full shadow-lg"
                :class="
                    isFlashOn
                        ? 'bg-yellow-400 text-black'
                        : 'bg-white text-gray-800'
                "
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <polygon
                        points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"
                    ></polygon>
                </svg>
            </button>
        </div>
        <div
            class="absolute top-0 left-0 right-0 p-6 flex justify-between items-start z-[10001]"
        >
            <div
                class="px-4 py-2 text-sm font-medium text-white border rounded-full bg-black/50 backdrop-blur-sm border-white/20"
            >
                Arahkan ke Barcode
            </div>

            <button
                @click="$emit('close')"
                class="flex items-center justify-center w-10 h-10 text-white transition rounded-full bg-white/20 backdrop-blur-md hover:bg-white/40"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-6 h-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>
        </div>

        <div
            class="absolute inset-0 flex items-center justify-center pointer-events-none z-[10000]"
        >
            <div
                class="relative w-64 h-64 border-2 border-lime-500/50 rounded-2xl"
            >
                <div
                    class="absolute top-0 left-0 w-5 h-5 border-t-4 border-l-4 border-lime-500 -mt-0.5 -ml-0.5 rounded-tl-lg"
                ></div>
                <div
                    class="absolute top-0 right-0 w-5 h-5 border-t-4 border-r-4 border-lime-500 -mt-0.5 -mr-0.5 rounded-tr-lg"
                ></div>
                <div
                    class="absolute bottom-0 left-0 w-5 h-5 border-b-4 border-l-4 border-lime-500 -mb-0.5 -ml-0.5 rounded-bl-lg"
                ></div>
                <div
                    class="absolute bottom-0 right-0 w-5 h-5 border-b-4 border-r-4 border-lime-500 -mb-0.5 -mr-0.5 rounded-br-lg"
                ></div>

                <div
                    class="absolute top-1/2 left-4 right-4 h-0.5 bg-red-500 shadow-[0_0_15px_rgba(239,68,68,0.8)] animate-scan"
                ></div>
            </div>
        </div>
    </div>
</template>

<style>
/* Style Global untuk menimpa bawaan html5-qrcode */
#global-reader video {
    object-fit: cover !important;
    width: 100% !important;
    height: 100% !important;
    border-radius: 0 !important;
}

/* Animasi Laser */
@keyframes scan {
    0% {
        transform: translateY(-100px);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(100px);
        opacity: 0;
    }
}
.animate-scan {
    animation: scan 2s linear infinite;
}
</style>
