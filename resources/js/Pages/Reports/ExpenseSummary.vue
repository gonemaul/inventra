<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Pagination from "@/Components/ReportPagination.vue";

import { useExport } from "@/Composable/useExport";

const props = defineProps({
    filters: Object,
    summary: Object,
    chart_data: Array,
    expenses: Object,
});

const { exportToCsv } = useExport();

const doExport = () => {
    const dataToExport = props.expenses.data.map(e => ({
        Tanggal: e.date,
        Keterangan: e.name + (e.description ? ` (${e.description})` : ''),
        Kategori: e.category,
        Jumlah: e.amount
    }));
    exportToCsv('Laporan_Pengeluaran', dataToExport);
};


const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilter = () => {
    router.get(route("reports.expense-summary"), {
        start_date: form.start_date,
        end_date: form.end_date,
    }, { preserveScroll: true });
};

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

// Chart Logic
const maxChartValue = computed(() => {
    if (!props.chart_data.length) return 0;
    return Math.max(...props.chart_data.map(item => item.total));
});

const getBarWidth = (value) => {
    if (maxChartValue.value === 0) return '0%';
    return `${(value / maxChartValue.value) * 100}%`;
};

const chartColors = [
    'bg-blue-500', 'bg-red-500', 'bg-green-500', 'bg-yellow-500', 
    'bg-purple-500', 'bg-pink-500', 'bg-indigo-500', 'bg-teal-500'
];
</script>

<template>
    <Head title="Rincian Pengeluaran" />

    <AuthenticatedLayout headerTitle="Rincian Pengeluaran">
        <div class="space-y-6 pb-20">
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
            <!-- Filter & Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                 <!-- Filter -->
                 <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-sm font-bold text-gray-500 uppercase tracking-widest">Periode Laporan</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block mb-1 text-xs font-medium text-gray-400">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="form.start_date"
                                class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"
                            />
                        </div>
                         <div>
                            <label class="block mb-1 text-xs font-medium text-gray-400">Sampai Tanggal</label>
                            <input
                                type="date"
                                v-model="form.end_date"
                                class="w-full text-sm border-gray-300 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-red-500 focus:border-red-500"
                            />
                        </div>
                        <button
                            @click="applyFilter"
                            class="w-full px-4 py-2 text-sm font-bold text-white bg-red-600 rounded-lg shadow hover:bg-red-700 transition"
                        >
                            Tampilkan Data
                        </button>
                    </div>
                 </div>

                 <!-- Summary Cards -->
                 <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-6 text-white bg-gradient-to-br from-red-600 to-red-800 rounded-2xl shadow-lg relative overflow-hidden print:shadow-none print:border print:border-red-800 print:text-black">
                        <div class="relative z-10">
                            <p class="text-xs font-bold tracking-widest text-red-100 uppercase">Total Pengeluaran</p>
                            <h2 class="mt-2 text-3xl font-black">{{ formatRupiah(summary.total_expense) }}</h2>
                            <p class="mt-2 text-xs text-red-200">Total biaya operasional pada periode terpilih.</p>
                        </div>
                         <div class="absolute bottom-0 right-0 p-4 opacity-10">
                            <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>

                    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col justify-center">
                         <p class="text-xs font-bold tracking-widest text-gray-500 uppercase">Rata-Rata Harian</p>
                         <h2 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white">{{ formatRupiah(summary.avg_daily) }}</h2>
                         <p class="mt-1 text-xs text-gray-400">Burn rate harian.</p>
                    </div>
                 </div>
            </div>

            <!-- Breakdown Chart & Table -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Simple Chart -->
                <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl shadow-sm p-6 dark:bg-gray-800 dark:border-gray-700">
                    <h3 class="mb-4 text-lg font-bold text-gray-800 dark:text-white">Komposisi Pengeluaran</h3>
                    <div class="space-y-4">
                        <div v-for="(item, index) in chart_data" :key="index">
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-bold text-gray-700 dark:text-gray-300">{{ item.category }}</span>
                                <span class="text-gray-500">{{ formatRupiah(item.total) }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden">
                                <div 
                                    class="h-2.5 rounded-full transition-all duration-500" 
                                    :class="chartColors[index % chartColors.length]"
                                    :style="{ width: getBarWidth(item.total) }"
                                ></div>
                            </div>
                        </div>
                        <div v-if="chart_data.length === 0" class="text-center text-gray-400 py-8 text-sm">
                            Tidak ada data untuk ditampilkan grafik.
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 relative flex flex-col">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                        <h3 class="text-lg font-bold text-gray-800 dark:text-white">Rincian Transaksi</h3>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700/50">
                                <tr>
                                    <th class="px-6 py-3">Tanggal</th>
                                    <th class="px-6 py-3">Keterangan</th>
                                    <th class="px-6 py-3">Kategori</th>
                                    <th class="px-6 py-3 text-right">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                    <td class="px-6 py-3 whitespace-nowrap text-gray-500">
                                        {{ formatDate(expense.date) }}
                                    </td>
                                    <td class="px-6 py-3 font-medium text-gray-800 dark:text-white">
                                        {{ expense.name }}
                                        <div class="text-[10px] text-gray-400 font-normal" v-if="expense.description">{{ expense.description }}</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-1 text-xs font-bold bg-gray-100 rounded text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            {{ expense.category }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-red-600">
                                        {{ formatRupiah(expense.amount) }}
                                    </td>
                                </tr>
                                <tr v-if="expenses.data.length === 0">
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                        Data pengeluaran kosong.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                     <!-- Footer Pagination -->
                    <div class="p-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                        <Pagination :links="expenses.links" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
