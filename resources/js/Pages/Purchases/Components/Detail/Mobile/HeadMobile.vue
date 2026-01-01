<script setup>
import { ref } from "vue";
import { usePurchaseAnalytics } from "@/Composable/usePurchaseAnalytics";
const props = defineProps({
    purchase: Object,
});
const showAnalysisModal = ref(false);
// Helper Formatter
const rp = (n) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(n || 0);
const formatDate = (date) =>
    date
        ? new Date(date).toLocaleDateString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
          })
        : "-";
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
} = usePurchaseAnalytics(props.purchase.items);
</script>

<script>
import { defineComponent, h } from "vue";
import BottomSheet from "../../../../../Components/BottomSheet.vue";

const StatCard = defineComponent({
    props: ["label", "value", "isDanger", "isWarning", "isSuccess", "isSubtle"],
    setup(props) {
        let valueClass = "text-gray-800 dark:text-gray-200";
        let bgClass =
            "bg-gray-50 dark:bg-gray-800 border-gray-100 dark:border-gray-700";

        if (props.isDanger) {
            valueClass = "text-red-600";
            bgClass =
                "bg-red-50 dark:bg-red-900/20 border-red-100 dark:border-red-800";
        } else if (props.isWarning) {
            valueClass = "text-orange-600";
            bgClass =
                "bg-orange-50 dark:bg-orange-900/20 border-orange-100 dark:border-orange-800";
        } else if (props.isSuccess) {
            valueClass = "text-green-600";
            bgClass =
                "bg-green-50 dark:bg-green-900/20 border-green-100 dark:border-green-800";
        } else if (props.isSubtle) {
            valueClass = "text-gray-400";
        }

        return () =>
            h(
                "div",
                {
                    class: `p-2 rounded border ${bgClass} flex flex-col justify-between min-h-[50px]`,
                },
                [
                    h(
                        "span",
                        {
                            class: "text-[9px] text-gray-400 leading-tight mb-1",
                        },
                        props.label
                    ),
                    h(
                        "span",
                        {
                            class: `text-sm font-bold leading-none ${valueClass}`,
                        },
                        props.value || "-"
                    ),
                ]
            );
    },
});
</script>
<template>
    <div
        class="p-4 space-y-4 bg-white border border-gray-100 shadow-sm dark:bg-gray-900 rounded-xl dark:border-gray-800"
    >
        <div>
            <div class="flex items-start justify-between mb-3">
                <div>
                    <p
                        class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold"
                    >
                        Supplier
                    </p>
                    <h3
                        class="text-lg font-bold leading-tight text-lime-600 dark:text-lime-400"
                    >
                        {{ purchase.supplier?.name }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ purchase.supplier?.address }} |
                        {{ purchase.supplier?.phone }}
                    </p>
                </div>
            </div>

            <div
                class="grid grid-cols-2 gap-3 bg-gray-50 dark:bg-gray-800 p-2.5 rounded-lg border border-gray-300 dark:border-gray-700"
            >
                <div>
                    <p class="text-[10px] text-gray-400 mb-0.5">
                        Tanggal Order
                    </p>
                    <p
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{ formatDate(purchase.transaction_date) }}
                    </p>
                </div>
                <div class="pl-3 border-l border-gray-400 dark:border-gray-700">
                    <p class="text-[10px] text-gray-400 mb-0.5">
                        Tanggal Datang
                    </p>
                    <p
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{
                            purchase.received_at
                                ? formatDate(purchase.received_at)
                                : "-"
                        }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="p-3 border rounded-lg bg-lime-50 dark:bg-lime-900/10 border-lime-100 dark:border-lime-900/30"
        >
            <h4
                class="text-[10px] font-bold text-lime-700 dark:text-lime-400 uppercase tracking-wide mb-2 flex items-center gap-1"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="w-3 h-3"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a1 1 0 100-2 1 1 0 000 2z"
                    />
                </svg>
                Ringkasan Keuangan
            </h4>
            <div class="flex flex-col gap-2">
                <div
                    class="flex items-center justify-between p-3 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                >
                    <span
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >Jml Nota</span
                    >
                    <span
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{ purchase.invoices?.length || "-" }}
                    </span>
                </div>

                <div
                    class="flex items-center justify-between p-3 border border-gray-100 rounded-lg bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                >
                    <span
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase"
                        >Nilai PO</span
                    >
                    <span
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        {{ rp(total_rupiah_dipesan) }}
                    </span>
                </div>

                <div
                    :class="[
                        'flex items-center justify-between p-3 border border-l-4 rounded-lg transition-colors',
                        // LOGIKA: Jika Realisasi (Diterima) melebihi Budget (Dipesan) -> Merah
                        total_rupiah_diterima > total_rupiah_dipesan
                            ? 'border-l-red-500 bg-red-50 border-red-100 dark:bg-red-900/20 dark:border-red-900/50'
                            : 'border-l-emerald-500 bg-emerald-50 border-emerald-100 dark:bg-emerald-900/20 dark:border-emerald-900/50',
                    ]"
                >
                    <span
                        :class="[
                            'text-xs font-bold tracking-wide uppercase',
                            total_rupiah_diterima > total_rupiah_dipesan
                                ? 'text-red-700 dark:text-red-400'
                                : 'text-emerald-700 dark:text-emerald-400',
                        ]"
                    >
                        Fisik Diterima
                    </span>

                    <span
                        :class="[
                            'text-sm font-black',
                            total_rupiah_diterima > total_rupiah_dipesan
                                ? 'text-red-700 dark:text-red-400'
                                : 'text-emerald-700 dark:text-emerald-400',
                        ]"
                    >
                        {{
                            purchase.received_at
                                ? rp(total_rupiah_diterima)
                                : "-"
                        }}
                    </span>
                </div>
            </div>
        </div>
        <button
            @click="showAnalysisModal = true"
            class="w-full bg-white dark:bg-gray-900 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-800 flex justify-between items-center group active:scale-[0.99] transition"
        >
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center justify-center w-10 h-10 rounded-full text-lime-600 bg-lime-50 dark:bg-lime-900/20 dark:text-lime-400"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-5 h-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </div>
                <div class="text-left">
                    <h4
                        class="text-sm font-bold text-gray-800 dark:text-gray-100"
                    >
                        Analisis Discrepancy
                    </h4>
                    <p class="text-[10px] text-gray-500">
                        Klik untuk melihat detail perbandingan PO vs Fisik
                    </p>
                </div>
            </div>
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5 text-gray-400 transition group-hover:text-blue-500"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>
    </div>
    <BottomSheet
        :show="showAnalysisModal"
        @close="showAnalysisModal = false"
        title="Analisis Detail (PO vs Fisik)"
    >
        <div class="">
            <div class="grid grid-cols-3 gap-2">
                <StatCard label="Barang Sesuai" :value="total_barang_sesuai" />
                <StatCard label="Macam Dipesan" :value="total_macam_dipesan" />
                <StatCard
                    label="Macam Diterima"
                    :value="purchase.received_at ? total_macam_diterima : ''"
                    is-subtle
                />

                <StatCard label="Qty Total Pesan" :value="total_qty_dipesan" />
                <StatCard
                    label="Qty Total Terima"
                    :value="purchase.received_at ? total_qty_diterima : ''"
                    is-subtle
                />
                <StatCard
                    label="Qty Kekurangan"
                    :value="produk_qty_kurang"
                    :is-danger="produk_qty_kurang > 0"
                />

                <StatCard
                    label="Qty Kelebihan"
                    :value="purchase.received_at ? produk_qty_lebih : ''"
                    :is-warning="produk_qty_lebih > 0"
                />
                <StatCard
                    label="Item Kosong"
                    :value="kosong"
                    :is-danger="kosong > 0"
                />
                <StatCard
                    label="Harga Naik"
                    :value="macam_harga_naik"
                    :is-warning="macam_harga_naik > 0"
                />

                <StatCard
                    label="Harga Turun"
                    :value="macam_harga_turun"
                    :is-success="macam_harga_turun > 0"
                />
                <StatCard label="Barang Baru" :value="baru_pengganti" />
                <div
                    class="flex flex-col justify-center col-span-1 p-2 border border-gray-100 rounded bg-gray-50 dark:bg-gray-800 dark:border-gray-700"
                >
                    <span class="text-[9px] text-gray-400 leading-tight"
                        >Catatan</span
                    >
                    <span class="text-xs font-medium truncate">{{
                        purchase.notes || "-"
                    }}</span>
                </div>
            </div>

            <p class="text-center text-[10px] text-gray-400 mt-6">
                Data ini diperbarui otomatis berdasarkan input nota kedatangan.
            </p>
        </div>
    </BottomSheet>
</template>
<style scoped>
/* Animasi Slide Up untuk Modal */
.animate-slide-up {
    animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUp {
    from {
        transform: translateY(100%);
    }
    to {
        transform: translateY(0);
    }
}
</style>
