<script setup>
const props = defineProps({
    inventory: {
        type: Object,
        required: true,
    },
    product: {
        type: Object,
        required: true,
    },
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
</script>
<template>
    <div
        class="p-5 bg-white border border-gray-100 shadow-sm dark:bg-gray-800 rounded-xl dark:border-gray-700"
    >
        <h2
            class="flex items-center gap-2 mb-4 text-base font-bold text-gray-800 dark:text-white"
        >
            <span>📊</span> Analisis Pergerakan Barang
        </h2>

        <div
            v-if="inventory.is_dead_stock"
            class="p-4 mb-4 bg-gray-100 border-l-4 border-gray-500 rounded-r-lg"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">🐢</span>
                <div>
                    <h3 class="font-bold text-gray-800">
                        Dead Stock (Barang Mati)
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Barang ini masih menumpuk di gudang. Terakhir terjual
                        <strong>{{ inventory.last_sale }}</strong
                        >.
                    </p>
                    <p class="mt-1 text-sm text-yellow-700">
                        Produk ini tidak bergerak selama
                        <strong>{{ inventory.days_inactive }} hari</strong>.
                    </p>
                    <div
                        class="mt-2 text-xs font-mono bg-white p-1.5 rounded border border-gray-200 inline-block"
                    >
                        Uang Mandek:
                        {{
                            formatRupiah(product.purchase_price * product.stock)
                        }}
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else-if="inventory.avg_daily >= 1"
            class="p-4 mb-4 border-l-4 border-purple-500 rounded-r-lg bg-purple-50"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">🔥</span>
                <div>
                    <h3 class="font-bold text-purple-900">
                        Fast Moving (Laris)
                    </h3>
                    <p class="mt-1 text-sm text-purple-700">
                        Perputaran sangat cepat! Rata-rata laku
                        <strong>{{ inventory.avg_daily }} unit/hari</strong>.
                    </p>
                </div>
            </div>
        </div>

        <div
            v-else
            class="p-4 mb-4 border-l-4 border-blue-400 rounded-r-lg bg-blue-50"
        >
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚖️</span>
                <div>
                    <h3 class="font-bold text-blue-900">Pergerakan Normal</h3>
                    <p class="mt-1 text-sm text-blue-700">
                        Penjualan stabil. Terjual
                        <strong>{{ inventory.sales_30_days }} unit</strong>
                        dalam 30 hari terakhir.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <p class="mb-2 text-xs font-bold text-gray-400 uppercase">
                Tren Mingguan
            </p>
            <div class="flex items-end h-24 gap-1">
                <div
                    v-for="(d, i) in chart_data"
                    :key="i"
                    class="relative flex-1 transition bg-indigo-100 rounded-t hover:bg-indigo-300 group"
                    :style="{ height: Math.max(10, d.qty * 10) + '%' }"
                >
                    <div
                        class="opacity-0 group-hover:opacity-100 absolute -top-6 left-1/2 -translate-x-1/2 text-xs bg-gray-800 text-white px-1.5 rounded"
                    >
                        {{ d.qty }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
