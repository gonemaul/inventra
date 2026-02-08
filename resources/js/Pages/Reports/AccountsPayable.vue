<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { useExport } from "@/Composable/useExport";

const props = defineProps({
    invoices: Array,
    summary: Object,
});

const formatRupiah = (val) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(val);
const formatDate = (date) =>
    new Date(date).toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.invoices.map(inv => ({
        'Jatuh Tempo': formatDate(inv.due_date),
        'No Invoice': inv.invoice_number,
        'Supplier': inv.supplier_name,
        'Total Tagihan': inv.total_amount,
        'Sudah Bayar': inv.paid_amount,
        'Sisa Hutang': inv.remaining_amount,
        'Status': inv.status
    }));
    exportToCsv('Laporan_Hutang', dataToExport);
};

// Helper Status Jatuh Tempo
const getDueDateBadge = (days) => {
    if (days > 0)
        return {
            text: `Telat ${days} Hari`,
            class: "bg-red-100 text-red-700 border-red-200 animate-pulse",
        };
    if (days === 0)
        return {
            text: "Jatuh Tempo Hari Ini",
            class: "bg-orange-100 text-orange-700 border-orange-200",
        };
    return {
        text: `${Math.abs(days)} Hari Lagi`,
        class: "bg-green-100 text-green-700 border-green-200",
    };
};
</script>

<template>
    <Head title="Buku Hutang Dagang" />

    <AuthenticatedLayout headerTitle="Buku Hutang (Accounts Payable)">
        <div class="space-y-6">
            <!-- Toolbar -->
            <div class="flex flex-col gap-4 mb-4 md:flex-row md:items-center md:justify-between print:hidden">
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('reports.index')"
                        class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-gray-600 transition bg-white border border-gray-200 rounded-lg shadow-sm hover:bg-gray-50 hover:text-blue-600 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:text-white"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Kembali
                    </Link>
                </div>
                <div class="flex gap-2">
                    <button @click="doExport" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-green-600 rounded-lg shadow hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Export CSV
                    </button>
                    <button onclick="window.print()" class="flex items-center gap-2 px-4 py-2 text-sm font-bold text-white transition bg-blue-600 rounded-lg shadow hover:bg-blue-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        Print
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div
                    class="flex items-center justify-between p-6 bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
                >
                    <div>
                        <p
                            class="text-xs font-bold tracking-widest text-gray-400 uppercase"
                        >
                            Total Kewajiban Hutang
                        </p>
                        <h2
                            class="mt-1 text-3xl font-black text-gray-800 dark:text-white"
                        >
                            {{ formatRupiah(summary.total_debt) }}
                        </h2>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ summary.count }} Invoice Belum Lunas
                        </p>
                    </div>
                    <div class="p-3 bg-gray-100 rounded-full dark:bg-gray-700">
                        <svg
                            class="w-8 h-8 text-gray-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between p-6 border border-red-200 shadow-sm bg-red-50 rounded-2xl dark:bg-red-900/20 dark:border-red-800"
                >
                    <div>
                        <p
                            class="text-xs font-bold tracking-widest text-red-600 uppercase dark:text-red-400"
                        >
                            Lewat Jatuh Tempo (Overdue)
                        </p>
                        <h2
                            class="mt-1 text-3xl font-black text-red-600 dark:text-red-400"
                        >
                            {{ formatRupiah(summary.overdue_debt) }}
                        </h2>
                        <p class="mt-1 text-xs text-red-500">Segera Lunasi!</p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full dark:bg-red-800">
                        <svg
                            class="w-8 h-8 text-red-600 dark:text-red-300"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="overflow-hidden bg-white border border-gray-200 shadow-sm rounded-2xl dark:bg-gray-800 dark:border-gray-700"
            >
                <div
                    class="flex items-center justify-between p-4 border-b bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3 class="font-bold text-gray-800 dark:text-white">
                        Daftar Tagihan Supplier
                    </h3>
                    <button
                        class="text-xs font-bold text-blue-600 hover:underline"
                    >
                        Download Excel
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead
                            class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700"
                        >
                            <tr>
                                <th class="px-6 py-3">Jatuh Tempo</th>
                                <th class="px-6 py-3">Invoice & Supplier</th>
                                <th class="px-6 py-3 text-right">
                                    Total Tagihan
                                </th>
                                <th class="px-6 py-3 text-right">
                                    Sudah Bayar
                                </th>
                                <th
                                    class="px-6 py-3 font-bold text-right text-red-600"
                                >
                                    Sisa Hutang
                                </th>
                                <th class="px-6 py-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-gray-100 dark:divide-gray-700"
                        >
                            <tr
                                v-for="inv in invoices"
                                :key="inv.id"
                                class="transition hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            >
                                <td class="px-6 py-3">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ formatDate(inv.due_date) }}
                                    </div>
                                    <span
                                        class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase border shadow-sm"
                                        :class="
                                            getDueDateBadge(inv.days_overdue)
                                                .class
                                        "
                                    >
                                        {{
                                            getDueDateBadge(inv.days_overdue)
                                                .text
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-3">
                                    <div
                                        class="font-bold text-gray-800 dark:text-white"
                                    >
                                        {{ inv.invoice_number }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ inv.supplier_name }}
                                    </div>
                                </td>

                                <td class="px-6 py-3 text-right text-gray-500">
                                    {{ formatRupiah(inv.total_amount) }}
                                </td>
                                <td class="px-6 py-3 text-right text-green-600">
                                    {{ formatRupiah(inv.paid_amount) }}
                                </td>
                                <td
                                    class="px-6 py-3 font-black text-right text-red-600 bg-red-50/50 dark:bg-red-900/10"
                                >
                                    {{ formatRupiah(inv.remaining_amount) }}
                                </td>

                                <td class="px-6 py-3 text-center">
                                    <span
                                        class="px-2 py-1 text-xs font-bold uppercase rounded"
                                        :class="
                                            inv.status === 'partial'
                                                ? 'bg-yellow-100 text-yellow-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ inv.status }}
                                    </span>
                                    <button
                                        class="block w-full mt-2 text-[10px] text-blue-600 hover:underline"
                                    >
                                        Bayar Sekarang
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="invoices.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center text-green-500"
                                    >
                                        <svg
                                            class="w-12 h-12 mb-2"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            ></path>
                                        </svg>
                                        <h3
                                            class="text-lg font-bold text-gray-800 dark:text-white"
                                        >
                                            Tidak Ada Hutang!
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Semua tagihan supplier sudah lunas.
                                            Keuangan sehat.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
