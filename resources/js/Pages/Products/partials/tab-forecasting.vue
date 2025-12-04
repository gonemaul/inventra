<script setup>
const props = defineProps({
    inventory: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div
        class="p-5 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700"
    >
        <div class="flex items-center justify-between mb-4">
            <h2
                class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-white"
            >
                <span>🔮</span> Prediksi Stok (Forecasting)
            </h2>
            <span
                v-if="inventory.stock_status === 'critical'"
                class="px-2 py-1 text-xs font-bold text-red-700 bg-red-100 rounded-lg animate-pulse"
                >KRITIS</span
            >
            <span
                v-else-if="inventory.stock_status === 'warning'"
                class="px-2 py-1 text-xs font-bold text-yellow-700 bg-yellow-100 rounded-lg"
                >WASPADA</span
            >
            <span
                v-else
                class="px-2 py-1 text-xs font-bold text-green-700 bg-green-100 rounded-lg"
                >AMAN</span
            >
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-4">
                <div>
                    <p class="text-xs font-bold text-gray-500 uppercase">
                        Kecepatan Jual
                    </p>
                    <p class="text-2xl font-bold text-gray-800">
                        {{ inventory.avg_daily }}
                        <span class="text-sm font-normal text-gray-400"
                            >/ hari</span
                        >
                    </p>
                </div>

                <div v-if="inventory.avg_daily > 0">
                    <p class="text-xs font-bold text-gray-500 uppercase">
                        Estimasi Habis
                    </p>
                    <p class="text-xl font-bold text-gray-800">
                        {{ inventory.days_left }} Hari Lagi
                    </p>
                    <p class="mt-1 text-xs font-medium text-red-500">
                        Perkiraan tgl: {{ inventory.stockout_date }}
                    </p>
                </div>
                <div
                    v-else
                    class="p-3 text-sm text-gray-500 border border-gray-200 rounded-lg bg-gray-50"
                >
                    Belum cukup data penjualan untuk melakukan prediksi waktu
                    habis.
                </div>
            </div>

            <div
                class="flex flex-col items-center justify-center p-4 text-center border border-blue-100 bg-blue-50/50 rounded-xl"
            >
                <p class="mb-2 text-xs font-bold text-blue-500 uppercase">
                    Rekomendasi Sistem
                </p>

                <div v-if="inventory.suggested_qty > 0">
                    <p class="mb-2 text-sm text-gray-600">
                        Disarankan order minimal:
                    </p>
                    <div class="mb-1 text-4xl font-black text-blue-600">
                        {{ inventory.suggested_qty }}
                    </div>
                    <p class="mb-4 text-xs text-gray-400">
                        Untuk stok aman 14 hari
                    </p>

                    <button
                        class="w-full py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700"
                    >
                        + Buat Order Pembelian
                    </button>
                </div>

                <div v-else>
                    <div class="mb-2 text-3xl">👍</div>
                    <p class="text-sm font-bold text-gray-700">
                        Stok Mencukupi
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        Tidak perlu belanja sekarang.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
