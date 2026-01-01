<template>
    <div class="space-y-8 dark:bg-gray-800">
        <!-- <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <h2
                class="text-lg font-bold text-gray-800 md:text-2xl dark:text-gray-100"
            >
                Ringkasan Belanja
            </h2>
            <div class="flex flex-wrap justify-center gap-2">
                <slot name="action-buttons"></slot>
            </div>
        </div> -->

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-6">
                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-900"
                >
                    <div class="flex justify-between">
                        <h3
                            class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                        >
                            Info Transaksi
                        </h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <InfoCard label="Supplier">
                            <div class="flex flex-col">
                                <span class="font-medium">{{
                                    data.supplier
                                        ? data.supplier.name
                                        : "Umum/Cash"
                                }}</span>
                                <span class="text-sm text-gray-500">{{
                                    data.supplier
                                        ? data.supplier.address +
                                          " | " +
                                          data.supplier.phone
                                        : "-"
                                }}</span>
                            </div>
                        </InfoCard>
                        <InfoCard label="Status">
                            <span
                                :class="[
                                    'px-3 py-1 rounded-md text-sm uppercase font-bold border',
                                    getStatusColor(data.status),
                                ]"
                            >
                                {{ data.status }}
                            </span>
                        </InfoCard>

                        <InfoCard
                            label="Tanggal Order"
                            :value="formatTanggal(data.transaction_date)"
                        />
                        <InfoCard
                            label="Tanggal Datang"
                            :value="formatTanggal(data.received_at)"
                        />
                    </div>
                </div>

                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-900"
                >
                    <h3
                        class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Ringkasan Keuangan
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <InfoCard
                            label="Jumlah Nota"
                            :value="
                                isReceived
                                    ? data.invoices.length + ' Nota'
                                    : '-'
                            "
                        />
                        <InfoCard
                            label="Nilai PO Awal"
                            :value="formatRupiah(total_rupiah_dipesan)"
                            :format-value="formatRupiah"
                        />

                        <InfoCard
                            label="Nilai Fisik Diterima"
                            :value="
                                isReceived
                                    ? formatRupiah(total_rupiah_diterima)
                                    : '-'
                            "
                            :format-value="formatRupiah"
                            highlight
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col h-full gap-6">
                <div
                    class="p-4 border shadow-lg rounded-xl border-lime-400 bg-gray-50 dark:bg-gray-900"
                >
                    <h3
                        class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300"
                    >
                        Analisis Discrepancy (PO vs Fisik Nota)
                    </h3>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        <InfoCard
                            label="Barang Sesuai Total"
                            :value="total_barang_sesuai"
                            type="success"
                        />

                        <InfoCard
                            label="Macam Dipesan"
                            :value="total_macam_dipesan"
                        />

                        <InfoCard
                            label="Macam Diterima"
                            :value="isReceived ? total_macam_diterima : '-'"
                        />

                        <InfoCard
                            label="Qty Total Dipesan"
                            :value="total_qty_dipesan"
                        />

                        <InfoCard
                            label="Qty Total Diterima"
                            :value="isReceived ? total_qty_diterima : '-'"
                        />

                        <InfoCard
                            label="Qty Kekurangan"
                            :value="data.received_at ? produk_qty_kurang : '-'"
                            type="danger"
                        />

                        <InfoCard
                            label="Qty Kelebihan"
                            :value="data.received_at ? produk_qty_lebih : '-'"
                            type="warning"
                        />

                        <InfoCard
                            label="Item Kosong (Qty 0)"
                            :value="kosong"
                            type="danger"
                        />

                        <InfoCard
                            label="Harga Naik (Macam)"
                            :value="macam_harga_naik"
                            type="warning"
                        />

                        <InfoCard
                            label="Harga Turun (Macam)"
                            :value="macam_harga_turun"
                            type="info"
                        />

                        <InfoCard
                            label="Barang Baru/Substitusi"
                            :value="baru_pengganti"
                            type="info"
                        />
                        <InfoCard label="Catatan" :value="data.notes || '-'" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from "@inertiajs/vue3";
// Asumsi Anda memiliki InfoCard di folder yang sama (./InfoCard.vue)
import InfoCard from "./InfoCard.vue";
import { usePurchaseAnalytics } from "@/Composable/usePurchaseAnalytics";
import { computed } from "vue";

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    // Mode prop tidak digunakan di sini, tapi dipertahankan untuk kompatibilitas
    mode: {
        type: String,
        default: "detail",
    },
});

function formatTanggal(tanggal) {
    if (!tanggal) return "-";
    return new Date(tanggal).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "numeric",
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
const isReceived = computed(
    () => props.data.status === "diterima" || props.data.status === "checking"
);
const totalAddedCost =
    (props.data.shipping_cost || 0) + (props.data.other_costs || 0);
const total_nominal =
    (props.data.invoices_sum_total_amount || 0) + totalAddedCost;

const getStatusColor = (status) => {
    const map = {
        // 1. Draft: Abu-abu (Netral)
        draft: "bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700",

        // 2. Dipesan: Biru (Info/Aktif)
        dipesan:
            "bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800",

        // 3. Dikirim: Oranye (Proses/Perjalanan - Truk)
        dikirim:
            "bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:border-orange-800",

        // 4. Diterima: Cyan/Teal (Barang sampai, fresh)
        diterima:
            "bg-cyan-50 text-cyan-700 border border-cyan-200 dark:bg-cyan-900/30 dark:text-cyan-300 dark:border-cyan-800",

        // 5. Checking: Ungu (Proses Audit/Pengecekan)
        checking:
            "bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800",

        // 6. Selesai: Hijau (Sukses)
        selesai:
            "bg-green-50 text-green-700 border border-green-200 dark:bg-green-900/30 dark:text-green-300 dark:border-green-800",

        // 7. Batal: Merah (Danger)
        dibatalkan:
            "bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800",
    };

    return map[status] || "bg-gray-100 text-gray-600 border border-gray-200";
};
const {
    kosong,
    baru_pengganti,
    total_macam_dipesan,
    total_macam_diterima,
    total_qty_dipesan,
    total_qty_diterima,
    total_rupiah_dipesan,
    total_rupiah_diterima,
    total_barang_sesuai,
    macam_harga_naik,
    macam_harga_turun,
    produk_qty_lebih, // Sum selisih
    produk_qty_kurang,
} = usePurchaseAnalytics(props.data.items);
</script>
