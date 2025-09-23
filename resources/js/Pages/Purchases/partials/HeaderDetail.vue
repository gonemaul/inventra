<template>
    <div
        class="p-4 space-y-8 bg-white border shadow-xl border-lime-400 md:p-6 lg:p-8 rounded-2xl dark:bg-gray-900"
    >
        <!-- Header + Tombol -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <h2
                class="text-lg font-bold text-gray-800 md:text-2xl dark:text-gray-100"
            >
                Ringkasan Belanja
            </h2>
            <div class="flex flex-wrap justify-center gap-2">
                <button
                    class="px-4 py-2 text-sm font-medium text-white transition rounded-xl bg-lime-600 hover:bg-lime-700 md:text-base"
                >
                    Simpan
                </button>
                <button
                    class="px-4 py-2 text-sm font-medium text-gray-800 transition bg-gray-200 rounded-xl hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 md:text-base"
                >
                    Cetak
                </button>

                <Link
                    class="px-4 py-2 text-sm font-medium text-white transition bg-red-500 rounded-xl hover:bg-red-600 md:text-base"
                >
                    Kembali
                </Link>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Kiri: Info Belanja + Invoice -->
            <div class="space-y-6">
                <!-- Info Belanja -->
                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Info Belanja
                    </h3>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InfoCard label="Supplier" :value="data.supplier" />
                        <InfoCard
                            label="Nomor Belanja"
                            :value="'#' + data.order_code"
                        />
                        <InfoCard
                            label="Tanggal Order"
                            :value="formatTanggal(data.tanggal_order)"
                        />
                        <InfoCard
                            label="Tanggal Datang"
                            :value="formatTanggal(data.tanggal_datang)"
                        />
                    </div>
                </div>

                <!-- Ringkasan Invoice -->
                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Ringkasan Invoice
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <InfoCard
                            label="Jumlah Invoice"
                            :value="data.jumlah_invoice + ' Invoice'"
                        />
                        <InfoCard
                            label="Total Nominal"
                            :value="formatRupiah(data.total_nominal)"
                            highlight
                        />
                        <InfoCard label="Status" :value="data.status" status />
                    </div>
                </div>
            </div>

            <!-- Kanan: Produk + Catatan -->
            <div class="flex flex-col h-full gap-6">
                <!-- Ringkasan Produk -->
                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-800"
                >
                    <h3
                        class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Ringkasan Produk
                    </h3>
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <InfoCard
                            label="Macam Produk"
                            :value="data.total_produk"
                        />
                        <InfoCard
                            label="Item Dipesan"
                            :value="data.total_qty_order"
                        />
                        <InfoCard
                            label="Item Diterima"
                            :value="data.total_qty_diterima"
                        />
                        <InfoCard
                            label="Qty Sesuai"
                            :value="data.qty_sesuai"
                            type="success"
                        />
                        <InfoCard
                            label="Qty Kurang"
                            :value="data.qty_kurang"
                            type="danger"
                        />
                    </div>
                </div>

                <!-- Catatan -->
                <div
                    class="flex flex-col flex-1 p-4 border shadow-lg border-lime-400 rounded-xl bg-gray-50 dark:bg-gray-800"
                >
                    <h3
                        class="mb-2 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Catatan
                    </h3>
                    <p
                        class="flex-1 overflow-auto text-sm font-medium text-gray-800 dark:text-gray-200"
                    >
                        {{ data.catatan || "-" }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
import InfoCard from "./InfoCard.vue";

defineProps({
    data: {
        type: Object,
        required: true,
    },
    mode: {
        type: String,
        default: "detail",
    },
});

function formatTanggal(tanggal) {
    if (!tanggal) return "-";
    return new Date(tanggal).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

function formatRupiah(value) {
    if (!value && value !== 0) return "-";
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
}
</script>
